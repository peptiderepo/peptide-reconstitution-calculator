<?php
/**
 * Activation handler.
 *
 * What: Runs on plugin activation — flushes rewrite rules.
 * Who calls it: register_activation_hook in bootstrap.
 * Dependencies: None.
 */

declare(strict_types=1);

/**
 * Handles plugin activation tasks.
 */
class PRC_Activator {

	/**
	 * Run activation tasks.
	 *
	 * Flushes rewrite rules so any new pages using the shortcode
	 * are immediately accessible.
	 *
	 * @return void
	 */
	public static function activate(): void {
		flush_rewrite_rules();
	}
}
