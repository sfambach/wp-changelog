<?php
/**
 * Block registration, editor styles, and script enqueueing.
 *
 * @package WP_Changelog
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CSS value for matching the editor/theme document background.
 *
 * @return string CSS background value with fallbacks.
 */
function wpc_sheet_background_css() {
    return 'var(--wp--preset--color--base, var(--wp--style--global--background-color, #fff))';
}

/**
 * Inline CSS that keeps the Change Log table on the document background.
 *
 * @return string CSS rules.
 */
function wpc_change_log_table_background_css() {
    $sheet = wpc_sheet_background_css();

    return '.wp-block-changelog-table{display:block;width:100%;max-width:100%;clear:both;margin:1.5em 0;overflow-x:auto;}'
        . '.wp-block-changelog-table table{width:100%;border-collapse:collapse;}'
        . '.wp-block-changelog-table table,'
        . '.wp-block-changelog-table .wpc-change-log-table,'
        . '.wp-block-changelog-table .wpc-change-log-table th,'
        . '.wp-block-changelog-table .wpc-change-log-table td{background-color:' . $sheet . ';}'
        . '.wp-block-changelog-table.is-style-stripes tbody tr:nth-child(odd){background-color:rgba(0,0,0,0.03);}'
        . '.wp-block-changelog-table.is-style-stripes tbody tr:nth-child(odd) td{background-color:rgba(0,0,0,0.03);}'
        . '.wpc-change-log-global-wrap{display:block;width:100%;clear:both;}';
}

/**
 * Output editor-only styles for note blocks and the Change Log preview.
 *
 * @return void
 */
