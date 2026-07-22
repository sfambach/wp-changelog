<?php
/**
 * Server-side rendering for the Change Log block table.
 *
 * @package WP_Changelog
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sort change items inside a merged Change column cell.
 *
 * @param array  $items      Change items with text and changed_at keys.
 * @param string $sort_mode  "time" or "alpha".
 * @param string $sort_order "oldest_first", "newest_first", or legacy asc/desc.
 * @return array Sorted items.
 */
function wpc_sort_change_items( array $items, $sort_mode, $sort_order ) {
    foreach ( $items as $index => &$item ) {
        $item['_wpc_sort_index'] = $index;
    }
    unset( $item );

    $oldest_on_top = ( wpc_normalize_merged_change_order( $sort_order ) === 'oldest_first' );

    usort(
        $items,
        function ( $a, $b ) use ( $sort_mode, $oldest_on_top ) {
            if ( $sort_mode === 'time' ) {
                $a_creation = ! empty( $a['is_creation'] );
                $b_creation = ! empty( $b['is_creation'] );

                if ( $a_creation !== $b_creation ) {
                    if ( $oldest_on_top ) {
                        return $a_creation ? -1 : 1;
                    }

                    return $a_creation ? 1 : -1;
                }

                $cmp = (int) ( $a['changed_at'] ?? 0 ) <=> (int) ( $b['changed_at'] ?? 0 );
            } else {
                $cmp = strcasecmp( wp_strip_all_tags( $a['text'] ), wp_strip_all_tags( $b['text'] ) );
            }

            if ( $cmp === 0 ) {
                $idx_cmp = (int) ( $a['_wpc_sort_index'] ?? 0 ) <=> (int) ( $b['_wpc_sort_index'] ?? 0 );
                $cmp     = $oldest_on_top ? -$idx_cmp : $idx_cmp;
            }

            if ( $oldest_on_top ) {
                return $cmp;
            }

            return -$cmp;
        }
    );

    foreach ( $items as &$item ) {
        unset( $item['_wpc_sort_index'] );
    }
    unset( $item );

    return $items;
}

/**
 * Render one or more change texts as plain lines or a bullet list.
 *
 * Uses the same output rules for single lines, multiline comments, and
 * multiple consolidated changes.
 *
 * @param array  $items      Change items with text and changed_at keys.
 * @param bool   $as_list    When true, always render a bullet list.
 * @param string $sort_mode  "time" or "alpha" (used when multiple items exist).
 * @param string $sort_order "asc" or "desc".
 * @return string HTML for the Change column cell.
 */
function wpc_format_change_comments_html( array $items, $as_list, $sort_mode = 'time', $sort_order = 'asc' ) {
    if ( empty( $items ) ) {
        return '';
    }

    $items = wpc_sort_change_items( $items, $sort_mode, $sort_order );
    $texts = array_column( $items, 'text' );

    if ( $as_list ) {
        $output = '<ul class="wpc-change-list" style="margin:0; padding-left:16px;">';
        foreach ( $texts as $text ) {
            $output .= sprintf( '<li>%s</li>', $text );
        }

        return $output . '</ul>';
    }

    return implode( '<br>', $texts );
}

/**
 * Render a single HTML table row.
 *
 * @param string $date_str     Display date.
 * @param string $comment_html Rendered Change column HTML.
 * @param string $author       Author name.
 * @param bool   $show_author  Whether to include the Author column.
 * @return string HTML table row.
 */
function wpc_render_table_row( $date_str, $comment_html, $author, $show_author ) {
    $output  = '<tr>';
    $output .= sprintf( '<td>%s</td>', $date_str );
    $output .= sprintf( '<td>%s</td>', $comment_html );

    if ( $show_author ) {
        $output .= sprintf( '<td>%s</td>', $author );
    }

    $output .= '</tr>';

    return $output;
}

/**
 * Render all prepared table rows.
 *
 * @param array  $rows                Rows from wpc_build_change_log_table_rows().
 * @param bool   $show_author         Whether to show the Author column.
 * @param bool   $list_changes        Whether changes should render as bullet lists.
 * @param string $change_field_sort   Merged change sort mode: "time" or "alpha".
 * @param string $change_field_order  Merged change sort order: "asc" or "desc".
 * @return string HTML table body rows.
 */
