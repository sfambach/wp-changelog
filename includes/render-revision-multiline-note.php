<?php

/**

 * Server-side rendering for the Revision Multiline Note block.

 *

 * @package WP_Changelog

 */



if ( ! defined( 'ABSPATH' ) ) {

    exit;

}



/**

 * Render one editable revision note row as a table row.

 *

 * @param array $row         Row object from block attributes.

 * @param bool  $show_author Whether to render the Author column.

 * @return string HTML table row markup.

 */

function wpc_render_revision_note_row( array $row, $show_author ) {

    $output  = '<tr>';

    $output .= '<td>' . esc_html( $row['date'] ?? '' ) . '</td>';

    $output .= '<td>' . esc_html( $row['comment'] ?? '' ) . '</td>';



    if ( $show_author ) {

        $output .= '<td>' . esc_html( $row['author'] ?? '' ) . '</td>';

    }



    $output .= '</tr>';



    return $output;

}



/**

 * Render the revision note table from stored rows.

 *

 * @param array $rows        Sorted row objects.

 * @param bool  $show_author Whether to render the Author column.

 * @return string HTML table markup.

 */

function wpc_render_revision_multiline_note_table( array $rows, $show_author ) {

    $output  = '<div class="wpc-multi-note-wrap wpc-revision-multiline-note">';

    $output .= '<table class="wpc-multi-note-table">';

    $output .= '<thead><tr>';

    $output .= '<th style="width:90px">' . esc_html__( 'Date', 'wp-changelog' ) . '</th>';

    $output .= '<th>' . esc_html__( 'Change', 'wp-changelog' ) . '</th>';



    if ( $show_author ) {

        $output .= '<th style="width:110px">' . esc_html__( 'Author', 'wp-changelog' ) . '</th>';

    }



    $output .= '</tr></thead><tbody>';



    foreach ( $rows as $row ) {

        if ( ! is_array( $row ) ) {

            continue;

        }



        $output .= wpc_render_revision_note_row( $row, $show_author );

    }



    $output .= '</tbody></table></div>';



    return $output;

}



/**

 * Block render callback for wpc/revision-multiline-note.

 *

 * Renders editable revision-synced rows stored in block attributes.

 *

 * @param array  $attributes Block attributes from the editor.

 * @param string $content    Saved block content (unused).

 * @return string Frontend HTML.

 */

function wpc_render_revision_multiline_note( $attributes, $content ) {

    $attributes = wpc_normalize_block_attributes( $attributes );

    // Input surface for the Change Log — never output on the public site.
    if ( ! wpc_is_change_log_editor_context() ) {
        return '';
    }

    $post_id    = wpc_get_render_post_id();



    if ( ! $post_id ) {

        return '<p class="wpc-revision-multiline-note-notice">' . esc_html__( 'Please save the post as a draft to load the revisions.', 'wp-changelog' ) . '</p>';

    }



    $post                = get_post( $post_id );

    $is_template_preview = wpc_is_template_preview( $post, $post_id );



    if ( $is_template_preview ) {

        $rows = wpc_get_template_preview_revision_rows();

    } else {

        if ( ! $post ) {

            return '<p class="wpc-revision-multiline-note-notice">' . esc_html__( 'Please save the post as a draft to load the revisions.', 'wp-changelog' ) . '</p>';

        }



        $rows = wpc_sync_revision_note_rows_for_post( $post_id, $attributes );

    }



    if ( empty( $rows ) ) {

        return '<p class="wpc-revision-multiline-note-notice">' . esc_html__( 'No post revisions found yet. Save the post to create revisions.', 'wp-changelog' ) . '</p>';

    }



    $sort_field  = ! empty( $attributes['sortField'] ) ? $attributes['sortField'] : 'date';

    $sort_order  = ! empty( $attributes['sortOrder'] ) ? $attributes['sortOrder'] : 'desc';

    $show_author = ! isset( $attributes['showAuthor'] ) || $attributes['showAuthor'];

    $rows        = wpc_sort_revision_note_rows( $rows, $sort_field, $sort_order );



    return wpc_render_revision_multiline_note_table( $rows, $show_author );

}



/**

 * Legacy render callback alias for wpc/version-multiline-note.

 *

 * @param array  $attributes Block attributes from the editor.

 * @param string $content    Saved block content (unused).

 * @return string Frontend HTML.

 */

function wpc_render_version_multiline_note( $attributes, $content ) {

    return wpc_render_revision_multiline_note( $attributes, $content );

}



/**

 * Legacy render callback alias for wpc/generated-multiline-note.

 *

 * @param array  $attributes Block attributes from the editor.

 * @param string $content    Saved block content (unused).

 * @return string Frontend HTML.

 */

function wpc_render_generated_multiline_note( $attributes, $content ) {

    return wpc_render_revision_multiline_note( $attributes, $content );

}


