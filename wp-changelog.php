<?php
/**
 * Plugin Name: Gutenberg Changelog & Version History
 * Plugin URI:  https://github.com/sfambach/wp-changelog
 * Description: Adds change notes and lists them in a flexible, interactive table.
 * Version: 2.0.0
 * Author: Stefan Fambach
 * Author URI: https://www.fambach.net
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: wp-changelog
 * Domain Path: /languages
 *
 * @package WPChangelog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PSR-4 autoloader for the WPChangelog namespace.
 *
 * @param string $class Fully-qualified class name.
 * @return void
 */
spl_autoload_register(
	static function ( $class ) {
		$prefix = 'WPChangelog\\';

		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$file     = __DIR__ . '/src/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

WPChangelog\Plugin::instance()->init();
