<?php
/**
 * Plugin Name: Peptide Reconstitution Calculator
 * Plugin URI:  https://peptiderepo.com/tools/reconstitution-calculator
 * Description: Interactive reconstitution calculator with peptide-specific presets from Peptide Repo Core. Computes concentration, injection volume, syringe units, and doses per vial.
 * Version:     1.0.0
 * Author:      peptiderepo
 * Author URI:  https://peptiderepo.com
 * License:     GPL-2.0-or-later
 * Text Domain: peptide-reconstitution-calculator
 * Requires PHP: 8.1
 *
 * @see ARCHITECTURE.md — Full data flow and file tree.
 * @see CONVENTIONS.md  — Naming patterns and extension guides.
 *
 * Depends on: Peptide Repo Core (optional — provides peptide-specific presets).
 * Without PR Core, the calculator still works with manual entry and built-in defaults.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ── Constants ────────────────────────────────────────────────────────── */

define( 'PRC_VERSION', '1.0.0' );
define( 'PRC_PLUGIN_FILE', __FILE__ );
define( 'PRC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PRC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/* ── Autoloader ───────────────────────────────────────────────────────── */

require_once PRC_PLUGIN_DIR . 'includes/class-prc-autoloader.php';
PRC_Autoloader::register();

/* ── Activation / Deactivation ────────────────────────────────────────── */

register_activation_hook( __FILE__, [ 'PRC_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'PRC_Deactivator', 'deactivate' ] );

/* ── Boot ─────────────────────────────────────────────────────────────── */

add_action( 'plugins_loaded', [ 'PRC_Calculator', 'boot' ] );
