<?php
/**
 * Block registration, editor styles, and script enqueueing.
 *
 * @package WPChangelog
 */

namespace WPChangelog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers Gutenberg blocks and related assets.
 */
class BlockRegistry {

	/**
	 * Renderer used as block render callback.
	 *
	 * @var Renderer
	 */
	private $renderer;

	/**
	 * Constructor.
	 *
	 * @param Renderer $renderer Change Log renderer.
	 */
	public function __construct( Renderer $renderer ) {
		$this->renderer = $renderer;
	}

	/**
	 * Register block-related hooks.
	 *
	 * @return void
	 */
	public function register() {
		/** @see https://developer.wordpress.org/reference/hooks/admin_head/ */
		add_action( 'admin_head', [ $this, 'editor_styles' ] );

		/** @see https://developer.wordpress.org/reference/hooks/enqueue_block_assets/ */
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_styles' ] );

		/** @see https://developer.wordpress.org/reference/hooks/init/ */
		add_action( 'init', [ $this, 'register_editor_scripts' ], 5 );

		/** @see https://developer.wordpress.org/reference/hooks/init/ */
		add_action( 'init', [ $this, 'register_changelog_blocks' ] );

		/** @see https://developer.wordpress.org/reference/hooks/enqueue_block_editor_assets/ */
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
	}

	/**
	 * CSS value for matching the editor/theme document background.
	 *
	 * Prefers theme.json presets, then global style tokens, then transparent
	 * so classic themes can keep their own table backgrounds.
	 *
	 * @return string
	 */
	private function sheet_background_css() {
		return 'var(--wp--preset--color--base, var(--wp--style--global--background-color, var(--wp--style--color--background, transparent)))';
	}

	/**
	 * CSS value for striped table rows, derived from theme contrast/base.
	 *
	 * Uses color-mix so light and dark themes both get readable stripes.
	 *
	 * @return string
	 */
	private function stripe_background_css() {
		return 'color-mix(in srgb, var(--wp--preset--color--contrast, currentColor) 8%, var(--wp--preset--color--base, transparent))';
	}

	/**
	 * Inline CSS that keeps the Change Log table on the document background.
	 *
	 * @return string
	 */
	private function change_log_table_background_css() {
		$sheet  = $this->sheet_background_css();
		$stripe = $this->stripe_background_css();

		return '.wp-block-changelog-table{display:block;width:100%;max-width:100%;clear:both;margin:1.5em 0;overflow-x:auto;color:var(--wp--preset--color--contrast, inherit);}'
			. '.wp-block-changelog-table table{width:100%;border-collapse:collapse;}'
			. '.wp-block-changelog-table table.has-fixed-layout{table-layout:fixed;}'
			. '.wp-block-changelog-table .wpc-changelog-col-date{white-space:nowrap;vertical-align:top;text-align:center;box-sizing:border-box;width:7.5em;min-width:7.5em;}'
			. '.wp-block-changelog-table .wpc-changelog-col-author{white-space:nowrap;vertical-align:top;text-align:left;box-sizing:border-box;width:8em;min-width:7em;}'
			. '.wp-block-changelog-table .wpc-changelog-col-change{width:auto;vertical-align:top;word-wrap:break-word;overflow-wrap:anywhere;text-align:left;}'
			. '.wp-block-changelog-table table,'
			. '.wp-block-changelog-table .wpc-change-log-table,'
			. '.wp-block-changelog-table .wpc-change-log-table th,'
			. '.wp-block-changelog-table .wpc-change-log-table td{background-color:' . $sheet . ';}'
			. '.wp-block-changelog-table.is-style-stripes tbody tr:nth-child(odd),'
			. '.wp-block-changelog-table.is-style-stripes tbody tr:nth-child(odd) td,'
			. '.wpc-change-log-table.is-style-stripes tbody tr:nth-child(odd),'
			. '.wpc-change-log-table.is-style-stripes tbody tr:nth-child(odd) td{background-color:' . $stripe . ';}'
			. '.wpc-change-log-global-wrap{display:block;width:100%;clear:both;}'
			. '.wpc-change-log-global-wrap .wp-block-changelog-table{margin-top:0;}';
	}

