<?php
/**
 * Shared helpers: block constants, paths, parsing, and attribute schemas.
 *
 * @package WP_Changelog
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** @var string Current Single Change Note block name. */
define( 'WPC_BLOCK_SINGLE_CHANGE_NOTE', 'wpc/single-change-note' );

/** @var string Current Multi Change Note block name. */
define( 'WPC_BLOCK_MULTI_CHANGE_NOTE', 'wpc/multi-change-note' );

/** @var string Current Change Log block name. */
define( 'WPC_BLOCK_CHANGE_LOG', 'wpc/change-log' );

/** @var string Revision Multiline Note block name (revision-synced editable rows). */
define( 'WPC_BLOCK_REVISION_MULTILINE_NOTE', 'wpc/revision-multiline-note' );

/** @var string Legacy Version Multiline Note block name (hidden from inserter). */
define( 'WPC_LEGACY_BLOCK_VERSION_MULTILINE_NOTE', 'wpc/version-multiline-note' );

/** @var string Legacy Generated Multiline Note block name (hidden from inserter). */
define( 'WPC_LEGACY_BLOCK_GENERATED_MULTILINE_NOTE', 'wpc/generated-multiline-note' );

/** @var string Legacy Single Change Note block name (hidden from inserter). */
define( 'WPC_LEGACY_BLOCK_SINGLE_CHANGE_NOTE', 'wpc/change-item' );

/** @var string Legacy Multi Change Note block name (hidden from inserter). */
define( 'WPC_LEGACY_BLOCK_MULTI_CHANGE_NOTE', 'wpc/multi-note' );

/** @var string Legacy Change Log block name (hidden from inserter). */
define( 'WPC_LEGACY_BLOCK_CHANGE_LOG', 'wpc/change-table' );

/**
 * Absolute filesystem path inside the plugin directory.
 *
 * @param string $relative Optional path relative to the plugin root.
 * @return string
 */
function wpc_plugin_path( $relative = '' ) {
    return trailingslashit( dirname( __DIR__ ) ) . ltrim( $relative, '/' );
}

/**
 * Public URL for a file inside the plugin directory.
 *
 * @param string $relative Optional path relative to the plugin root.
 * @return string
 */
function wpc_plugin_url( $relative = '' ) {
    return plugins_url( ltrim( $relative, '/' ), dirname( __DIR__ ) . '/wp-changelog.php' );
}

/**
 * Resolve the post ID used during server-side block rendering.
 *
 * Prefers the explicit post_id query arg from editor previews over get_the_ID().
 *
 * @return int Post ID, or 0 when unavailable.
 */
function wpc_get_render_post_id() {
    if ( defined( 'REST_REQUEST' ) && REST_REQUEST && ! empty( $_GET['post_id'] ) ) {
        return intval( $_GET['post_id'] );
    }

    return get_the_ID() ?: 0;
}

/**
 * Whether the Change Log is being rendered outside a saved post context.
 *
 * @param WP_Post|null $post    Loaded post object.
 * @param int          $post_id Requested post ID.
 * @return bool
 */
function wpc_is_template_preview( $post, $post_id ) {
    return ! $post
        || $post->post_type === 'wp_block'
        || $post->post_type === 'wp_template'
        || $post_id === 0;
}

/**
 * Parse a d.m.Y date string into a Unix timestamp.
 *
 * @param string $date_str Date in d.m.Y format, or "-" when empty.
 * @return int
 */
function wpc_parse_date_to_timestamp( $date_str ) {
    if ( empty( $date_str ) || $date_str === '-' ) {
        return time();
    }

    $timestamp = strtotime( str_replace( '.', '-', $date_str ) );

    return $timestamp ? $timestamp : time();
}

/**
 * Format a Unix timestamp as d.m.Y.
 *
 * @param int $timestamp Unix timestamp.
 * @return string
 */
function wpc_format_timestamp( $timestamp ) {
    return date( 'd.m.Y', (int) $timestamp );
}

/**
 * Recursively find block instances by name.
 *
 * @param array  $blocks     Parsed block list.
 * @param string $block_name Target block name.
 * @return array Matching block arrays.
 */
function wpc_find_blocks_by_name( array $blocks, $block_name ) {
    return wpc_find_blocks_by_names( $blocks, [ $block_name ] );
}

/**
 * Recursively find block instances matching any of the given names.
 *
 * @param array $blocks      Parsed block list.
 * @param array $block_names Block names to match.
 * @return array Matching block arrays.
 */
function wpc_find_blocks_by_names( array $blocks, array $block_names ) {
    $found = [];

    foreach ( $blocks as $block ) {
        if ( isset( $block['blockName'] ) && in_array( $block['blockName'], $block_names, true ) ) {
            $found[] = $block;
        }
        if ( ! empty( $block['innerBlocks'] ) ) {
            $found = array_merge( $found, wpc_find_blocks_by_names( $block['innerBlocks'], $block_names ) );
        }
    }

    return $found;
}

