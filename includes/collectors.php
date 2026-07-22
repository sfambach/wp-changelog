<?php
/**
 * Collect, normalize, sort, and consolidate changelog entries from post content.
 *
 * @package WP_Changelog
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize raw note fields into a sanitized changelog entry.
 *
 * @param string $date_str   Display date (d.m.Y).
 * @param string $comment    Change description.
 * @param string $author     Author display name.
 * @param int    $changed_at Unix timestamp of the last edit, or 0 to derive from date.
 * @return array {
 *     @type int    $timestamp  Sort timestamp derived from the display date.
 *     @type int    $changed_at Fine-grained sort timestamp.
 *     @type string $date_str   Escaped display date.
 *     @type string $comment    Escaped change text.
 *     @type string $author     Escaped author name.
 * }
 */
function wpc_parse_change_row( $date_str, $comment, $author, $changed_at = 0 ) {
    $date_str   = ! empty( $date_str ) ? esc_html( $date_str ) : '-';
    $comment    = esc_html( $comment );
    $author     = ! empty( $author ) ? esc_html( $author ) : __( 'Unknown', 'wp-changelog' );
    $changed_at = intval( $changed_at );

    if ( $changed_at <= 0 ) {
        $changed_at = wpc_parse_date_to_timestamp( $date_str );
    }

    return [
        'timestamp'  => wpc_parse_date_to_timestamp( $date_str ),
        'changed_at' => $changed_at,
        'date_str'   => $date_str,
        'comment'    => $comment,
        'author'     => $author,
    ];
}

/**
 * Parse a Single Change Note block into a changelog entry.
 *
 * @param array $block Parsed block array from parse_blocks().
 * @return array Normalized changelog entry.
 */
function wpc_parse_single_change_note_block( array $block ) {
    return wpc_parse_change_row(
        $block['attrs']['date'] ?? '',
        $block['attrs']['comment'] ?? '',
        $block['attrs']['author'] ?? '',
        $block['attrs']['changedAt'] ?? 0
    );
}

/**
 * Parse one row from a Multi Change Note block.
 *
 * @param array $row Row object from block attributes.
 * @return array Normalized changelog entry.
 */
function wpc_parse_multi_change_note_row( array $row ) {
    return wpc_parse_change_row(
        $row['date'] ?? '',
        $row['comment'] ?? '',
        $row['author'] ?? '',
        $row['changedAt'] ?? 0
    );
}

/**
 * Sort entries ascending by display date, then by changed_at.
 *
 * @param array $entries Changelog entries.
 * @return array Sorted entries.
 */
function wpc_sort_entries_by_date( array $entries ) {
    usort(
        $entries,
        function ( $a, $b ) {
            $date_cmp = wpc_parse_date_to_timestamp( $a['date_str'] ) <=> wpc_parse_date_to_timestamp( $b['date_str'] );
            if ( $date_cmp !== 0 ) {
                return $date_cmp;
            }

            return wpc_get_entry_sort_timestamp( $a ) <=> wpc_get_entry_sort_timestamp( $b );
        }
    );

    return $entries;
}

/**
 * Collect all rows from Multi Change Note blocks in parsed content.
 *
 * @param array $blocks Parsed block list.
 * @return array Changelog entries sorted by date.
 */
function wpc_collect_multi_change_note_items( array $blocks ) {
    $items = [];
    $names = [ WPC_BLOCK_MULTI_CHANGE_NOTE, WPC_LEGACY_BLOCK_MULTI_CHANGE_NOTE ];

    foreach ( wpc_find_blocks_by_names( $blocks, $names ) as $block ) {
        $rows = ! empty( $block['attrs']['rows'] ) && is_array( $block['attrs']['rows'] ) ? $block['attrs']['rows'] : [];

        foreach ( $rows as $row ) {
            if ( empty( trim( $row['comment'] ?? '' ) ) ) {
                continue;
            }

            $items[] = wpc_parse_multi_change_note_row( $row );
        }
    }

    return wpc_sort_entries_by_date( $items );
}

