<?php
/**
 * Reconstitution math engine.
 *
 * What: Pure calculation functions — no side effects, no database access.
 *       Computes concentration, injection volume, syringe units, and
 *       doses per vial from user inputs.
 * Who calls it: PRC_Rest_Controller (server-side), also mirrored in JS.
 * Dependencies: None.
 *
 * @see assets/js/calculator.js — Client-side mirror of this math.
 * @see api/class-prc-rest-controller.php — Server-side consumer.
 */

declare(strict_types=1);

/**
 * Static math functions for reconstitution calculations.
 *
 * All methods are pure — same inputs always produce same outputs.
 * The JS calculator mirrors this logic client-side for instant feedback;
 * the REST endpoint provides server-side validation.
 */
class PRC_Math {

	/**
	 * Standard insulin syringe size in units (100 IU = 1 mL).
	 *
	 * Mirrored in: assets/js/calculator.js (SYRINGE_UNITS_PER_ML)
	 * and prcConfig.syringeUnits (class-prc-shortcode.php).
	 * All three MUST stay in sync.
	 */
	private const SYRINGE_UNITS_PER_ML = 100;

	/**
	 * Run the full reconstitution calculation.
	 *
	 * @param float  $vial_mg      Amount of peptide in the vial (mg).
	 * @param float  $water_ml     Bacteriostatic water added (mL).
	 * @param float  $desired_dose Desired dose per injection.
	 * @param string $dose_unit    Unit of desired_dose: 'mcg' or 'mg'.
	 * @return array<string, mixed> Calculation results.
	 */
	public static function calculate(
		float $vial_mg,
		float $water_ml,
		float $desired_dose,
		string $dose_unit
	): array {
		// Normalize desired dose to mg for consistent math.
		$dose_mg = ( 'mcg' === $dose_unit )
			? $desired_dose / 1000
			: $desired_dose;

		$concentration_mg_per_ml = self::concentration_mg_per_ml( $vial_mg, $water_ml );
		$concentration_mcg_per_unit = self::concentration_mcg_per_unit( $vial_mg, $water_ml );
		$injection_ml = self::injection_volume_ml( $dose_mg, $concentration_mg_per_ml );
		$syringe_units = self::syringe_units( $injection_ml );
		$doses_per_vial = self::doses_per_vial( $vial_mg, $dose_mg );

		return [
			'concentration_mg_per_ml'    => round( $concentration_mg_per_ml, 4 ),
			'concentration_mcg_per_unit' => round( $concentration_mcg_per_unit, 2 ),
			'injection_volume_ml'        => round( $injection_ml, 4 ),
			'syringe_units'              => round( $syringe_units, 1 ),
			'doses_per_vial'             => floor( $doses_per_vial ),
			'total_peptide_mg'           => $vial_mg,
			'water_ml'                   => $water_ml,
			'desired_dose_mg'            => round( $dose_mg, 4 ),
			'desired_dose_display'       => $desired_dose,
			'dose_unit'                  => $dose_unit,
		];
	}

	/**
	 * Concentration after reconstitution in mg/mL.
	 *
	 * @param float $vial_mg  Peptide amount (mg).
	 * @param float $water_ml Water volume (mL).
	 * @return float mg per mL.
	 */
	public static function concentration_mg_per_ml( float $vial_mg, float $water_ml ): float {
		if ( $water_ml <= 0 ) {
			return 0;
		}

		return $vial_mg / $water_ml;
	}

	/**
	 * Concentration in mcg per insulin syringe unit.
	 *
	 * Why: Most users measure with insulin syringes (100 units = 1 mL).
	 * Knowing mcg-per-unit makes it easy to count tick marks.
	 *
	 * @param float $vial_mg  Peptide amount (mg).
	 * @param float $water_ml Water volume (mL).
	 * @return float mcg per syringe unit.
	 */
	public static function concentration_mcg_per_unit( float $vial_mg, float $water_ml ): float {
		if ( $water_ml <= 0 ) {
			return 0;
		}

		$total_mcg   = $vial_mg * 1000;
		$total_units = $water_ml * self::SYRINGE_UNITS_PER_ML;

		return $total_mcg / $total_units;
	}

	/**
	 * Volume to inject for a desired dose (mL).
	 *
	 * @param float $dose_mg              Desired dose in mg.
	 * @param float $concentration_mg_ml  Solution concentration in mg/mL.
	 * @return float Injection volume in mL.
	 */
	public static function injection_volume_ml( float $dose_mg, float $concentration_mg_ml ): float {
		if ( $concentration_mg_ml <= 0 ) {
			return 0;
		}

		return $dose_mg / $concentration_mg_ml;
	}

	/**
	 * Convert mL to insulin syringe units.
	 *
	 * @param float $ml Volume in mL.
	 * @return float Syringe units (100 units = 1 mL).
	 */
	public static function syringe_units( float $ml ): float {
		return $ml * self::SYRINGE_UNITS_PER_ML;
	}

	/**
	 * How many full doses fit in one vial.
	 *
	 * @param float $vial_mg Total peptide in vial (mg).
	 * @param float $dose_mg Dose per injection (mg).
	 * @return float Number of doses (floor to get whole doses).
	 */
	public static function doses_per_vial( float $vial_mg, float $dose_mg ): float {
		if ( $dose_mg <= 0 ) {
			return 0;
		}

		return $vial_mg / $dose_mg;
	}
}
