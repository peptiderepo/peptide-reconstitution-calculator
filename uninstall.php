<?php
/**
 * Uninstall handler — cleans up all plugin data.
 *
 * What: Removes all options and transients created by this plugin.
 * Who calls it: WordPress on plugin deletion.
 * Dependencies: None (standalone, no autoloader).
 *
 * @see ARCHITECTURE.md — Teardown section.
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete all prc_ prefixed options.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( 'prc_' ) . '%'
	)
);

// Delete all prc_ prefixed transients.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_prc_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_prc_' ) . '%'
	)
);