/**
 * Collect all rows from Revision Multiline Note blocks in parsed content.
 *
 * Only rows with a non-empty comment are included, matching Multi Change Note rules.
 *
 * @param array $blocks Parsed block list.
 * @return array Changelog entries sorted by date.
 */
function wpc_collect_revision_multiline_note_items( array $blocks ) {
    $items = [];

    foreach ( wpc_find_blocks_by_names( $blocks, wpc_revision_multiline_note_block_names() ) as $block ) {
        $rows = ! empty( $block['attrs']['rows'] ) && is_array( $block['attrs']['rows'] ) ? $block['attrs']['rows'] : [];

        foreach ( $rows as $row ) {
            if ( empty( trim( $row['comment'] ?? '' ) ) ) {
                continue;
            }

            $items[] = wpc_parse_multi_change_note_row( $row );
        }
    }

    return wpc_sort_entries_by_date( $items );
}

/**
 * Collect all change notes from post content (single, multi, and revision blocks).
 *
 * @param string $post_content Raw post content.
 * @return array Changelog entries sorted by date.
 */
function wpc_collect_change_items( $post_content ) {
    $blocks       = parse_blocks( $post_content );
    $change_items = [];
    $single_names = [ WPC_BLOCK_SINGLE_CHANGE_NOTE, WPC_LEGACY_BLOCK_SINGLE_CHANGE_NOTE ];

    foreach ( wpc_find_blocks_by_names( $blocks, $single_names ) as $block ) {
        if ( empty( trim( $block['attrs']['comment'] ?? '' ) ) ) {
            continue;
        }

        $change_items[] = wpc_parse_single_change_note_block( $block );
    }

    $change_items = array_merge( $change_items, wpc_collect_multi_change_note_items( $blocks ) );
    $change_items = array_merge( $change_items, wpc_collect_revision_multiline_note_items( $blocks ) );

    return wpc_sort_entries_by_date( $change_items );
}

/**
 * Derive post creation date metadata for the initial changelog row.
 *
 * @param WP_Post $post Post object.
 * @return array {
 *     @type int    $timestamp  Post creation Unix timestamp.
 *     @type int    $changed_at Same as timestamp.
 *     @type string $date_str   Formatted creation date.
 * }
 */
function wpc_get_post_creation_meta( WP_Post $post ) {
    $latest_post = get_post( $post->ID );

    if ( ! $latest_post ) {
        $latest_post = $post;
    }

    $created_timestamp = wpc_get_post_earliest_version_timestamp( $latest_post );

    return [
        'timestamp'  => $created_timestamp,
        'changed_at' => $created_timestamp,
        'date_str'   => wpc_format_timestamp( $created_timestamp ),
    ];
}

/**
 * Build the synthetic "Post created" changelog entry.
 *
 * @param WP_Post $post Post object.
 * @return array Changelog entry including HTML comment markup.
 */
function wpc_build_creation_entry( WP_Post $post ) {
    $author_obj = get_user_by( 'id', $post->post_author );
    $meta       = wpc_get_post_creation_meta( $post );

    return array_merge(
        $meta,
        [
            'comment'      => '<strong>' . esc_html__( 'Post created', 'wp-changelog' ) . '</strong>',
            'author'       => $author_obj ? $author_obj->display_name : __( 'System', 'wp-changelog' ),
            'is_creation'  => true,
        ]
    );
}

/**
 * Sample entries shown when previewing inside block patterns or templates.
 *
 * @return array Changelog entries for editor preview.
 */
