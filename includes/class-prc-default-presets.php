<?php
/**
 * Hardcoded default presets — fallback when PR Core is not active.
 *
 * What: Provides a curated list of popular peptide presets so the
 *       calculator is useful out of the box, even without the data layer.
 * Who calls it: PRC_Preset_Provider when PR Core is absent or as gap-fill.
 * Dependencies: None.
 *
 * @see class-prc-preset-provider.php — Merges these with live PR Core data.
 */

declare(strict_types=1);

/**
 * Static class containing built-in peptide presets.
 */
class PRC_Default_Presets {

	/**
	 * Get all built-in presets.
	 *
	 * Each preset includes common vial sizes, recommended reconstitution
	 * volume, typical dose ranges, and frequency. Data sourced from
	 * published literature and commonly available vendor specifications.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_all(): array {
		return [
			[
				'slug'                 => 'bpc-157',
				'name'                 => 'BPC-157',
				'vial_sizes_mg'        => [ 5, 10 ],
				'default_vial_mg'      => 5,
				'recommended_water_ml' => 2.0,
				'dose_range_min'       => 200,
				'dose_range_max'       => 500,
				'dose_unit'            => 'mcg',
				'typical_frequency'    => 'twice daily',
				'evidence_strength'    => 'observational',
				'dosing_row_count'     => 0,
				'source'               => 'default',
			],
			[
				'slug'                 => 'tb-500',
				'name'                 => 'TB-500',
				'vial_sizes_mg'        => [ 2, 5, 10 ],
				'default_vial_mg'      => 5,
				'recommended_water_ml' => 2.0,
				'dose_range_min'       => 2000,
				'dose_range_max'       => 5000,
				'dose_unit'            => 'mcg',
				'typical_frequency'    => 'twice weekly',
				'evidence_strength'    => 'preclinical',
				'dosing_row_count'     => 0,
				'source'               => 'default',
			],
			[
				'slug'                 => 'semaglutide',
				'name'                 => 'Semaglutide',
				'vial_sizes_mg'        => [ 2, 5, 10 ],
				'default_vial_mg'      => 5,
				'recommended_water_ml' => 2.0,
				'dose_range_min'       => 0.25,
				'dose_range_max'       => 2.4,
				'dose_unit'            => 'mg',
				'typical_frequency'    => 'weekly',
				'evidence_strength'    => 'meta-analysis',
				'dosing_row_count'     => 0,
				'source'               => 'default',
			],
			[
				'slug'                 => 'cjc-1295-dac',
				'name'                 => 'CJC-1295 (DAC)',
				'vial_sizes_mg'        => [ 2, 5 ],
				'default_vial_mg'      => 2,
				'recommended_water_ml' => 1.0,
				'dose_range_min'       => 1000,
				'dose_range_max'       => 2000,
				'dose_unit'            => 'mcg',
				'typical_frequency'    => 'weekly',
				'evidence_strength'    => 'preclinical',
				'dosing_row_count'     => 0,
				'source'               => 'default',
			],
			[
				'slug'                 => 'ipamorelin',
				'name'                 => 'Ipamorelin',
				'vial_sizes_mg'        => [ 2, 5 ],
				'default_vial_mg'      => 5,
				'recommended_water_ml' => 2.0,
				'dose_range_min'       => 100,
				'dose_range_max'       => 300,
				'dose_unit'            => 'mcg',
				'typical_frequency'    => 'three times daily',
				'evidence_strength'    => 'preclinical',
				'dosing_row_count'     => 0,
				'source'               => 'default',
			],
			[
				'slug'                 => 'pt-141',
				'name'                 => 'PT-141 (Bremelanotide)',
				'vial_sizes_mg'        => [ 2, 10 ],
				'default_vial_mg'      => 10,
				'recommended_water_ml' => 2.0,
				'dose_range_min'       => 500,
				'dose_range_max'       => 2000,
				'dose_unit'            => 'mcg',
				'typical_frequency'    => 'as needed',
				'evidence_strength'    => 'rct-large',
				'dosing_row_count'     => 0,
				'source'               => 'default',
			],
			[
				'slug'                 => 'mk-677',
				'name'                 => 'MK-677 (Ibutamoren)',
				'vial_sizes_mg'        => [ 15, 30 ],
				'default_vial_mg'      => 30,
				'recommended_water_ml' => 3.0,
				'dose_range_min'       => 10,
				'dose_range_max'       => 25,
				'dose_unit'            => 'mg',
				'typical_frequency'    => 'daily',
				'evidence_strength'    => 'rct-small',
				'dosing_row_count'     => 0,
				'source'               => 'default',
			],
			[
				'slug'                 => 'ghrp-6',
				'name'                 => 'GHRP-6',
				'vial_sizes_mg'        => [ 5, 10 ],
				'default_vial_mg'      => 5,
				'recommended_water_ml' => 2.0,
				'dose_range_min'       => 100,
				'dose_range_max'       => 300,
				'dose_unit'            => 'mcg',
				'typical_frequency'    => 'three times daily',
				'evidence_strength'    => 'preclinical',
				'dosing_row_count'     => 0,
				'source'               => 'default',
			],
		];
	}
}
