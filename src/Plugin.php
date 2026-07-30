<?php
/**
 * Plugin bootstrap singleton.
 *
 * @package WPChangelog
 */

namespace WPChangelog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Boots modules and wires WordPress hooks.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Absolute path to the main plugin file.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Absolute path to the plugin root directory.
	 *
	 * @var string
	 */
	private $plugin_dir;

	/**
	 * Block registry module.
	 *
	 * @var BlockRegistry
	 */
	private $block_registry;

	/**
	 * Collectors module.
	 *
	 * @var Collectors
	 */
	private $collectors;

	/**
	 * Renderer module.
	 *
	 * @var Renderer
	 */
	private $renderer;

	/**
	 * Settings module.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Global Change Log module.
	 *
	 * @var GlobalChangeLog
	 */
	private $global_change_log;

	/**
	 * Get the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->plugin_file = dirname( __DIR__ ) . '/wp-changelog.php';
		$this->plugin_dir  = dirname( __DIR__ );
	}

	/**
	 * Initialize plugin modules and hooks.
	 *
	 * @return void
	 */
	public function init() {
		$this->renderer          = new Renderer();
		$this->collectors        = new Collectors();
		$this->settings          = new Settings();
		$this->block_registry    = new BlockRegistry( $this->renderer );
		$this->global_change_log = new GlobalChangeLog( $this->renderer, $this->settings );

		/** @see https://developer.wordpress.org/reference/hooks/plugins_loaded/ */
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );

		$this->collectors->register();
		$this->block_registry->register();
		$this->settings->register();
		$this->global_change_log->register();
	}

	/**
	 * Load plugin translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'wp-changelog',
			false,
			dirname( plugin_basename( $this->plugin_file ) ) . '/languages'
		);
	}

	/**
	 * Absolute path to the main plugin file.
	 *
	 * @return string
	 */
	public function plugin_file() {
		return $this->plugin_file;
	}

	/**
	 * Absolute path to the plugin root directory.
	 *
	 * @return string
	 */
	public function plugin_dir() {
		return $this->plugin_dir;
	}

	/**
	 * Renderer instance.
	 *
	 * @return Renderer
	 */
	public function renderer() {
		return $this->renderer;
	}

	/**
	 * Collectors instance.
	 *
	 * @return Collectors
	 */
	public function collectors() {
		return $this->collectors;
	}

	/**
	 * Settings instance.
	 *
	 * @return Settings
	 */
	public function settings() {
		return $this->settings;
	}
}