function wpc_get_template_preview_entries() {
    return [
        [
            'timestamp'  => time() - DAY_IN_SECONDS,
            'changed_at' => time() - DAY_IN_SECONDS,
            'date_str'   => wpc_format_timestamp( time() - DAY_IN_SECONDS ),
            'comment'    => '<strong>' . esc_html__( 'Post created', 'wp-changelog' ) . '</strong>',
            'author'     => __( 'System', 'wp-changelog' ),
            'is_creation'  => true,
        ],
        [
            'timestamp'  => time(),
            'changed_at'   => time(),
            'date_str'     => wpc_format_timestamp( time() ),
            'comment'      => esc_html__( 'Example: Replaced header image and updated text layout.', 'wp-changelog' ),
            'author'       => __( 'John Doe', 'wp-changelog' ),
        ],
    ];
}

/**
 * Build the full entry list for a post, including the creation row.
 *
 * @param WP_Post|null $post                Post object.
 * @param bool         $is_template_preview Whether dummy preview data should be returned.
 * @return array Changelog entries before table sort order is applied.
 */
function wpc_collect_changelog_entries( $post, $is_template_preview ) {
    if ( $is_template_preview ) {
        return wpc_get_template_preview_entries();
    }

    $change_items   = wpc_collect_change_items( $post->post_content );
    $creation_entry = wpc_build_creation_entry( $post );

    return array_merge( [ $creation_entry ], $change_items );
}

/**
 * Sort changelog entries by display date for the table Sort Order setting.
 *
 * @param array  $entries    Changelog entries.
 * @param string $sort_order "asc" or "desc".
 * @return array Sorted entries.
 */
function wpc_sort_changelog_entries( array $entries, $sort_order ) {
    usort(
        $entries,
        function ( $a, $b ) use ( $sort_order ) {
            $a_ts = isset( $a['timestamp'] ) ? (int) $a['timestamp'] : wpc_parse_date_to_timestamp( $a['date_str'] ?? '' );
            $b_ts = isset( $b['timestamp'] ) ? (int) $b['timestamp'] : wpc_parse_date_to_timestamp( $b['date_str'] ?? '' );

            if ( $a_ts === $b_ts ) {
                $cmp = wpc_get_entry_sort_timestamp( $a ) <=> wpc_get_entry_sort_timestamp( $b );
            } else {
                $cmp = $a_ts <=> $b_ts;
            }

            if ( $sort_order === 'asc' ) {
                return $cmp;
            }

            return -$cmp;
        }
    );

    return $entries;
}

/**
 * Sort consolidated table rows by display date.
 *
 * @param array  $rows       Consolidated rows with date_str, author, and items.
 * @param string $sort_order "asc" or "desc".
 * @return array Sorted rows.
 */
function wpc_sort_consolidated_rows( array $rows, $sort_order ) {
    usort(
        $rows,
        function ( $a, $b ) use ( $sort_order ) {
            $a_ts = wpc_parse_date_to_timestamp( $a['date_str'] ?? '' );
            $b_ts = wpc_parse_date_to_timestamp( $b['date_str'] ?? '' );
            $cmp  = $a_ts <=> $b_ts;

            if ( $sort_order === 'asc' ) {
                return $cmp;
            }

            return -$cmp;
        }
    );

    return $rows;
}

/**
 * Build one renderable change item for the Change column.
 *
 * @param string $comment     Change text (may contain safe HTML for system rows).
 * @param int    $changed_at  Sort timestamp for merged change ordering.
 * @param bool   $is_creation Whether this item is the synthetic post-created row.
 * @return array {
 *     @type string $text        Rendered change HTML/text.
 *     @type int    $changed_at  Sort timestamp.
 *     @type bool   $is_creation True for the post-created row.
 * }
 */
function wpc_make_change_item( $comment, $changed_at, $is_creation = false ) {
    return [
        'text'        => $comment,
        'changed_at'  => (int) $changed_at,
        'is_creation' => (bool) $is_creation,
    ];
}

