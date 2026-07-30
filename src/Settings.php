<?php
/**
 * Admin settings page for global Change Log integration.
 *
 * @package WPChangelog
 */

namespace WPChangelog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders plugin settings.
 */
class Settings {

	/** @var string Option name for global Change Log settings. */
	public const OPTION_NAME = 'wpc_global_change_log_settings';

	/**
	 * Register settings hooks.
	 *
	 * @return void
	 */
	public function register() {
		/** @see https://developer.wordpress.org/reference/hooks/admin_menu/ */
		add_action( 'admin_menu', [ $this, 'register_settings_page' ] );

		/** @see https://developer.wordpress.org/reference/hooks/admin_init/ */
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Register the plugin settings page.
	 *
	 * @return void
	 */
	public function register_settings_page() {
		add_options_page(
			__( 'Change Log Settings', 'wp-changelog' ),
			__( 'Change Log', 'wp-changelog' ),
			'manage_options',
			'wp-changelog',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Register global settings and their sanitization callback.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'wpc_global_change_log',
			self::OPTION_NAME,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize' ],
				'default'           => $this->get_defaults(),
			]
		);
	}

	/**
	 * Default global Change Log integration settings.
	 *
	 * @return array
	 */
	public function get_defaults() {
		return array_merge(
			[
				'enabled'          => false,
				'post_types'       => [ 'page' ],
				'shortcutEnabled'  => true,
				'shortcutPrefix'   => '#log',
			],
			Helpers::default_change_log_display_attributes()
		);
	}

	/**
	 * Load saved global Change Log settings merged with defaults.
	 *
	 * @return array
	 */
	public function get_settings() {
		$saved = get_option( self::OPTION_NAME, [] );

		return wp_parse_args(
			is_array( $saved ) ? $saved : [],
			$this->get_defaults()
		);
	}

	/**
	 * Sanitize settings submitted from the admin page.
	 *
	 * @param mixed $input Raw submitted settings.
	 * @return array
	 */
	public function sanitize( $input ) {
		$defaults = $this->get_defaults();
		$input    = is_array( $input ) ? $input : [];
		$clean    = $defaults;

		$clean['enabled'] = ! empty( $input['enabled'] );

		$clean['shortcutEnabled'] = ! empty( $input['shortcutEnabled'] );

		$prefix = isset( $input['shortcutPrefix'] ) ? sanitize_text_field( wp_unslash( $input['shortcutPrefix'] ) ) : '';
		$prefix = trim( $prefix );
		if ( '' === $prefix || strlen( $prefix ) > 20 ) {
			$prefix = $defaults['shortcutPrefix'];
		}
		$clean['shortcutPrefix'] = $prefix;

		$allowed_post_types  = array_keys( $this->get_selectable_post_types() );
		$submitted_types     = isset( $input['post_types'] ) && is_array( $input['post_types'] ) ? $input['post_types'] : [];
		$clean['post_types'] = array_values(
			array_intersect(
				array_map( 'sanitize_key', $submitted_types ),
				$allowed_post_types
			)
		);

		if ( empty( $clean['post_types'] ) ) {
			$clean['post_types'] = [ 'page' ];
		}

		$clean['showAuthor']       = ! empty( $input['showAuthor'] );
		$clean['hasFixedLayout']   = ! empty( $input['hasFixedLayout'] );
		$clean['consolidateDates'] = ! empty( $input['consolidateDates'] );
		$clean['listChanges']      = ! empty( $input['listChanges'] );
		$clean['visibleOnPage']    = ! empty( $input['visibleOnPage'] );

		$clean['tableStyle'] = in_array( $input['tableStyle'] ?? '', [ 'default', 'stripes' ], true )
			? $input['tableStyle']
			: $defaults['tableStyle'];

		$clean['sortOrder'] = in_array( $input['sortOrder'] ?? '', [ 'asc', 'desc' ], true )
			? $input['sortOrder']
			: $defaults['sortOrder'];

		$clean['changeFieldSort'] = in_array( $input['changeFieldSort'] ?? '', [ 'time', 'alpha' ], true )
			? $input['changeFieldSort']
			: $defaults['changeFieldSort'];

		$clean['changeFieldOrder'] = Helpers::normalize_merged_change_order( $input['changeFieldOrder'] ?? $defaults['changeFieldOrder'] );

		$allowed_align  = [ '', 'left', 'center', 'right', 'wide', 'full' ];
		$clean['align'] = in_array( $input['align'] ?? '', $allowed_align, true )
			? $input['align']
			: $defaults['align'];

		$clean['className'] = sanitize_text_field( $input['className'] ?? '' );

		return $clean;
	}

	/**
	 * Public post types that can receive the global Change Log.
	 *
	 * @return array<string, \WP_Post_Type>
	 */
	public function get_selectable_post_types() {
		$post_types = get_post_types(
			[
				'public'  => true,
				'show_ui' => true,
			],
			'objects'
		);

		unset( $post_types['attachment'] );

		return $post_types;
	}

	/**
	 * Render one admin settings field.
	 *
	 * @param string $label       Field label.
	 * @param string $field_html  Input markup.
	 * @param string $description Optional help text.
	 * @return void
	 */
	public function render_settings_field( $label, $field_html, $description = '' ) {
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
		echo $field_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in callers.
		if ( $description !== '' ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
		echo '</td></tr>';
	}

	/**
	 * Output the plugin settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo Helpers::render_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'settings-page',
			[
				'settings'   => $this->get_settings(),
				'option_key' => self::OPTION_NAME,
				'post_types' => $this->get_selectable_post_types(),
				'settings_ui' => $this,
			]
		);
	}
}
