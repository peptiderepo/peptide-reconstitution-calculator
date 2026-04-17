<?php
/**
 * Preset provider — bridges PR Core dosing data to the calculator.
 *
 * What: Builds peptide-specific reconstitution presets from PR Core's
 *       repository classes. Falls back to built-in defaults when PR Core
 *       is not active.
 * Who calls it: PRC_Rest_Controller serves these to the JS calculator.
 * Dependencies: PR Core repositories (optional), PRC_Calculator::is_pr_core_active().
 *
 * @see class-prc-rest-controller.php           — Serves presets via REST.
 * @see class-prc-default-presets.php            — Hardcoded fallback presets.
 * @see PR_Core_Peptide_Repository (PR Core)     — Peptide list source.
 * @see PR_Core_Dosing_Repository  (PR Core)     — Dosing data source.
 */

declare(strict_types=1);

/**
 * Assembles reconstitution presets — live data from PR Core or static defaults.
 */
class PRC_Preset_Provider {

	/**
	 * Cache key prefix for transient storage.
	 */
	private const CACHE_PREFIX = 'prc_presets_';

	/**
	 * How long to cache presets (seconds). 1 hour.
	 */
	private const CACHE_TTL_SECONDS = 3600;

	/**
	 * Get all available presets for the calculator dropdown.
	 *
	 * Returns a merged list: PR Core live data (if active) + built-in defaults
	 * for peptides not yet in the database.
	 *
	 * @return array<int, array<string, mixed>> Array of preset objects.
	 */
	public function get_all_presets(): array {
		if ( ! PRC_Calculator::is_pr_core_active() ) {
			return PRC_Default_Presets::get_all();
		}

		$cached = get_transient( self::CACHE_PREFIX . 'all' );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$presets = $this->build_presets_from_pr_core();

		// Merge any default presets for peptides not covered by PR Core.
		$live_slugs = array_column( $presets, 'slug' );
		foreach ( PRC_Default_Presets::get_all() as $default ) {
			if ( ! in_array( $default['slug'], $live_slugs, true ) ) {
				$presets[] = $default;
			}
		}

		set_transient( self::CACHE_PREFIX . 'all', $presets, self::CACHE_TTL_SECONDS );

		return $presets;
	}

	/**
	 * Invalidate the presets cache.
	 *
	 * Called when PR Core dosing data changes so the calculator
	 * picks up updated values.
	 *
	 * @return void Side effect: deletes transient.
	 */
	public function invalidate_cache(): void {
		delete_transient( self::CACHE_PREFIX . 'all' );
	}

	/**
	 * Build preset entries from PR Core's live peptide + dosing data.
	 *
	 * For each published peptide with subcutaneous dosing rows, extracts
	 * common vial sizes, dose range, and typical reconstitution volume.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function build_presets_from_pr_core(): array {
		// Why class_exists: PR_CORE_VERSION constant may be defined but classes
		// could fail to autoload (e.g. fatal error in PR Core bootstrap).
		// Fall back to empty array so the caller merges in defaults gracefully.
		if ( ! class_exists( 'PR_Core_Peptide_Repository' ) || ! class_exists( 'PR_Core_Dosing_Repository' ) ) {
			return [];
		}

		$peptide_repo = new PR_Core_Peptide_Repository();
		$dosing_repo  = new PR_Core_Dosing_Repository();

		$peptides = $peptide_repo->find_all( [ 'posts_per_page' => 100 ] );
		$presets  = [];

		foreach ( $peptides as $peptide ) {
			$rows = $dosing_repo->find_by_peptide(
				$peptide->id,
				[ 'route' => 'subcutaneous' ]
			);

			if ( empty( $rows ) ) {
				// No subcutaneous dosing data — skip this peptide.
				continue;
			}

			$preset = $this->build_single_preset( $peptide, $rows );
			if ( null !== $preset ) {
				$presets[] = $preset;
			}
		}

		return $presets;
	}

	/**
	 * Build a single preset from a peptide DTO and its dosing rows.
	 *
	 * @param object                        $peptide PR_Core_Peptide_DTO.
	 * @param PR_Core_Dosing_Row_DTO[]|null $rows    Active subcutaneous dosing rows.
	 * @return array<string, mixed>|null Null if data is insufficient.
	 */
	private function build_single_preset( object $peptide, ?array $rows ): ?array {
		if ( empty( $rows ) ) {
			return null;
		}

		// Extract dose range across all rows.
		$dose_mins = array_filter( array_map( fn( $r ) => $r->dose_min, $rows ) );
		$dose_maxs = array_filter( array_map( fn( $r ) => $r->dose_max, $rows ) );
		$dose_unit = $rows[0]->dose_unit ?: 'mcg';

		// Derive typical vial sizes from dose range.
		// Why: Most peptides are sold in standard vial sizes (2mg, 5mg, 10mg, 15mg, 30mg).
		$vial_sizes = $this->infer_vial_sizes( $dose_mins, $dose_maxs, $dose_unit );

		$display_name = '';
		if ( method_exists( $peptide, 'to_array' ) ) {
			$arr = $peptide->to_array();
			$display_name = $arr['display_name'] ?? $peptide->title ?? '';
		} else {
			$display_name = $peptide->title ?? '';
		}

		return [
			'slug'                    => $peptide->slug,
			'name'                    => $display_name ?: $peptide->title,
			'vial_sizes_mg'           => $vial_sizes,
			'default_vial_mg'         => $vial_sizes[0] ?? 5,
			'recommended_water_ml'    => $this->recommend_water_ml( $vial_sizes[0] ?? 5 ),
			'dose_range_min'          => ! empty( $dose_mins ) ? min( $dose_mins ) : null,
			'dose_range_max'          => ! empty( $dose_maxs ) ? max( $dose_maxs ) : null,
			'dose_unit'               => $dose_unit,
			'typical_frequency'       => $this->most_common_frequency( $rows ),
			'evidence_strength'       => $peptide->evidence_strength ?? 'preclinical',
			'dosing_row_count'        => count( $rows ),
			'source'                  => 'pr_core',
		];
	}