/**
 * Split a multiline comment into multiple change items.
 *
 * Plain-text comments with line breaks become separate items. Comments that
 * already contain HTML (e.g. "Post created") are kept as a single item.
 *
 * @param string $comment     Raw comment text.
 * @param int    $changed_at    Sort timestamp applied to each derived item.
 * @param bool   $is_creation   Whether the source entry is the post-created row.
 * @return array List of change items.
 */
function wpc_expand_comment_to_change_items( $comment, $changed_at, $is_creation = false ) {
    $comment = (string) $comment;

    if ( $comment === '' ) {
        return [];
    }

    if ( strpos( $comment, '<' ) === false && preg_match( '/\R/u', $comment ) ) {
        $items = [];

        foreach ( preg_split( '/\R+/u', $comment ) as $line ) {
            $line = trim( $line );
            if ( $line === '' ) {
                continue;
            }

            $items[] = wpc_make_change_item( esc_html( $line ), $changed_at, $is_creation );
        }

        return $items;
    }

    return [ wpc_make_change_item( $comment, $changed_at, $is_creation ) ];
}

/**
 * Convert a full changelog entry into renderable change items.
 *
 * @param array $entry Changelog entry from collectors.
 * @return array List of change items.
 */
function wpc_entry_to_change_items( array $entry ) {
    return wpc_expand_comment_to_change_items(
        $entry['comment'] ?? '',
        wpc_get_entry_sort_timestamp( $entry ),
        ! empty( $entry['is_creation'] )
    );
}

/**
 * Merge entries that share the same display date and author into single rows.
 *
 * @param array $entries Sorted changelog entries.
 * @return array Rows keyed internally by date|author, each with an items array.
 */
function wpc_consolidate_entries_by_date_and_author( array $entries ) {
    $consolidated = [];

    foreach ( $entries as $entry ) {
        $group_key = $entry['date_str'] . '|' . $entry['author'];

        if ( ! isset( $consolidated[ $group_key ] ) ) {
            $consolidated[ $group_key ] = [
                'date_str' => $entry['date_str'],
                'author'   => $entry['author'],
                'items'    => [],
            ];
        }

        foreach ( wpc_entry_to_change_items( $entry ) as $item ) {
            $consolidated[ $group_key ]['items'][] = $item;
        }
    }

    return $consolidated;
}

/**
 * Prepare normalized table rows for rendering.
 *
 * @param array $entries          Sorted changelog entries.
 * @param bool  $consolidate_dates Whether rows should be merged by date and author.
 * @return array Table rows with date_str, author, and items keys.
 */
function wpc_build_change_log_table_rows( array $entries, $consolidate_dates ) {
    if ( $consolidate_dates ) {
        return wpc_consolidate_entries_by_date_and_author( $entries );
    }

    $rows = [];

    foreach ( $entries as $entry ) {
        $rows[] = [
            'date_str' => $entry['date_str'],
            'author'   => $entry['author'],
            'items'    => wpc_entry_to_change_items( $entry ),
        ];
    }

    return $rows;
}

/**
 * Format changelog entries as plain multiline text for export blocks.
 *
 * Each entry becomes one line: "date | change | author" (author optional).
 *
 * @param array $entries     Sorted changelog entries.
 * @param bool  $show_author Whether to append the author column.
 * @return string Multiline plain text.
 */
function wpc_format_entries_as_multiline_text( array $entries, $show_author ) {
    $lines = [];

    foreach ( $entries as $entry ) {
        $comment = trim( wp_strip_all_tags( $entry['comment'] ?? '' ) );
        if ( $comment === '' ) {
            continue;
        }

        if ( $show_author ) {
            $lines[] = sprintf(
                '%s | %s | %s',
                $entry['date_str'],
                $comment,
                $entry['author'] ?? ''
            );
            continue;
        }

        $lines[] = sprintf( '%s | %s', $entry['date_str'], $comment );
    }

    return implode( "\n", $lines );
}