/**
 * Build wrapper CSS classes for the Change Log table container.
 *
 * @param array $attributes Change Log block attributes.
 * @return string Space-separated class names.
 */
function wpc_get_wrapper_classes( array $attributes ) {
    $classes = [ 'wp-block-table', 'wp-block-changelog-table' ];

    if ( ! empty( $attributes['align'] ) ) {
        $classes[] = 'align' . $attributes['align'];
    }

    $table_style = ! empty( $attributes['tableStyle'] ) ? $attributes['tableStyle'] : 'default';

    if ( $table_style === 'stripes' ) {
        $classes[] = 'is-style-stripes';
    }

    if ( ! empty( $attributes['className'] ) ) {
        $classes[] = $attributes['className'];
    }

    return implode( ' ', array_map( 'sanitize_html_class', $classes ) );
}

/**
 * Build table element CSS classes for the Change Log block.
 *
 * @param array $attributes Change Log block attributes.
 * @return string Table class string.
 */
function wpc_get_table_classes( array $attributes ) {
    return ! empty( $attributes['hasFixedLayout'] ) ? 'has-fixed-layout' : '';
}

/**
 * Timestamp used for fine-grained sorting within the same calendar date.
 *
 * @param array $entry Changelog entry with timestamp and optional changed_at.
 * @return int
 */
function wpc_get_entry_sort_timestamp( array $entry ) {
    return isset( $entry['changed_at'] ) ? (int) $entry['changed_at'] : (int) $entry['timestamp'];
}

/**
 * Unix timestamp for when a post or revision version was saved.
 *
 * @param WP_Post $version_post Post or revision object.
 * @return int
 */
function wpc_get_post_version_timestamp( WP_Post $version_post ) {
    $timestamp = strtotime( $version_post->post_modified );

    if ( ! $timestamp ) {
        $timestamp = strtotime( $version_post->post_date );
    }

    return $timestamp ? (int) $timestamp : 0;
}

/**
 * Earliest known save timestamp for a post (oldest revision, else post_date).
 *
 * Uses the first stored revision when available because post_date can reflect
 * a later publish or modified date rather than the initial save.
 *
 * @param WP_Post $post Post object.
 * @return int
 */
function wpc_get_post_earliest_version_timestamp( WP_Post $post ) {
    $revisions = wp_get_post_revisions(
        $post->ID,
        [
            'order'          => 'ASC',
            'posts_per_page' => 1,
            'check_enabled'  => false,
        ]
    );

    if ( ! empty( $revisions ) ) {
        $oldest = reset( $revisions );

        if ( $oldest instanceof WP_Post ) {
            $timestamp = wpc_get_post_version_timestamp( $oldest );

            if ( $timestamp > 0 ) {
                return $timestamp;
            }
        }
    }

    $created = strtotime( $post->post_date );

    return $created ? (int) $created : time();
}

/**
 * Attribute schema for Single and Multi Change Note row fields.
 *
 * @return array Block attribute definitions.
 */
function wpc_note_block_attributes() {
    return [
        'date'      => [ 'type' => 'string', 'default' => '' ],
        'comment'   => [ 'type' => 'string', 'default' => '' ],
        'author'    => [ 'type' => 'string', 'default' => '' ],
        'changedAt' => [ 'type' => 'number', 'default' => 0 ],
    ];
}

/**
 * Shared row-object schema used by multi-line note blocks.
 *
 * @return array Block attribute item definition.
 */
function wpc_note_row_attribute_schema() {
    return [
        'type'       => 'object',
        'properties' => [
            'id'        => [ 'type' => 'string' ],
            'date'      => [ 'type' => 'string' ],
            'comment'   => [ 'type' => 'string' ],
            'author'    => [ 'type' => 'string' ],
            'changedAt' => [ 'type' => 'number' ],
        ],
    ];
}

/**
 * Attribute schema for the Multi Change Note block.
 *
 * @return array Block attribute definitions.
 */
function wpc_multi_note_block_attributes() {
    return [
        'rows' => [
            'type'    => 'array',
            'default' => [],
            'items'   => wpc_note_row_attribute_schema(),
        ],
    ];
}

/**
 * Normalize merged Change-column sort order to oldest_first or newest_first.
 *
 * Accepts legacy asc/desc values from older block saves:
 * asc  was labeled "Oldest on top", desc was labeled "Newest on top".
 *
 * @param string $order Raw attribute value.
 * @return string "oldest_first" or "newest_first".
 */
function wpc_normalize_merged_change_order( $order ) {
    if ( $order === 'oldest_first' || $order === 'asc' ) {
        return 'oldest_first';
    }

    if ( $order === 'newest_first' || $order === 'desc' ) {
        return 'newest_first';
    }

    return 'newest_first';
}

/**
 * Attribute schema for the Change Log block.
 *
 * @return array Block attribute definitions.
 */
