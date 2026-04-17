<?php
/**
 * Shortcode registration and frontend asset enqueueing.
 *
 * What: Registers [prc_calculator] shortcode, enqueues JS/CSS,
 *       and renders the calculator HTML shell.
 * Who calls it: PRC_Calculator::boot() registers this on init.
 * Dependencies: PRC_Preset_Provider (for inline preset data).
 *
 * @see assets/js/calculator.js  — Client-side calculator logic.
 * @see assets/css/calculator.css — Calculator styles.
 * @see class-prc-calculator.php  — Boot orchestrator.
 */

declare(strict_types=1);

/**
 * Handles the [prc_calculator] shortcode.
 */
class PRC_Shortcode {

	/**
	 * Whether assets have been enqueued this request.
	 *
	 * Prevents double-enqueue if shortcode appears multiple times.
	 *
	 * @var bool
	 */
	private bool $assets_enqueued = false;

	/**
	 * Register the shortcode with WordPress.
	 *
	 * @return void Side effect: registers shortcode.
	 */
	public function register(): void {
		add_shortcode( 'prc_calculator', [ $this, 'render' ] );
	}

	/**
	 * Render the calculator widget.
	 *
	 * Outputs the HTML structure and enqueues JS/CSS on first call.
	 * The JS calculator takes over from here — all interactivity
	 * is client-side for instant feedback.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render( $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'peptide' => '', // Pre-select a peptide by slug.
			],
			$atts,
			'prc_calculator'
		);

		$this->enqueue_assets();

		$selected_slug = sanitize_title( $atts['peptide'] );

		ob_start();
		$this->render_calculator_html( $selected_slug );
		return ob_get_clean();
	}

	/**
	 * Enqueue calculator JS and CSS, with inline preset data.
	 *
	 * Why inline presets: Avoids a REST round-trip on page load.
	 * The presets are small (< 5KB) and cached by the provider.
	 *
	 * @return void Side effect: enqueues scripts/styles.
	 */
	private function enqueue_assets(): void {
		if ( $this->assets_enqueued ) {
			return;
		}

		wp_enqueue_style(
			'prc-calculator',
			PRC_PLUGIN_URL . 'assets/css/calculator.css',
			[],
			PRC_VERSION
		);

		wp_enqueue_script(
			'prc-calculator',
			PRC_PLUGIN_URL . 'assets/js/calculator.js',
			[],
			PRC_VERSION,
			true
		);

		// Pass presets and config to JS.
		$provider = new PRC_Preset_Provider();
		wp_localize_script( 'prc-calculator', 'prcConfig', [
			'presets'      => $provider->get_all_presets(),
			'restUrl'      => esc_url_raw( rest_url( 'prc/v1' ) ),
			'nonce'        => wp_create_nonce( 'wp_rest' ),
			'syringeUnits' => 100,
			'i18n'         => [
				'selectPeptide'  => __( 'Select a peptide…', 'peptide-reconstitution-calculator' ),
				'customEntry'    => __( 'Custom / Manual Entry', 'peptide-reconstitution-calculator' ),
				'calculate'      => __( 'Calculate', 'peptide-reconstitution-calculator' ),
				'reset'          => __( 'Reset', 'peptide-reconstitution-calculator' ),
				'concentration'  => __( 'Concentration', 'peptide-reconstitution-calculator' ),
				'injectionVol'   => __( 'Injection Volume', 'peptide-reconstitution-calculator' ),
				'syringeUnits'   => __( 'Syringe Units', 'peptide-reconstitution-calculator' ),
				'dosesPerVial'   => __( 'Doses Per Vial', 'peptide-reconstitution-calculator' ),
				'perUnit'        => __( 'per unit', 'peptide-reconstitution-calculator' ),
				'disclaimer'     => __( 'For informational purposes only. Not medical advice. Always consult a qualified healthcare professional.', 'peptide-reconstitution-calculator' ),
			],
		] );

		$this->assets_enqueued = true;
	}