/**
 * Build one changelog-style row from a WordPress post or revision.
 *
 * @param WP_Post $version_post Post or revision object.
 * @param string  $label        Description shown in the Change column.
 * @return array Normalized entry array.
 */
function wpc_parse_version_entry( WP_Post $version_post, $label ) {
    $author_obj = get_user_by( 'id', $version_post->post_author );
    $timestamp  = wpc_get_post_version_timestamp( $version_post );

    if ( ! $timestamp ) {
        $timestamp = time();
    }

    return [
        'timestamp'  => $timestamp,
        'changed_at' => $timestamp,
        'date_str'   => wpc_format_timestamp( $timestamp ),
        'comment'    => esc_html( $label ),
        'author'     => $author_obj ? esc_html( $author_obj->display_name ) : __( 'Unknown', 'wp-changelog' ),
    ];
}

/**
 * Sample version rows for template and pattern previews.
 *
 * @return array Version entries for editor preview.
 */
function wpc_get_template_preview_version_entries() {
    return [
        [
            'timestamp'  => time() - DAY_IN_SECONDS,
            'changed_at' => time() - DAY_IN_SECONDS,
            'date_str'   => wpc_format_timestamp( time() - DAY_IN_SECONDS ),
            'comment'    => esc_html__( 'Initial version', 'wp-changelog' ),
            'author'     => __( 'System', 'wp-changelog' ),
        ],
        [
            'timestamp'  => time(),
            'changed_at' => time(),
            'date_str'   => wpc_format_timestamp( time() ),
            'comment'    => esc_html__( 'Revision saved', 'wp-changelog' ),
            'author'     => __( 'John Doe', 'wp-changelog' ),
        ],
    ];
}

/**
 * Collect WordPress post versions (current state and stored revisions).
 *
 * @param WP_Post|null $post                Post object.
 * @param bool         $is_template_preview Whether dummy preview data should be used.
 * @param array        $attributes          Block attributes.
 * @return array Sorted version entries ready for multiline formatting.
 */
function wpc_collect_post_version_entries( $post, $is_template_preview, array $attributes ) {
    if ( $is_template_preview ) {
        return wpc_get_template_preview_version_entries();
    }

    $entries = [];

    if ( ! isset( $attributes['includeCurrentVersion'] ) || $attributes['includeCurrentVersion'] ) {
        $entries[] = wpc_parse_version_entry( $post, __( 'Current revision', 'wp-changelog' ) );
    }

    $revisions = wp_get_post_revisions(
        $post->ID,
        [
            'order'          => 'ASC',
            'posts_per_page' => 100,
            'check_enabled'  => false,
        ]
    );

    if ( is_array( $revisions ) ) {
        foreach ( $revisions as $revision ) {
            if ( $revision instanceof WP_Post ) {
                $entries[] = wpc_parse_version_entry( $revision, __( 'Revision saved', 'wp-changelog' ) );
            }
        }
    }

    $sort_order = ! empty( $attributes['sortOrder'] ) ? $attributes['sortOrder'] : 'desc';

    return wpc_sort_changelog_entries( $entries, $sort_order );
}

/**
 * Build one editable row from a revision date slot.
 *
 * @param array $slot Date slot with date, author, and changedAt keys.
 * @return array Row object stored in block attributes.
 */
function wpc_create_revision_note_row( array $slot ) {
    return [
        'id'        => wpc_generate_row_id(),
        'date'      => $slot['date'],
        'comment'   => '',
        'author'    => $slot['author'],
        'changedAt' => (int) $slot['changedAt'],
    ];
}

/**
 * Collect one row per unique revision date from WordPress post versions.
 *
 * When multiple revisions share a calendar date, the latest revision on that
 * date supplies the author and changedAt values.
 *
 * @param WP_Post $post            Post object.
 * @param bool    $include_current Whether the current post state should be included.
 * @param array   $pending_data    Optional unsaved post fields (post_modified, post_author).
 * @return array List of date slots with date, author, and changedAt keys.
 */