function wpc_render_table_rows( array $rows, $show_author, $list_changes, $change_field_sort, $change_field_order ) {
    $output = '';

    foreach ( $rows as $row ) {
        $comment_html = wpc_format_change_comments_html(
            $row['items'] ?? [],
            $list_changes,
            $change_field_sort,
            $change_field_order
        );

        $output .= wpc_render_table_row(
            $row['date_str'],
            $comment_html,
            $row['author'],
            $show_author
        );
    }

    return $output;
}

/**
 * Render the complete Change Log table markup.
 *
 * @param array $entries    Sorted changelog entries.
 * @param array $attributes Change Log block attributes.
 * @return string Full table HTML wrapped in a container div.
 */
function wpc_render_changelog_table( array $entries, array $attributes ) {
    $show_author        = ! isset( $attributes['showAuthor'] ) || $attributes['showAuthor'];
    $consolidate_dates  = ! empty( $attributes['consolidateDates'] );
    $list_changes       = ! empty( $attributes['listChanges'] );
    $change_field_sort  = ! empty( $attributes['changeFieldSort'] ) ? $attributes['changeFieldSort'] : 'time';
    $change_field_order = ! empty( $attributes['changeFieldOrder'] ) ? $attributes['changeFieldOrder'] : 'newest_first';
    $sort_order         = ! empty( $attributes['sortOrder'] ) ? $attributes['sortOrder'] : 'desc';
    $wrapper_class      = wpc_get_wrapper_classes( $attributes );
    $table_class        = trim( 'wpc-change-log-table ' . wpc_get_table_classes( $attributes ) );
    $rows               = wpc_build_change_log_table_rows( $entries, $consolidate_dates );

    if ( $consolidate_dates ) {
        $rows = wpc_sort_consolidated_rows( $rows, $sort_order );
    }

    $output  = '<figure class="' . esc_attr( $wrapper_class ) . '">';
    $output .= '<table class="' . esc_attr( $table_class ) . '">';
    $output .= '<thead><tr>';
    $output .= '<th>' . esc_html__( 'Date', 'wp-changelog' ) . '</th>';
    $output .= '<th>' . esc_html__( 'Change', 'wp-changelog' ) . '</th>';

    if ( $show_author ) {
        $output .= '<th>' . esc_html__( 'Author', 'wp-changelog' ) . '</th>';
    }

    $output .= '</tr></thead><tbody>';
    $output .= wpc_render_table_rows( $rows, $show_author, $list_changes, $change_field_sort, $change_field_order );
    $output .= '</tbody></table></figure>';

    return $output;
}

/**
 * Render the Change Log table for one saved post.
 *
 * @param WP_Post $post                Post object.
 * @param array   $attributes          Change Log display attributes.
 * @param bool    $is_template_preview Whether dummy preview data should be used.
 * @return string HTML table markup or empty string.
 */
function wpc_render_change_log_for_post( WP_Post $post, array $attributes, $is_template_preview = false ) {
    $sort_order = ! empty( $attributes['sortOrder'] ) ? $attributes['sortOrder'] : 'desc';
    $entries    = wpc_collect_changelog_entries( $post, $is_template_preview );
    $entries    = wpc_sort_changelog_entries( $entries, $sort_order );

    return wpc_render_changelog_table( $entries, $attributes );
}

/**
 * Block render callback for wpc/change-log and legacy wpc/change-table.
 *
 * @param array  $attributes Block attributes from the editor.
 * @param string $content    Saved block content (unused; rendering is dynamic).
 * @return string Rendered HTML or editor notice when the post is unsaved.
 */
function wpc_render_change_log( $attributes, $content ) {
    $attributes = wpc_normalize_block_attributes( $attributes );

    if ( ! wpc_is_change_log_visible_on_page( $attributes ) ) {
        return '';
    }

    $post_id = wpc_get_render_post_id();

    if ( ! $post_id ) {
        return '<p style="padding:10px; background:#fff3cd;">' . esc_html__( 'Please save the post as a draft to load the history.', 'wp-changelog' ) . '</p>';
    }

    $post                = get_post( $post_id );
    $is_template_preview = wpc_is_template_preview( $post, $post_id );

    if ( ! $post && ! $is_template_preview ) {
        return '<p style="padding:10px; background:#fff3cd;">' . esc_html__( 'Please save the post as a draft to load the history.', 'wp-changelog' ) . '</p>';
    }

    return wpc_render_change_log_for_post( $post, $attributes, $is_template_preview );
}