function wpc_change_log_block_attributes() {
    return [
        'showAuthor'       => [ 'type' => 'boolean', 'default' => true ],
        'hasFixedLayout'   => [ 'type' => 'boolean', 'default' => false ],
        'consolidateDates' => [ 'type' => 'boolean', 'default' => false ],
        'listChanges'      => [ 'type' => 'boolean', 'default' => false ],
        'changeFieldSort'  => [ 'type' => 'string', 'default' => 'time' ],
        'changeFieldOrder' => [ 'type' => 'string', 'default' => 'newest_first' ],
        'sortOrder'        => [ 'type' => 'string', 'default' => 'desc' ],
        'visibleOnPage'    => [ 'type' => 'boolean', 'default' => true ],
        'tableStyle'       => [ 'type' => 'string', 'default' => 'default' ],
        'align'            => [ 'type' => 'string', 'default' => '' ],
        'className'        => [ 'type' => 'string', 'default' => '' ],
    ];
}

/**
 * Attribute schema for the Revision Multiline Note block.
 *
 * @return array Block attribute definitions.
 */
function wpc_revision_multiline_note_block_attributes() {
    return [
        'rows'                  => [
            'type'    => 'array',
            'default' => [],
            'items'   => wpc_note_row_attribute_schema(),
        ],
        'sortField'             => [ 'type' => 'string', 'default' => 'date' ],
        'sortOrder'             => [ 'type' => 'string', 'default' => 'desc' ],
        'showAuthor'            => [ 'type' => 'boolean', 'default' => true ],
        'includeCurrentVersion' => [ 'type' => 'boolean', 'default' => true ],
    ];
}

/**
 * All block names that share the Revision Multiline Note implementation.
 *
 * @return string[]
 */
function wpc_revision_multiline_note_block_names() {
    return [
        WPC_BLOCK_REVISION_MULTILINE_NOTE,
        WPC_LEGACY_BLOCK_VERSION_MULTILINE_NOTE,
        WPC_LEGACY_BLOCK_GENERATED_MULTILINE_NOTE,
    ];
}

/**
 * Generate a unique row id for multi-line note blocks.
 *
 * @return string
 */
function wpc_generate_row_id() {
    if ( function_exists( 'wp_generate_uuid4' ) ) {
        return 'row-' . wp_generate_uuid4();
    }

    return 'row-' . uniqid( '', true );
}

/**
 * Ensure block attributes are always a usable array.
 *
 * @param mixed $attributes Raw block attributes from the editor or REST render.
 * @return array
 */
function wpc_normalize_block_attributes( $attributes ) {
    return is_array( $attributes ) ? $attributes : [];
}

/**
 * Whether the Change Log is being rendered in the block editor or wp-admin.
 *
 * @return bool
 */
function wpc_is_change_log_editor_context() {
    if ( is_admin() ) {
        return true;
    }

    return defined( 'REST_REQUEST' ) && REST_REQUEST;
}

/**
 * Whether the Change Log table should be output for the current request.
 *
 * @param array $attributes Change Log block attributes.
 * @return bool
 */
function wpc_is_change_log_visible_on_page( array $attributes ) {
    if ( ! isset( $attributes['visibleOnPage'] ) || $attributes['visibleOnPage'] ) {
        return true;
    }

    return wpc_is_change_log_editor_context();
}

/**
 * Default display attributes for the Change Log block/table.
 *
 * @return array Attribute defaults keyed by block attribute name.
 */
function wpc_default_change_log_display_attributes() {
    $defaults = [];

    foreach ( wpc_change_log_block_attributes() as $key => $schema ) {
        if ( array_key_exists( 'default', $schema ) ) {
            $defaults[ $key ] = $schema['default'];
        }
    }

    return $defaults;
}

/**
 * Default global Change Log integration settings.
 *
 * @return array
 */
function wpc_default_global_change_log_settings() {
    return array_merge(
        [
            'enabled'    => false,
            'post_types' => [ 'page' ],
        ],
        wpc_default_change_log_display_attributes()
    );
}

/**
 * Load saved global Change Log settings merged with defaults.
 *
 * @return array
 */
function wpc_get_global_change_log_settings() {
    $saved = get_option( 'wpc_global_change_log_settings', [] );

    return wp_parse_args(
        is_array( $saved ) ? $saved : [],
        wpc_default_global_change_log_settings()
    );
}

/**
 * Whether parsed post content already contains a Change Log block.
 *
 * @param string $post_content Raw post content.
 * @return bool
 */
function wpc_post_has_change_log_block( $post_content ) {
    $blocks = parse_blocks( (string) $post_content );

    return ! empty(
        wpc_find_blocks_by_names(
            $blocks,
            [ WPC_BLOCK_CHANGE_LOG, WPC_LEGACY_BLOCK_CHANGE_LOG ]
        )
    );
}