function wpc_collect_revision_date_slots( WP_Post $post, $include_current, array $pending_data = [] ) {
    $slots   = [];
    $sources = [];

    if ( $include_current ) {
        if ( ! empty( $pending_data['post_modified'] ) ) {
            $timestamp = strtotime( $pending_data['post_modified'] );

            if ( ! $timestamp && ! empty( $pending_data['post_date'] ) ) {
                $timestamp = strtotime( $pending_data['post_date'] );
            }

            if ( ! $timestamp ) {
                $timestamp = time();
            }

            $author_id = ! empty( $pending_data['post_author'] )
                ? (int) $pending_data['post_author']
                : (int) $post->post_author;
            $author_obj = get_user_by( 'id', $author_id );
            $date_str   = wpc_format_timestamp( $timestamp );
            $slots[ $date_str ] = [
                'date'      => $date_str,
                'author'    => $author_obj ? esc_html( $author_obj->display_name ) : __( 'Unknown', 'wp-changelog' ),
                'changedAt' => $timestamp,
            ];
        } else {
            $sources[] = $post;
        }
    }

    $revisions = wp_get_post_revisions(
        $post->ID,
        [
            'order'          => 'ASC',
            'posts_per_page' => 100,
            'check_enabled'  => false,
        ]
    );

    if ( is_array( $revisions ) ) {
        foreach ( $revisions as $revision ) {
            if ( $revision instanceof WP_Post ) {
                $sources[] = $revision;
            }
        }
    }

    foreach ( $sources as $version_post ) {
        $timestamp = wpc_get_post_version_timestamp( $version_post );

        if ( ! $timestamp ) {
            $timestamp = time();
        }

        $date_str   = wpc_format_timestamp( $timestamp );
        $author_obj = get_user_by( 'id', $version_post->post_author );
        $author     = $author_obj ? esc_html( $author_obj->display_name ) : __( 'Unknown', 'wp-changelog' );

        if ( ! isset( $slots[ $date_str ] ) || $timestamp >= $slots[ $date_str ]['changedAt'] ) {
            $slots[ $date_str ] = [
                'date'      => $date_str,
                'author'    => $author,
                'changedAt' => $timestamp,
            ];
        }
    }

    return array_values( $slots );
}

/**
 * Merge stored rows with revision date slots, preserving user comments.
 *
 * @param array $existing_rows Rows from block attributes.
 * @param array $date_slots    Slots from wpc_collect_revision_date_slots().
 * @return array Synced row list.
 */
function wpc_sync_revision_note_rows( array $existing_rows, array $date_slots ) {
    $by_date = [];

    foreach ( $existing_rows as $row ) {
        if ( ! is_array( $row ) || empty( $row['date'] ) ) {
            continue;
        }

        $by_date[ $row['date'] ] = $row;
    }

    $merged = [];

    foreach ( $date_slots as $slot ) {
        $date = $slot['date'];

        if ( isset( $by_date[ $date ] ) ) {
            $row = $by_date[ $date ];

            if ( empty( $row['id'] ) ) {
                $row['id'] = wpc_generate_row_id();
            }

            $row['author'] = $slot['author'];

            if ( empty( $row['changedAt'] ) ) {
                $row['changedAt'] = (int) $slot['changedAt'];
            }

            $merged[] = $row;
            continue;
        }

        $merged[] = wpc_create_revision_note_row( $slot );
    }

    return $merged;
}

/**
 * Sort revision note rows for display using the block table settings.
 *
 * @param array  $rows       Row objects from block attributes.
 * @param string $sort_field "date", "comment", or "author".
 * @param string $sort_order "asc" or "desc".
 * @return array Sorted rows.
 */