	/**
	 * Infer standard vial sizes from the dose range.
	 *
	 * Why: Peptide vendors use standard sizes. If a peptide's typical dose
	 * is 250mcg, it's usually sold in 5mg or 10mg vials. This heuristic
	 * picks the 2-3 most likely sizes.
	 *
	 * @param float[] $dose_mins Minimum doses from all rows.
	 * @param float[] $dose_maxs Maximum doses from all rows.
	 * @param string  $dose_unit Unit (mcg or mg).
	 * @return float[] Standard vial sizes in mg.
	 */
	private function infer_vial_sizes( array $dose_mins, array $dose_maxs, string $dose_unit ): array {
		$standard_sizes = [ 2, 5, 10, 15, 30 ];

		if ( empty( $dose_mins ) && empty( $dose_maxs ) ) {
			return [ 5, 10 ];
		}

		// Convert everything to mg for comparison.
		$max_dose_mg = 0;
		$all_doses   = array_merge( $dose_mins, $dose_maxs );

		foreach ( $all_doses as $dose ) {
			$mg = ( 'mcg' === $dose_unit ) ? $dose / 1000 : $dose;
			$max_dose_mg = max( $max_dose_mg, $mg );
		}

		// Pick vials that hold at least 10 doses at the max dose level.
		$viable = [];
		foreach ( $standard_sizes as $size ) {
			if ( $max_dose_mg > 0 && ( $size / $max_dose_mg ) >= 5 ) {
				$viable[] = $size;
			}
		}

		// Fallback: return two middle sizes if heuristic found nothing.
		return ! empty( $viable ) ? array_slice( $viable, 0, 3 ) : [ 5, 10 ];
	}

	/**
	 * Recommend reconstitution water volume based on vial size.
	 *
	 * Why: Standard practice is 1-2mL per 5mg, producing round concentrations.
	 *
	 * @param float $vial_mg Vial size in mg.
	 * @return float Recommended bacteriostatic water in mL.
	 */
	private function recommend_water_ml( float $vial_mg ): float {
		if ( $vial_mg <= 2 ) {
			return 1.0;
		}
		if ( $vial_mg <= 5 ) {
			return 2.0;
		}
		if ( $vial_mg <= 10 ) {
			return 2.0;
		}
		if ( $vial_mg <= 15 ) {
			return 3.0;
		}

		return 5.0;
	}

	/**
	 * Find the most common frequency value across dosing rows.
	 *
	 * @param PR_Core_Dosing_Row_DTO[] $rows Dosing rows.
	 * @return string|null Most common frequency, or null if none.
	 */
	private function most_common_frequency( array $rows ): ?string {
		$freqs = array_filter( array_map( fn( $r ) => $r->frequency, $rows ) );
		if ( empty( $freqs ) ) {
			return null;
		}

		$counts = array_count_values( $freqs );
		arsort( $counts );

		return array_key_first( $counts );
	}
}