	/**
	 * Output editor-only styles for note blocks and the Change Log preview.
	 *
	 * @return void
	 */
	public function editor_styles() {
		$sheet  = $this->sheet_background_css();
		$stripe = $this->stripe_background_css();

		echo '<style>
        .wpc-change-log-preview,
        .wpc-global-change-log-preview {
            background: rgba(0, 124, 186, 0.12);
        }
        .wpc-change-log-preview .wp-block-changelog-table table,
        .wpc-change-log-preview .wpc-change-log-table,
        .wpc-change-log-preview .wpc-change-log-table th,
        .wpc-change-log-preview .wpc-change-log-table td {
            background-color: ' . esc_attr( $sheet ) . ';
        }
        .wpc-change-log-preview .wp-block-changelog-table.is-style-stripes tbody tr:nth-child(odd),
        .wpc-global-change-log-preview .wp-block-changelog-table.is-style-stripes tbody tr:nth-child(odd),
        .wpc-change-log-preview .wp-block-changelog-table.is-style-stripes tbody tr:nth-child(odd) td,
        .wpc-global-change-log-preview .wp-block-changelog-table.is-style-stripes tbody tr:nth-child(odd) td,
        .wpc-change-log-preview .wpc-change-log-table.is-style-stripes tbody tr:nth-child(odd),
        .wpc-global-change-log-preview .wpc-change-log-table.is-style-stripes tbody tr:nth-child(odd),
        .wpc-change-log-preview .wpc-change-log-table.is-style-stripes tbody tr:nth-child(odd) td,
        .wpc-global-change-log-preview .wpc-change-log-table.is-style-stripes tbody tr:nth-child(odd) td {
            background-color: ' . esc_attr( $stripe ) . ';
        }
        .wpc-note-table-wrap {
            border: 1px solid #c3c4c7;
            border-radius: 2px;
            background: #fff;
            overflow: hidden;
            margin-bottom: 4px;
        }
        .wpc-editor-note-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin: 0;
            font-size: 13px;
            background: #fff;
        }
        .wpc-editor-note-table th {
            text-align: left;
            padding: 8px 10px;
            background: #f0f0f1;
            border-bottom: 1px solid #c3c4c7;
            font-weight: 600;
            color: #1d2327;
        }
        .wpc-editor-note-table th.wpc-changelog-col-date {
            text-align: center;
        }
        .wpc-editor-note-table th.wpc-changelog-col-author {
            text-align: left;
        }
        .wpc-editor-note-table td {
            padding: 0;
            vertical-align: middle;
            border-top: 1px solid #dcdcde;
            background: #fff;
        }
        .wpc-editor-note-table tbody tr:first-child td { border-top: none; }
        .wpc-editor-note-table tbody tr:hover td { background: #f6f7f7; }
        .wpc-editor-note-table .wpc-changelog-col-date { width: 7.5rem; white-space: nowrap; text-align: center; }
        .wpc-editor-note-table .wpc-changelog-col-author { width: 9rem; white-space: nowrap; text-align: left; }
        .wpc-editor-note-table .wpc-changelog-col-change { width: auto; text-align: left; }
        .wpc-editor-note-table .wpc-changelog-col-actions {
            width: 3rem;
            text-align: center;
            white-space: nowrap;
            padding: 0 4px;
        }
        .wpc-editor-note-table .wpc-changelog-col-date .components-text-control__input {
            text-align: center;
        }
        .wpc-editor-note-table .wpc-changelog-col-author .components-text-control__input {
            text-align: left;
            color: #646970;
        }
        .wpc-minimal-input .components-base-control { margin-bottom: 0 !important; }
        .wpc-minimal-input .components-base-control__field { margin-bottom: 0 !important; }
        .wpc-minimal-input .components-base-control__label { display: none !important; }
        .wpc-editor-note-table .components-base-control,
        .wpc-editor-note-table .components-text-control__input {
            width: 100%;
            min-width: 0;
        }
        .wpc-editor-note-table .components-text-control__input {
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
            border-radius: 0 !important;
            padding: 8px 10px !important;
            min-height: 36px !important;
            height: auto !important;
            font-size: 13px !important;
            line-height: 1.4 !important;
        }
        .wpc-editor-note-table .components-text-control__input:focus {
            border: none !important;
            box-shadow: inset 0 0 0 1px #2271b1 !important;
            outline: none !important;
        }
        .wpc-editor-note-table .components-text-control__input:disabled {
            background: transparent !important;
            opacity: 1;
        }
        .wpc-multi-note-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
        .wpc-change-log-preview,
        .wpc-global-change-log-preview { border: 1px dashed rgba(0, 124, 186, 0.35); padding: 10px; border-radius: 2px; }
        .wpc-change-log-preview > .wp-block-table,
        .wpc-change-log-preview > figure.wp-block-table,
        .wpc-global-change-log-preview .wpc-global-change-log-html > .wp-block-table,
        .wpc-global-change-log-preview .wpc-global-change-log-html > figure.wp-block-table { margin: 0; }
        .wpc-global-change-log-preview .wp-block-changelog-table table,
        .wpc-global-change-log-preview .wpc-change-log-table,
        .wpc-global-change-log-preview .wpc-change-log-table th,
        .wpc-global-change-log-preview .wpc-change-log-table td {
            background-color: ' . esc_attr( $sheet ) . ';
        }
        .wpc-editor-block-label { display: block; font-size: 11px; font-weight: 600; color: #1e1e1e; margin-bottom: 6px; }
        .wpc-block-surface-wrap { margin-bottom: 4px; }
        .wpc-single-note-idle {
            opacity: 0.45;
            transition: opacity 0.15s ease;
        }
        .wpc-single-note-idle:hover {
            opacity: 0.7;
        }
        .wpc-single-note-summary {
            font-size: 12px;
            line-height: 1.4;
            color: #646970;
            padding: 4px 8px;
            border-left: 2px solid #c3c4c7;
            background: #f6f7f7;
            border-radius: 0 2px 2px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* Empty paragraphs between compact log notes stay noticeable. */
        .editor-styles-wrapper .block-editor-block-list__block[data-type="wpc/single-change-note"] + .block-editor-block-list__block[data-type="core/paragraph"],
        .editor-styles-wrapper .block-editor-block-list__block[data-type="wpc/change-item"] + .block-editor-block-list__block[data-type="core/paragraph"] {
            min-height: 2.4em;
            margin-top: 0.35em;
            margin-bottom: 0.35em;
        }
        .wpc-global-change-log-root.wp-block {
            width: 100%;
            max-width: var(--wp--style--global--content-size, 840px);
            margin-left: auto;
            margin-right: auto;
            box-sizing: border-box;
        }
    </style>';
	}

	/**
	 * Enqueue frontend and editor styles for the Change Log table.
	 *
	 * @return void
	 */
	public function enqueue_block_styles() {
		wp_register_style( 'wpc-change-log', false );
		wp_enqueue_style( 'wpc-change-log' );
		wp_add_inline_style( 'wpc-change-log', $this->change_log_table_background_css() );
	}

	/**
	 * Register editor scripts before blocks reference them.
	 *
	 * @return void
	 */
	public function register_editor_scripts() {
		$asset_path = Helpers::plugin_path( 'assets/js/' );
		$base_deps  = [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-data', 'wp-block-editor', 'wp-i18n' ];
		$ssr_deps   = [ 'wp-server-side-render' ];

		wp_register_script(
			'wpc-shared',
			Helpers::plugin_url( 'assets/js/shared.js' ),
			[],
			filemtime( $asset_path . 'shared.js' ),
			true
		);

		wp_register_script(
			'wpc-single-change-note',
			Helpers::plugin_url( 'assets/js/single-change-note.js' ),
			array_merge( [ 'wpc-shared' ], $base_deps ),
			filemtime( $asset_path . 'single-change-note.js' ),
			true
		);

		wp_register_script(
			'wpc-multi-change-note',
			Helpers::plugin_url( 'assets/js/multi-change-note.js' ),
			array_merge( [ 'wpc-shared' ], $base_deps ),
			filemtime( $asset_path . 'multi-change-note.js' ),
			true
		);

		wp_register_script(
			'wpc-change-log',
			Helpers::plugin_url( 'assets/js/change-log.js' ),
			array_merge( [ 'wpc-shared' ], $base_deps, $ssr_deps ),
			filemtime( $asset_path . 'change-log.js' ),
			true
		);

		wp_register_script(
			'wpc-global-change-log-editor',
			Helpers::plugin_url( 'assets/js/global-change-log-editor.js' ),
			[ 'wp-hooks', 'wp-element', 'wp-data', 'wp-api-fetch', 'wp-i18n' ],
			filemtime( $asset_path . 'global-change-log-editor.js' ),
			true
		);
	}

	/**
	 * Register current and legacy block types plus table block styles.
	 *
	 * @return void
	 */
	public function register_changelog_blocks() {
		$this->register_editor_scripts();

		$change_log_args = [
			'editor_script'   => 'wpc-change-log',
			'render_callback' => [ $this->renderer, 'render_change_log' ],
			'attributes'      => Helpers::change_log_block_attributes(),
		];

		register_block_type(
			Helpers::BLOCK_SINGLE_CHANGE_NOTE,
			[
				'editor_script' => 'wpc-single-change-note',
				'attributes'    => Helpers::note_block_attributes(),
			]
		);

		register_block_type(
			Helpers::LEGACY_BLOCK_SINGLE_CHANGE_NOTE,
			[
				'editor_script' => 'wpc-single-change-note',
				'attributes'    => Helpers::note_block_attributes(),
			]
		);

		register_block_type(
			Helpers::BLOCK_MULTI_CHANGE_NOTE,
			[
				'editor_script' => 'wpc-multi-change-note',
				'attributes'    => Helpers::multi_note_block_attributes(),
			]
		);

		register_block_type(
			Helpers::LEGACY_BLOCK_MULTI_CHANGE_NOTE,
			[
				'editor_script' => 'wpc-multi-change-note',
				'attributes'    => Helpers::multi_note_block_attributes(),
			]
		);

		register_block_type( Helpers::BLOCK_CHANGE_LOG, $change_log_args );
		register_block_type( Helpers::LEGACY_BLOCK_CHANGE_LOG, $change_log_args );

		if ( function_exists( 'register_block_style' ) ) {
			foreach ( [ Helpers::BLOCK_CHANGE_LOG, Helpers::LEGACY_BLOCK_CHANGE_LOG ] as $block_name ) {
				register_block_style( $block_name, [ 'name' => 'default', 'label' => __( 'Default', 'wp-changelog' ), 'is_default' => true ] );
				register_block_style( $block_name, [ 'name' => 'stripes', 'label' => __( 'Stripes', 'wp-changelog' ) ] );
			}
		}
	}

	/**
	 * Attach script translations in the block editor.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		$languages = Helpers::plugin_path( 'languages' );
		$settings  = Plugin::instance()->settings()->get_settings();

		if ( wp_script_is( 'wpc-shared', 'registered' ) ) {
			wp_add_inline_script(
				'wpc-shared',
				'window.wpcChangelogSettings = ' . wp_json_encode(
					[
						'shortcutEnabled' => ! empty( $settings['shortcutEnabled'] ),
						'shortcutPrefix'  => ! empty( $settings['shortcutPrefix'] ) ? (string) $settings['shortcutPrefix'] : '#log',
					]
				) . ';',
				'before'
			);
		}

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
}