function wpc_sort_revision_note_rows( array $rows, $sort_field, $sort_order ) {
    $sort_field = in_array( $sort_field, [ 'date', 'comment', 'author' ], true ) ? $sort_field : 'date';
    $sort_order = $sort_order === 'asc' ? 'asc' : 'desc';

    usort(
        $rows,
        function ( $a, $b ) use ( $sort_field, $sort_order ) {
            switch ( $sort_field ) {
                case 'comment':
                    $cmp = strcasecmp( (string) ( $a['comment'] ?? '' ), (string) ( $b['comment'] ?? '' ) );
                    break;
                case 'author':
                    $cmp = strcasecmp( (string) ( $a['author'] ?? '' ), (string) ( $b['author'] ?? '' ) );
                    break;
                case 'date':
                default:
                    $cmp = wpc_parse_date_to_timestamp( $a['date'] ?? '' ) <=> wpc_parse_date_to_timestamp( $b['date'] ?? '' );
                    break;
            }

            if ( $cmp === 0 ) {
                $cmp = (int) ( $a['changedAt'] ?? 0 ) <=> (int) ( $b['changedAt'] ?? 0 );
            }

            if ( $sort_order === 'asc' ) {
                return $cmp;
            }

            return -$cmp;
        }
    );

    return $rows;
}

/**
 * Sync revision note rows for one post and block attribute set.
 *
 * @param int   $post_id      Post ID.
 * @param array $attributes   Block attributes.
 * @param array $pending_data Optional unsaved post fields for save-time sync.
 * @return array Synced rows.
 */
function wpc_sync_revision_note_rows_for_post( $post_id, array $attributes, array $pending_data = [] ) {
    $post = get_post( $post_id );

    if ( ! $post instanceof WP_Post ) {
        return [];
    }

    $include_current = ! isset( $attributes['includeCurrentVersion'] ) || $attributes['includeCurrentVersion'];
    $existing_rows   = ! empty( $attributes['rows'] ) && is_array( $attributes['rows'] ) ? $attributes['rows'] : [];
    $date_slots      = wpc_collect_revision_date_slots( $post, $include_current, $pending_data );

    return wpc_sync_revision_note_rows( $existing_rows, $date_slots );
}

/**
 * Recursively sync revision multiline note blocks inside parsed content.
 *
 * @param array $blocks       Parsed block list.
 * @param int   $post_id      Post ID.
 * @param array $pending_data Optional unsaved post fields for save-time sync.
 * @return array Updated block list.
 */
function wpc_sync_revision_blocks_in_parsed_blocks( array $blocks, $post_id, array $pending_data = [] ) {
    foreach ( $blocks as &$block ) {
        if ( ! empty( $block['innerBlocks'] ) ) {
            $block['innerBlocks'] = wpc_sync_revision_blocks_in_parsed_blocks( $block['innerBlocks'], $post_id, $pending_data );
        }

        if ( empty( $block['blockName'] ) || ! in_array( $block['blockName'], wpc_revision_multiline_note_block_names(), true ) ) {
            continue;
        }

        $attrs = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : [];
        $attrs['rows'] = wpc_sync_revision_note_rows_for_post( $post_id, $attrs, $pending_data );
        $block['attrs'] = $attrs;
    }
    unset( $block );

    return $blocks;
}

/**
 * Sample rows for template and pattern previews of the revision note block.
 *
 * @return array Row objects.
 */
function wpc_get_template_preview_revision_rows() {
    return [
        [
            'id'        => 'row-preview-1',
            'date'      => wpc_format_timestamp( time() - DAY_IN_SECONDS ),
            'comment'   => __( 'Initial version', 'wp-changelog' ),
            'author'    => __( 'System', 'wp-changelog' ),
            'changedAt' => time() - DAY_IN_SECONDS,
        ],
        [
            'id'        => 'row-preview-2',
            'date'      => wpc_format_timestamp( time() ),
            'comment'   => '',
            'author'    => __( 'John Doe', 'wp-changelog' ),
            'changedAt' => time(),
        ],
    ];
}
