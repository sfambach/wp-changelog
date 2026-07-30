<?php
/**
 * Append a globally configured Change Log table to supported post content.
 *
 * @package WPChangelog
 */

namespace WPChangelog;

use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Global Change Log frontend append and editor REST preview.
 */
class GlobalChangeLog {

	/**
	 * Renderer instance.
	 *
	 * @var Renderer
	 */
	private $renderer;

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Renderer $renderer Change Log renderer.
	 * @param Settings $settings Settings module.
	 */
	public function __construct( Renderer $renderer, Settings $settings ) {
		$this->renderer = $renderer;
		$this->settings = $settings;
	}

	/**
	 * Register global Change Log hooks.
	 *
	 * @return void
	 */
	public function register() {
		/** @see https://developer.wordpress.org/reference/hooks/the_content/ */
		add_filter( 'the_content', [ $this, 'append_to_content' ], 99 );

		/** @see https://developer.wordpress.org/reference/hooks/rest_api_init/ */
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Build block-style attributes from global plugin settings.
	 *
	 * @param array $settings Global settings array.
	 * @return array
	 */
	public function settings_to_block_attributes( array $settings ) {
		$attributes = Helpers::default_change_log_display_attributes();

		foreach ( array_keys( $attributes ) as $key ) {
			if ( array_key_exists( $key, $settings ) ) {
				$attributes[ $key ] = $settings[ $key ];
			}
		}

		return $attributes;
	}

	/**
	 * Whether the global Change Log should apply to a post.
	 *
	 * @param WP_Post     $post             Post object.
	 * @param string|null $post_content     Optional live editor content instead of saved content.
	 * @param bool        $allow_auto_draft Whether unsaved auto-draft posts qualify.
	 * @return bool
	 */
	public function is_applicable( WP_Post $post, $post_content = null, $allow_auto_draft = false ) {
		$settings = $this->settings->get_settings();

		if ( empty( $settings['enabled'] ) ) {
			return false;
		}

		if ( ! $allow_auto_draft && $post->post_status === 'auto-draft' ) {
			return false;
		}

		$post_types = isset( $settings['post_types'] ) && is_array( $settings['post_types'] )
			? $settings['post_types']
			: [ 'page' ];

		if ( ! in_array( $post->post_type, $post_types, true ) ) {
			return false;
		}

		$content = null !== $post_content ? (string) $post_content : (string) $post->post_content;

		return ! Helpers::post_has_change_log_block( $content );
	}

	/**
	 * Render the global Change Log table HTML for a post.
	 *
	 * @param WP_Post $post    Post object.
	 * @param string  $context Request context: frontend or editor.
	 * @return string
	 */
	public function render_for_post( WP_Post $post, $context = 'frontend' ) {
		if ( ! $this->is_applicable( $post ) ) {
			return '';
		}

		$settings   = $this->settings->get_settings();
		$attributes = $this->settings_to_block_attributes( $settings );

		if ( $context === 'frontend' && ! Helpers::is_change_log_visible_on_page( $attributes ) ) {
			return '';
		}

		return $this->renderer->render_change_log_for_post( $post, $attributes );
	}

	/**
	 * Append the global Change Log table to eligible singular content views.
	 *
	 * @param string $content Post content HTML.
	 * @return string
	 */
	public function append_to_content( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post = get_post();

		if ( ! $post instanceof WP_Post ) {
			return $content;
		}

		$table_html = $this->render_for_post( $post, 'frontend' );

		if ( $table_html === '' ) {
			return $content;
		}

		return $content . '<div class="wpc-change-log-global-wrap">' . $table_html . '</div>';
	}

	/**
	 * REST callback: render the global Change Log preview for the block editor.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_preview( WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'post_id' );

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'wpc_forbidden', __( 'You cannot edit this post.', 'wp-changelog' ), [ 'status' => 403 ] );
		}

		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return rest_ensure_response(
				[
					'eligible'         => false,
					'html'             => '',
					'hiddenOnFrontend' => false,
				]
			);
		}

		$post_content = $request->get_param( 'post_content' );
		$post_content = is_string( $post_content ) ? $post_content : null;

		if ( ! $this->is_applicable( $post, $post_content, true ) ) {
			return rest_ensure_response(
				[
					'eligible'         => false,
					'html'             => '',
					'hiddenOnFrontend' => false,
				]
			);
		}

		$settings   = $this->settings->get_settings();
		$attributes = $this->settings_to_block_attributes( $settings );

		return rest_ensure_response(
			[
				'eligible'         => true,
				'html'             => $this->renderer->render_change_log_for_post( $post, $attributes ),
				'hiddenOnFrontend' => empty( $attributes['visibleOnPage'] ),
			]
		);
	}

	/**
	 * Register REST routes for global Change Log editor preview.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'wp-changelog/v1',
			'/global-change-log-preview',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest_preview' ],
				'permission_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => [
					'post_id'      => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'post_content' => [
						'required' => false,
						'type'     => 'string',
					],
				],
			]
		);
	}
}
