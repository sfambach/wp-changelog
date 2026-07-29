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
        . '.wp-block-changelog-table .wpc-changelog-col-date,'
        . '.wp-block-changelog-table .wpc-changelog-col-author{white-space:nowrap;width:1%;vertical-align:top;}'
        . '.wp-block-changelog-table .wpc-changelog-col-change{width:auto;vertical-align:top;word-wrap:break-word;overflow-wrap:anywhere;}'
        . '.wp-block-changelog-table table,'
        . '.wp-block-changelog-table .wpc-change-log-table,'
        . '.wp-block-changelog-table .wpc-change-log-table th,'
        . '.wp-block-changelog-table .wpc-change-log-table td{background-color:' . $sheet . ';}'
        . '.wp-block-changelog-table.is-style-stripes tbody tr:nth-child(odd),'
        . '.wp-block-changelog-table.is-style-stripes tbody tr:nth-child(odd) td,'
        . '.wpc-change-log-table.is-style-stripes tbody tr:nth-child(odd),'
        . '.wpc-change-log-table.is-style-stripes tbody tr:nth-child(odd) td{background-color:#f0f0f0;}'
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
        .wpc-global-change-log-preview {
            background: rgba(0, 124, 186, 0.12);
        }
        .wpc-change-log-preview .wp-block-changelog-table table,
        .wpc-change-log-preview .wpc-change-log-table,
        .wpc-change-log-preview .wpc-change-log-table th,
        .wpc-change-log-preview .wpc-change-log-table td {
            background-color: ' . $sheet . ';
        }
        .wpc-change-log-preview .wp-block-changelog-table.is-style-stripes tbody tr:nth-child(odd),
        .wpc-global-change-log-preview .wp-block-changelog-table.is-style-stripes tbody tr:nth-child(odd),
        .wpc-change-log-preview .wp-block-changelog-table.is-style-stripes tbody tr:nth-child(odd) td,
        .wpc-global-change-log-preview .wp-block-changelog-table.is-style-stripes tbody tr:nth-child(odd) td,
        .wpc-change-log-preview .wpc-change-log-table.is-style-stripes tbody tr:nth-child(odd),
        .wpc-global-change-log-preview .wpc-change-log-table.is-style-stripes tbody tr:nth-child(odd),
        .wpc-change-log-preview .wpc-change-log-table.is-style-stripes tbody tr:nth-child(odd) td,
        .wpc-global-change-log-preview .wpc-change-log-table.is-style-stripes tbody tr:nth-child(odd) td {
            background-color: #f0f0f0;
        }
        .wpc-minimal-input .components-base-control__field { margin-bottom: 0 !important; }
        .wpc-multi-note-table { width: 100%; table-layout: fixed; border-collapse: collapse; margin-bottom: 8px; font-size: 12px; }
        .wpc-multi-note-table th { text-align: left; padding: 4px 6px; background: rgba(0, 124, 186, 0.18); font-weight: 600; }
        .wpc-multi-note-table td { padding: 4px 6px; vertical-align: middle; border-top: 1px solid rgba(0, 124, 186, 0.2); }
        .wpc-multi-note-table .wpc-changelog-col-date { width: 7rem; white-space: nowrap; }
        .wpc-multi-note-table .wpc-changelog-col-author { width: 9rem; white-space: nowrap; }
        .wpc-multi-note-table .wpc-changelog-col-change { width: auto; }
        .wpc-multi-note-table .wpc-changelog-col-actions { width: 4.5rem; text-align: center; white-space: nowrap; }
        .wpc-multi-note-table .components-base-control,
        .wpc-multi-note-table .components-text-control__input { width: 100%; min-width: 0; }
        .wpc-block-surface { width: 100%; box-sizing: border-box; }
        .wpc-block-surface .wpc-changelog-col-date { width: 7rem; flex: 0 0 7rem; }
        .wpc-block-surface .wpc-changelog-col-author { width: 9rem; flex: 0 0 9rem; }
        .wpc-block-surface .wpc-changelog-col-change { flex: 1 1 auto; min-width: 0; }
        .wpc-multi-note-wrap { padding: 8px; border-radius: 2px; border-left: 3px solid #007cba; margin-bottom: 8px; }
        .wpc-multi-note-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
        .wpc-change-log-preview,
        .wpc-global-change-log-preview { border: 1px dashed rgba(0, 124, 186, 0.35); padding: 10px; border-radius: 2px; margin-top: 24px; }
        .wpc-change-log-preview > .wp-block-table,
        .wpc-change-log-preview > figure.wp-block-table { margin: 0; }
        .wpc-global-change-log-preview .wp-block-changelog-table table,
        .wpc-global-change-log-preview .wpc-change-log-table,
        .wpc-global-change-log-preview .wpc-change-log-table th,
        .wpc-global-change-log-preview .wpc-change-log-table td {
            background-color: ' . $sheet . ';
        }
        .wpc-editor-block-label { display: block; font-size: 11px; font-weight: 600; color: #1e1e1e; margin-bottom: 6px; }
        .wpc-block-surface-wrap { margin-bottom: 4px; }
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

    foreach ( [ 'wpc-single-change-note', 'wpc-multi-change-note', 'wpc-change-log' ] as $handle ) {
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

