<?php
/**
 * REST API controller for the reconstitution calculator.
 *
 * What: Exposes presets and calculation endpoints for the frontend JS.
 * Who calls it: rest_api_init hook in PRC_Calculator::boot().
 * Dependencies: PRC_Preset_Provider, PRC_Math.
 *
 * @see class-prc-preset-provider.php — Data source for presets.
 * @see class-prc-math.php           — Calculation engine.
 * @see class-prc-shortcode.php      — Frontend JS consumes these endpoints.
 */

declare(strict_types=1);

/**
 * REST endpoints for the reconstitution calculator.
 *
 * Namespace: prc/v1
 * Endpoints:
 *   GET /presets           — List all available peptide presets.
 *   GET /presets/{slug}    — Get a single preset by slug.
 *   POST /calculate        — Run reconstitution math server-side.
 */
class PRC_Rest_Controller {

	/**
	 * REST namespace.
	 */
	private const NAMESPACE = 'prc/v1';

	/**
	 * Register all routes.
	 *
	 * @return void Side effect: registers REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/presets',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_presets' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/presets/(?P<slug>[a-z0-9-]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_preset' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'slug' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_title',
						'required'          => true,
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/calculate',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'calculate' ],
				'permission_callback' => '__return_true',
				'args'                => $this->get_calculate_args(),
			]
		);
	}

	/**
	 * GET /presets — return all available presets.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_presets( \WP_REST_Request $request ): \WP_REST_Response {
		$provider = new PRC_Preset_Provider();
		$presets  = $provider->get_all_presets();

		return new \WP_REST_Response( $presets, 200 );
	}

	/**
	 * GET /presets/{slug} — return a single preset.
	 *
	 * @param \WP_REST_Request $request Request with 'slug' param.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_preset( \WP_REST_Request $request ) {
		$slug     = $request->get_param( 'slug' );
		$provider = new PRC_Preset_Provider();
		$presets  = $provider->get_all_presets();

		foreach ( $presets as $preset ) {
			if ( $preset['slug'] === $slug ) {
				return new \WP_REST_Response( $preset, 200 );
			}
		}

		return new \WP_Error(
			'prc_preset_not_found',
			__( 'Preset not found.', 'peptide-reconstitution-calculator' ),
			[ 'status' => 404 ]
		);
	}

	/**
	 * POST /calculate — run reconstitution math.
	 *
	 * Accepts vial_mg, water_ml, desired_dose, dose_unit and returns
	 * concentration, injection volume, syringe units, and doses per vial.
	 *
	 * @param \WP_REST_Request $request Request with calculation params.
	 * @return \WP_REST_Response
	 */
	public function calculate( \WP_REST_Request $request ): \WP_REST_Response {
		$result = PRC_Math::calculate(
			(float) $request->get_param( 'vial_mg' ),
			(float) $request->get_param( 'water_ml' ),
			(float) $request->get_param( 'desired_dose' ),
			(string) $request->get_param( 'dose_unit' )
		);

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * Define validation args for the /calculate endpoint.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_calculate_args(): array {
		return [
			'vial_mg'      => [
				'type'              => 'number',
				'required'          => true,
				'sanitize_callback' => 'floatval',
				'validate_callback' => fn( $v ) => is_numeric( $v ) && (float) $v > 0,
			],
			'water_ml'     => [
				'type'              => 'number',
				'required'          => true,
				'sanitize_callback' => 'floatval',
				'validate_callback' => fn( $v ) => is_numeric( $v ) && (float) $v > 0,
			],
			'desired_dose' => [
				'type'              => 'number',
				'required'          => true,
				'sanitize_callback' => 'floatval',
				'validate_callback' => fn( $v ) => is_numeric( $v ) && (float) $v > 0,
			],
			'dose_unit'    => [
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => fn( $v ) => in_array( $v, [ 'mcg', 'mg' ], true ),
			],
		];
	}
}
