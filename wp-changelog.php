<?php
/**
 * Plugin Name: Gutenberg Changelog & Version History
 * Description: Adds change notes and lists them in a flexible, interactive table.
 * Version: 1.6.0
 * Author: Stefan Fambach
 * Text Domain: wp-changelog
 * Domain Path: /languages
 *
 * @package WP_Changelog
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/collectors.php';
require_once __DIR__ . '/includes/render-change-log.php';
require_once __DIR__ . '/includes/render-revision-multiline-note.php';
require_once __DIR__ . '/includes/blocks.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/global-change-log.php';

/**
 * Load plugin translations from the languages directory.
 *
 * @return void
 */
function wpc_load_textdomain() {
    load_plugin_textdomain( 'wp-changelog', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'wpc_load_textdomain' );
