<?php
/**
 * SPL autoloader for PRC_-prefixed classes.
 *
 * What: Maps PRC_ class names to kebab-case filenames under includes/.
 * Who calls it: Required once in the bootstrap file (peptide-reconstitution-calculator.php).
 * Dependencies: None.
 *
 * @see class-pr-core-autoloader.php in Peptide Repo Core — same pattern.
 */

declare(strict_types=1);

/**
 * Autoloads PRC_ classes from includes/ and its immediate subdirectories.
 */
class PRC_Autoloader {

	/**
	 * Register the autoloader with SPL.
	 *
	 * @return void
	 */
	public static function register(): void {
		spl_autoload_register( [ __CLASS__, 'autoload' ] );
	}

	/**
	 * Autoload callback — resolve PRC_ class name to file path.
	 *
	 * Strips PRC_ prefix, converts underscores to hyphens, lowercases,
	 * prepends "class-prc-", and searches includes/ + subdirs.
	 *
	 * @param string $class_name Fully qualified class name.
	 * @return void
	 */
	public static function autoload( string $class_name ): void {
		if ( 0 !== strpos( $class_name, 'PRC_' ) ) {
			return;
		}

		$relative = substr( $class_name, 4 ); // Strip "PRC_".
		$filename = 'class-prc-' . str_replace( '_', '-', strtolower( $relative ) ) . '.php';

		$base = PRC_PLUGIN_DIR . 'includes/';

		// Check includes/ root first.
		if ( file_exists( $base . $filename ) ) {
			require_once $base . $filename;
			return;
		}

		// Check immediate subdirectories.
		$dirs = glob( $base . '*', GLOB_ONLYDIR );
		if ( ! is_array( $dirs ) ) {
			return;
		}

		foreach ( $dirs as $dir ) {
			$path = $dir . '/' . $filename;
			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
}
