<?php
/**
 * Cache invalidation listener — keeps presets in sync with PR Core.
 *
 * What: Listens for PR Core dosing row lifecycle hooks and clears
 *       the preset cache so the calculator picks up fresh data.
 * Who calls it: PRC_Calculator::boot() registers hooks on init.
 * Dependencies: PRC_Preset_Provider (cache invalidation method).
 *
 * @see class-prc-preset-provider.php — Cache being invalidated.
 * @see PR_Core_Dosing_Repository     — Fires the hooks we listen to.
 */

declare(strict_types=1);

/**
 * Hooks into PR Core actions to invalidate the presets cache.
 */
class PRC_Cache_Listener {

	/**
	 * Register PR Core hooks for cache invalidation.
	 *
	 * Safe to call even when PR Core is not active — the hooks
	 * simply never fire.
	 *
	 * @return void Side effect: registers action hooks.
	 */
	public static function register(): void {
		$callback = [ __CLASS__, 'invalidate' ];

		// Dosing row lifecycle — affects calculator presets.
		add_action( 'pr_core_after_dosing_row_publish', $callback );

		// Peptide lifecycle — new peptides mean new preset candidates.
		add_action( 'pr_core_after_peptide_publish', $callback );
	}

	/**
	 * Invalidate the preset cache.
	 *
	 * @return void Side effect: deletes transient.
	 */
	public static function invalidate(): void {
		( new PRC_Preset_Provider() )->invalidate_cache();
	}
}
