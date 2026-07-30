<?php
/**
 * Server-side rendering for the Change Log block table.
 *
 * @package WPChangelog
 */

namespace WPChangelog;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders Change Log HTML from collected entries.
 */
class Renderer {

	/**
	 * Sort change items inside a merged Change column cell.
	 *
	 * @param array  $items      Change items with text and changed_at keys.
	 * @param string $sort_mode  "time" or "alpha".
	 * @param string $sort_order "oldest_first", "newest_first", or legacy asc/desc.
	 * @return array
	 */
	public function sort_change_items( array $items, $sort_mode, $sort_order ) {
		foreach ( $items as $index => &$item ) {
			$item['_wpc_sort_index'] = $index;
		}
		unset( $item );

		$oldest_on_top = ( Helpers::normalize_merged_change_order( $sort_order ) === 'oldest_first' );

		usort(
			$items,
			static function ( $a, $b ) use ( $sort_mode, $oldest_on_top ) {
				$a_creation = ! empty( $a['is_creation'] );
				$b_creation = ! empty( $b['is_creation'] );

				if ( $a_creation !== $b_creation ) {
					return $a_creation === $oldest_on_top ? -1 : 1;
				}

				if ( $sort_mode === 'time' ) {
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
	 * @param array  $items      Change items with text and changed_at keys.
	 * @param bool   $as_list    When true, always render a bullet list.
	 * @param string $sort_mode  "time" or "alpha".
	 * @param string $sort_order Merged change sort order.
	 * @return string
	 */
	public function format_change_comments_html( array $items, $as_list, $sort_mode = 'time', $sort_order = 'asc' ) {
		if ( empty( $items ) ) {
			return '';
		}

		$items = $this->sort_change_items( $items, $sort_mode, $sort_order );
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
	 * @return string
	 */
	public function render_table_row( $date_str, $comment_html, $author, $show_author ) {
		$output  = '<tr>';
		$output .= sprintf( '<td class="wpc-changelog-col-date">%s</td>', $date_str );
		$output .= sprintf( '<td class="wpc-changelog-col-change">%s</td>', $comment_html );

		if ( $show_author ) {
			$output .= sprintf( '<td class="wpc-changelog-col-author">%s</td>', $author );
		}

		$output .= '</tr>';

		return $output;
	}

	/**
	 * Render all prepared table rows.
	 *
	 * @param array  $rows               Rows from Collectors::build_change_log_table_rows().
	 * @param bool   $show_author        Whether to show the Author column.
	 * @param bool   $list_changes       Whether changes should render as bullet lists.
	 * @param string $change_field_sort  Merged change sort mode: "time" or "alpha".
	 * @param string $change_field_order Merged change sort order.
	 * @return string
	 */
	public function render_table_rows( array $rows, $show_author, $list_changes, $change_field_sort, $change_field_order ) {
		$output = '';

		foreach ( $rows as $row ) {
			$comment_html = $this->format_change_comments_html(
				$row['items'] ?? [],
				$list_changes,
				$change_field_sort,
				$change_field_order
			);

			$output .= $this->render_table_row(
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
	 * @return string
	 */
	public function render_changelog_table( array $entries, array $attributes ) {
		$collectors         = Plugin::instance()->collectors();
		$show_author        = ! isset( $attributes['showAuthor'] ) || $attributes['showAuthor'];
		$consolidate_dates  = ! empty( $attributes['consolidateDates'] );
		$list_changes       = ! empty( $attributes['listChanges'] );
		$change_field_sort  = ! empty( $attributes['changeFieldSort'] ) ? $attributes['changeFieldSort'] : 'time';
		$change_field_order = ! empty( $attributes['changeFieldOrder'] ) ? $attributes['changeFieldOrder'] : 'newest_first';
		$sort_order         = ! empty( $attributes['sortOrder'] ) ? $attributes['sortOrder'] : 'desc';
		$show_caption       = ! isset( $attributes['showCaption'] ) || ! empty( $attributes['showCaption'] );
		$caption            = array_key_exists( 'caption', $attributes )
			? trim( (string) $attributes['caption'] )
			: '';
		$wrapper_class      = Helpers::get_wrapper_classes( $attributes );
		$table_class        = trim( 'wpc-change-log-table ' . Helpers::get_table_classes( $attributes ) );
		$rows               = $collectors->build_change_log_table_rows( $entries, $consolidate_dates );
		$rows               = $collectors->sort_consolidated_rows( $rows, $sort_order );

		if ( $show_caption && '' === $caption ) {
			$caption = Helpers::get_default_table_caption();
		}

		$body_rows = $this->render_table_rows( $rows, $show_author, $list_changes, $change_field_sort, $change_field_order );

		return Helpers::render_template(
			'change-log-table',
			[
				'table_class'    => $table_class,
				'wrapper_class'  => $wrapper_class,
				'show_author'    => $show_author,
				'show_caption'   => $show_caption,
				'caption'        => $caption,
				'body_rows'      => $body_rows,
				'editor_preview' => ! empty( $attributes['editorPreview'] ),
			]
		);
	}

	/**
	 * Render the Change Log table for one saved post.
	 *
	 * @param WP_Post $post                Post object.
	 * @param array   $attributes          Change Log display attributes.
	 * @param bool    $is_template_preview Whether dummy preview data should be used.
	 * @return string
	 */
	public function render_change_log_for_post( WP_Post $post, array $attributes, $is_template_preview = false ) {
		$collectors = Plugin::instance()->collectors();
		$sort_order = ! empty( $attributes['sortOrder'] ) ? $attributes['sortOrder'] : 'desc';
		$entries    = $collectors->collect_changelog_entries( $post, $is_template_preview );
		$entries    = $collectors->sort_changelog_entries( $entries, $sort_order );

		return $this->render_changelog_table( $entries, $attributes );
	}

	/**
	 * Block render callback for wpc/change-log and legacy wpc/change-table.
	 *
	 * @param array  $attributes Block attributes from the editor.
	 * @param string $content    Saved block content (unused; rendering is dynamic).
	 * @return string
	 */
	public function render_change_log( $attributes, $content ) {
		$attributes = Helpers::normalize_block_attributes( $attributes );

		if ( ! Helpers::is_change_log_visible_on_page( $attributes ) ) {
			return '';
		}

		$post_id = Helpers::get_render_post_id();

		if ( ! $post_id ) {
			return '<p style="padding:10px; background:#fff3cd;">' . esc_html__( 'Please save the post as a draft to load the history.', 'wp-changelog' ) . '</p>';
		}

		$post                = get_post( $post_id );
		$is_template_preview = Helpers::is_template_preview( $post, $post_id );

		if ( ! $post && ! $is_template_preview ) {
			return '<p style="padding:10px; background:#fff3cd;">' . esc_html__( 'Please save the post as a draft to load the history.', 'wp-changelog' ) . '</p>';
		}

		return $this->render_change_log_for_post( $post, $attributes, $is_template_preview );
	}
}