function wpc_editor_styles() {
    $sheet = wpc_sheet_background_css();

    echo '<style>
        .wpc-block-surface,
        .wpc-multi-note-wrap,
        .wpc-change-log-preview,
        .wpc-global-change-log-preview,
        .wpc-revision-multiline-note {
            background: rgba(0, 124, 186, 0.12);
        }
        .wpc-change-log-preview .wp-block-changelog-table table,
        .wpc-change-log-preview .wpc-change-log-table,
        .wpc-change-log-preview .wpc-change-log-table th,
        .wpc-change-log-preview .wpc-change-log-table td {
            background-color: ' . $sheet . ';
        }
        .wpc-change-log-preview .wp-block-changelog-table.is-style-stripes tbody tr:nth-child(odd),
        .wpc-global-change-log-preview .wp-block-changelog-table.is-style-stripes tbody tr:nth-child(odd) {
            background-color: rgba(0, 0, 0, 0.03);
        }
        .wpc-change-log-preview .wp-block-changelog-table.is-style-stripes tbody tr:nth-child(odd) td,
        .wpc-global-change-log-preview .wp-block-changelog-table.is-style-stripes tbody tr:nth-child(odd) td {
            background-color: rgba(0, 0, 0, 0.03);
        }
        .wpc-minimal-input .components-base-control__field { margin-bottom: 0 !important; }
        .wpc-multi-note-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 12px; }
        .wpc-multi-note-table th { text-align: left; padding: 4px 6px; background: rgba(0, 124, 186, 0.18); font-weight: 600; }
        .wpc-multi-note-table td { padding: 4px 6px; vertical-align: middle; border-top: 1px solid rgba(0, 124, 186, 0.2); }
        .wpc-multi-note-wrap { padding: 8px; border-radius: 2px; border-left: 3px solid #007cba; margin-bottom: 8px; }
        .wpc-change-log-preview,
        .wpc-global-change-log-preview { border: 1px dashed rgba(0, 124, 186, 0.35); padding: 10px; border-radius: 2px; margin-top: 24px; }
        .wpc-global-change-log-preview .wp-block-changelog-table table,
        .wpc-global-change-log-preview .wpc-change-log-table,
        .wpc-global-change-log-preview .wpc-change-log-table th,
        .wpc-global-change-log-preview .wpc-change-log-table td {
            background-color: ' . $sheet . ';
        }
        .wpc-revision-multiline-note-notice { margin: 0; font-size: 12px; color: #666; }
        .wpc-revision-multiline-note.wpc-multi-note-wrap { margin-bottom: 0; }
        .wpc-revision-multiline-note .wpc-multi-note-table td { opacity: 1; }
    </style>';
}
add_action( 'admin_head', 'wpc_editor_styles' );

/**
 * Enqueue frontend and editor styles for the Change Log table.
 *
 * @return void
 */
function wpc_enqueue_block_styles() {
    wp_register_style( 'wpc-change-log', false );
    wp_enqueue_style( 'wpc-change-log' );
    wp_add_inline_style( 'wpc-change-log', wpc_change_log_table_background_css() );
}
add_action( 'enqueue_block_assets', 'wpc_enqueue_block_styles' );

/**
 * Register editor scripts before blocks reference them.
 *
 * @return void
 */
function wpc_register_editor_scripts() {
    $asset_path = wpc_plugin_path( 'assets/js/' );
    $base_deps  = [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-data', 'wp-block-editor', 'wp-i18n' ];
    $ssr_deps   = [ 'wp-server-side-render' ];

    wp_register_script(
        'wpc-shared',
        wpc_plugin_url( 'assets/js/shared.js' ),
        [],
        filemtime( $asset_path . 'shared.js' ),
        true
    );

    wp_register_script(
        'wpc-single-change-note',
        wpc_plugin_url( 'assets/js/single-change-note.js' ),
        array_merge( [ 'wpc-shared' ], $base_deps ),
        filemtime( $asset_path . 'single-change-note.js' ),
        true
    );

    wp_register_script(
        'wpc-multi-change-note',
        wpc_plugin_url( 'assets/js/multi-change-note.js' ),
        array_merge( [ 'wpc-shared' ], $base_deps ),
        filemtime( $asset_path . 'multi-change-note.js' ),
        true
    );

    wp_register_script(
        'wpc-change-log',
        wpc_plugin_url( 'assets/js/change-log.js' ),
        array_merge( [ 'wpc-shared' ], $base_deps, $ssr_deps ),
        filemtime( $asset_path . 'change-log.js' ),
        true
    );

    wp_register_script(
        'wpc-revision-multiline-note',
        wpc_plugin_url( 'assets/js/revision-multiline-note.js' ),
        array_merge( [ 'wpc-shared' ], $base_deps, [ 'wp-api-fetch' ] ),
        filemtime( $asset_path . 'revision-multiline-note.js' ),
        true
    );

    wp_register_script(
        'wpc-global-change-log-editor',
        wpc_plugin_url( 'assets/js/global-change-log-editor.js' ),
        [ 'wp-hooks', 'wp-element', 'wp-data', 'wp-api-fetch', 'wp-i18n' ],
        filemtime( $asset_path . 'global-change-log-editor.js' ),
        true
    );
}
add_action( 'init', 'wpc_register_editor_scripts', 5 );

/**
 * Thin wrapper around register_block_type().
 *
 * @param string $name Block name.
 * @param array  $args Block registration arguments.
 * @return void
 */
function wpc_register_editor_block( $name, $args ) {
    register_block_type( $name, $args );
}

/**
 * Register current and legacy block types plus table block styles.
 *
 * @return void
 */
function wpc_register_changelog_blocks() {
    wpc_register_editor_scripts();

    $change_log_args = [
        'editor_script'   => 'wpc-change-log',
        'render_callback' => 'wpc_render_change_log',
        'attributes'      => wpc_change_log_block_attributes(),
    ];

    wpc_register_editor_block(
        WPC_BLOCK_SINGLE_CHANGE_NOTE,
        [
            'editor_script' => 'wpc-single-change-note',
            'attributes'    => wpc_note_block_attributes(),
        ]
    );

    wpc_register_editor_block(
        WPC_LEGACY_BLOCK_SINGLE_CHANGE_NOTE,
        [
            'editor_script' => 'wpc-single-change-note',
            'attributes'    => wpc_note_block_attributes(),
        ]
    );

    wpc_register_editor_block(
        WPC_BLOCK_MULTI_CHANGE_NOTE,
        [
            'editor_script' => 'wpc-multi-change-note',
            'attributes'    => wpc_multi_note_block_attributes(),
        ]
    );

    wpc_register_editor_block(
        WPC_LEGACY_BLOCK_MULTI_CHANGE_NOTE,
        [
            'editor_script' => 'wpc-multi-change-note',
            'attributes'    => wpc_multi_note_block_attributes(),
        ]
    );

    wpc_register_editor_block( WPC_BLOCK_CHANGE_LOG, $change_log_args );
    wpc_register_editor_block( WPC_LEGACY_BLOCK_CHANGE_LOG, $change_log_args );

    $revision_multiline_note_args = [
        'editor_script'   => 'wpc-revision-multiline-note',
        'render_callback' => 'wpc_render_revision_multiline_note',
        'attributes'      => wpc_revision_multiline_note_block_attributes(),
    ];

    wpc_register_editor_block( WPC_BLOCK_REVISION_MULTILINE_NOTE, $revision_multiline_note_args );
    wpc_register_editor_block( WPC_LEGACY_BLOCK_VERSION_MULTILINE_NOTE, $revision_multiline_note_args );
    wpc_register_editor_block( WPC_LEGACY_BLOCK_GENERATED_MULTILINE_NOTE, $revision_multiline_note_args );

    if ( function_exists( 'register_block_style' ) ) {
        foreach ( [ WPC_BLOCK_CHANGE_LOG, WPC_LEGACY_BLOCK_CHANGE_LOG ] as $block_name ) {
            register_block_style( $block_name, [ 'name' => 'default', 'label' => __( 'Default', 'wp-changelog' ), 'is_default' => true ] );
            register_block_style( $block_name, [ 'name' => 'stripes', 'label' => __( 'Stripes', 'wp-changelog' ) ] );
        }
    }
}
add_action( 'init', 'wpc_register_changelog_blocks' );

/**
 * Attach script translations in the block editor.
 *
 * @return void
 */
function wpc_enqueue_block_editor_assets() {
    $languages = wpc_plugin_path( 'languages' );

    foreach ( [ 'wpc-single-change-note', 'wpc-multi-change-note', 'wpc-change-log', 'wpc-revision-multiline-note' ] as $handle ) {
        if ( wp_script_is( $handle, 'registered' ) ) {
            wp_set_script_translations( $handle, 'wp-changelog', $languages );
        }
    }

    if ( wp_script_is( 'wpc-global-change-log-editor', 'registered' ) ) {
        wp_enqueue_script( 'wpc-global-change-log-editor' );
        wp_set_script_translations( 'wpc-global-change-log-editor', 'wp-changelog', $languages );
    }
}
add_action( 'enqueue_block_editor_assets', 'wpc_enqueue_block_editor_assets' );

/**
 * REST callback: merge revision dates with stored rows for the editor.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response|WP_Error
 */
function wpc_rest_sync_revision_rows( WP_REST_Request $request ) {
    $post_id = (int) $request->get_param( 'post_id' );

    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
        return new WP_Error( 'wpc_forbidden', __( 'You cannot edit this post.', 'wp-changelog' ), [ 'status' => 403 ] );
    }

    $attributes = [
        'rows'                  => $request->get_param( 'rows' ),
        'includeCurrentVersion' => $request->get_param( 'includeCurrentVersion' ),
    ];

    if ( ! is_array( $attributes['rows'] ) ) {
        $attributes['rows'] = [];
    }

    if ( null === $attributes['includeCurrentVersion'] ) {
        $attributes['includeCurrentVersion'] = true;
    }

    return rest_ensure_response(
        wpc_sync_revision_note_rows_for_post( $post_id, $attributes )
    );
}

/**
 * Register REST routes used by the block editor.
 *
 * @return void
 */
function wpc_register_rest_routes() {
    register_rest_route(
        'wp-changelog/v1',
        '/sync-revision-rows',
        [
            'methods'             => 'POST',
            'callback'            => 'wpc_rest_sync_revision_rows',
            'permission_callback' => function () {
                return current_user_can( 'edit_posts' );
            },
            'args'                => [
                'post_id'               => [
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ],
                'rows'                  => [
                    'required' => false,
                    'type'     => 'array',
                ],
                'includeCurrentVersion' => [
                    'required' => false,
                    'type'     => 'boolean',
                ],
            ],
        ]
    );
}
add_action( 'rest_api_init', 'wpc_register_rest_routes' );