	/**
	 * Output the calculator HTML shell.
	 *
	 * The JS calculator populates the interactive elements.
	 * This provides the semantic structure and no-JS fallback message.
	 *
	 * @param string $selected_slug Pre-selected peptide slug (empty = none).
	 * @return void Side effect: outputs HTML.
	 */
	private function render_calculator_html( string $selected_slug ): void {
		?>
		<div class="prc-calculator" data-selected-slug="<?php echo esc_attr( $selected_slug ); ?>">
			<div class="prc-calculator__header">
				<h3 class="prc-calculator__title">
					<?php esc_html_e( 'Peptide Reconstitution Calculator', 'peptide-reconstitution-calculator' ); ?>
				</h3>
			</div>

			<div class="prc-calculator__body">
				<!-- Peptide selector -->
				<div class="prc-field">
					<label class="prc-field__label" for="prc-peptide-select">
						<?php esc_html_e( 'Peptide', 'peptide-reconstitution-calculator' ); ?>
					</label>
					<select id="prc-peptide-select" class="prc-field__input">
						<option value=""><?php esc_html_e( 'Select a peptide…', 'peptide-reconstitution-calculator' ); ?></option>
					</select>
				</div>

				<!-- Vial size -->
				<div class="prc-field">
					<label class="prc-field__label" for="prc-vial-mg">
						<?php esc_html_e( 'Vial Size', 'peptide-reconstitution-calculator' ); ?>
					</label>
					<div class="prc-field__row">
						<select id="prc-vial-select" class="prc-field__input prc-field__input--half"></select>
						<div class="prc-field__custom-wrap">
							<input type="number" id="prc-vial-mg" class="prc-field__input prc-field__input--half"
								   step="0.1" min="0.1" placeholder="5" />
							<span class="prc-field__unit"><?php esc_html_e( 'mg', 'peptide-reconstitution-calculator' ); ?></span>
						</div>
					</div>
				</div>

				<!-- Bacteriostatic water -->
				<div class="prc-field">
					<label class="prc-field__label" for="prc-water-ml">
						<?php esc_html_e( 'Bacteriostatic Water', 'peptide-reconstitution-calculator' ); ?>
					</label>
					<div class="prc-field__row">
						<input type="number" id="prc-water-ml" class="prc-field__input"
							   step="0.1" min="0.1" placeholder="2.0" />
						<span class="prc-field__unit"><?php esc_html_e( 'mL', 'peptide-reconstitution-calculator' ); ?></span>
					</div>
				</div>

				<!-- Desired dose -->
				<div class="prc-field">
					<label class="prc-field__label" for="prc-desired-dose">
						<?php esc_html_e( 'Desired Dose', 'peptide-reconstitution-calculator' ); ?>
					</label>
					<div class="prc-field__row">
						<input type="number" id="prc-desired-dose" class="prc-field__input"
							   step="any" min="0" placeholder="250" />
						<select id="prc-dose-unit" class="prc-field__input prc-field__input--unit">
							<option value="mcg">mcg</option>
							<option value="mg">mg</option>
						</select>
					</div>
					<div id="prc-dose-range" class="prc-field__hint"></div>
				</div>

				<!-- Frequency (informational) -->
				<div id="prc-frequency-row" class="prc-field prc-field--hidden">
					<label class="prc-field__label">
						<?php esc_html_e( 'Typical Frequency', 'peptide-reconstitution-calculator' ); ?>
					</label>
					<span id="prc-frequency-value" class="prc-field__static"></span>
				</div>
			</div>

			<!-- Results panel -->
			<div id="prc-results" class="prc-results prc-results--hidden">
				<h4 class="prc-results__title">
					<?php esc_html_e( 'Results', 'peptide-reconstitution-calculator' ); ?>
				</h4>
				<div class="prc-results__grid">
					<div class="prc-result-card">
						<div class="prc-result-card__value" id="prc-result-concentration">—</div>
						<div class="prc-result-card__label"><?php esc_html_e( 'Concentration (mg/mL)', 'peptide-reconstitution-calculator' ); ?></div>
					</div>
					<div class="prc-result-card">
						<div class="prc-result-card__value" id="prc-result-mcg-per-unit">—</div>
						<div class="prc-result-card__label"><?php esc_html_e( 'mcg per Syringe Unit', 'peptide-reconstitution-calculator' ); ?></div>
					</div>
					<div class="prc-result-card prc-result-card--primary">
						<div class="prc-result-card__value" id="prc-result-syringe-units">—</div>
						<div class="prc-result-card__label"><?php esc_html_e( 'Syringe Units to Inject', 'peptide-reconstitution-calculator' ); ?></div>
					</div>
					<div class="prc-result-card">
						<div class="prc-result-card__value" id="prc-result-injection-ml">—</div>
						<div class="prc-result-card__label"><?php esc_html_e( 'Injection Volume (mL)', 'peptide-reconstitution-calculator' ); ?></div>
					</div>
					<div class="prc-result-card">
						<div class="prc-result-card__value" id="prc-result-doses">—</div>
						<div class="prc-result-card__label"><?php esc_html_e( 'Doses Per Vial', 'peptide-reconstitution-calculator' ); ?></div>
					</div>
				</div>
			</div>

			<!-- Disclaimer -->
			<div class="prc-disclaimer" id="prc-disclaimer"></div>

			<noscript>
				<p class="prc-noscript">
					<?php esc_html_e( 'This calculator requires JavaScript to function.', 'peptide-reconstitution-calculator' ); ?>
				</p>
			</noscript>
		</div>
		<?php
	}
}
