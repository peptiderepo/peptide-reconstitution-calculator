<?php
/**
 * Main orchestrator for the Reconstitution Calculator plugin.
 *
 * What: Boots all subsystems — shortcode, REST API, admin settings.
 * Who calls it: plugins_loaded hook in bootstrap.
 * Dependencies: PRC_Shortcode, PRC_Rest_Controller, PRC_Preset_Provider.
 *
 * @see class-prc-shortcode.php    — Frontend calculator widget.
 * @see class-prc-rest-controller.php — REST endpoints for presets.
 * @see class-prc-preset-provider.php — PR Core integration + fallback defaults.
 */

declare(strict_types=1);

/**
 * Central boot class — wires hooks for the entire plugin.
 */
class PRC_Calculator {

	/**
	 * Boot the plugin — called on plugins_loaded.
	 *
	 * Registers the shortcode, REST routes, and admin notice
	 * if PR Core is not active.
	 *
	 * @return void
	 */
	public static function boot(): void {
		// Frontend shortcode — always available.
		$shortcode = new PRC_Shortcode();
		add_action( 'init', [ $shortcode, 'register' ] );

		// REST API — serves presets to the JS calculator.
		add_action( 'rest_api_init', [ new PRC_Rest_Controller(), 'register_routes' ] );

		// Cache invalidation — keep presets in sync with PR Core dosing data.
		PRC_Cache_Listener::register();

		// Admin notice when PR Core is absent.
		if ( is_admin() && ! self::is_pr_core_active() ) {
			add_action( 'admin_notices', [ __CLASS__, 'render_pr_core_notice' ] );
		}
	}

	/**
	 * Check whether Peptide Repo Core is active.
	 *
	 * Looks for the PR_CORE_VERSION constant which is defined
	 * in peptide-repo-core.php's bootstrap.
	 *
	 * @return bool True if PR Core is loaded.
	 */
	public static function is_pr_core_active(): bool {
		return defined( 'PR_CORE_VERSION' );
	}

	/**
	 * Render an admin notice suggesting PR Core installation.
	 *
	 * Not a blocker — the calculator works standalone with built-in
	 * defaults. PR Core just unlocks peptide-specific presets.
	 *
	 * @return void Side effect: outputs HTML notice.
	 */
	public static function render_pr_core_notice(): void {
		$screen = get_current_screen();
		if ( null === $screen ) {
			return;
		}

		// Only show on plugins page and the calculator's own pages.
		$show_on = [ 'plugins', 'settings_page_prc-settings' ];
		if ( ! in_array( $screen->id, $show_on, true ) ) {
			return;
		}

		printf(
			'<div class="notice notice-info is-dismissible"><p>%s</p></div>',
			esc_html__(
				'Peptide Reconstitution Calculator: Install Peptide Repo Core to unlock peptide-specific presets with real dosing data. The calculator works without it using built-in defaults.',
				'peptide-reconstitution-calculator'
			)
		);
	}
}
