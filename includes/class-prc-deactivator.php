<?php
/**
 * Deactivation handler.
 *
 * What: Runs on plugin deactivation — flushes rewrite rules.
 * Who calls it: register_deactivation_hook in bootstrap.
 * Dependencies: None.
 */

declare(strict_types=1);

/**
 * Handles plugin deactivation tasks.
 */
class PRC_Deactivator {

	/**
	 * Run deactivation tasks.
	 *
	 * Flushes rewrite rules to remove any routes this plugin registered.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
