<?php
/**
 * Collect, normalize, sort, and consolidate changelog entries.
 *
 * @package WPChangelog
 */

namespace WPChangelog;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parses change notes from post content and prepares table rows.
 */
class Collectors {

	/**
	 * Register collectors hooks.
	 *
	 * @return void
	 */
	public function register() {
		/** @see https://developer.wordpress.org/reference/hooks/wp_insert_post_data/ */
		add_filter( 'wp_insert_post_data', [ $this, 'sort_multi_note_rows_on_save' ], 20, 2 );
	}

	/**
	 * Normalize raw note fields into a sanitized changelog entry.
	 *
	 * @param string $date_str   Display date (d.m.Y).
	 * @param string $comment    Change description.
	 * @param string $author     Author display name.
	 * @param int    $changed_at Unix timestamp of the last edit, or 0 to derive from date.
	 * @return array
	 */
	public function parse_change_row( $date_str, $comment, $author, $changed_at = 0 ) {
		$date_str   = ! empty( $date_str ) ? esc_html( $date_str ) : '-';
		$comment    = esc_html( $comment );
		$author     = ! empty( $author ) ? esc_html( $author ) : __( 'Unknown', 'wp-changelog' );
		$changed_at = intval( $changed_at );

		if ( $changed_at <= 0 ) {
			$changed_at = Helpers::parse_date_to_timestamp( $date_str );
		}

		return [
			'timestamp'  => Helpers::parse_date_to_timestamp( $date_str ),
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
	 * @return array
	 */
	public function parse_single_change_note_block( array $block ) {
		return $this->parse_change_row(
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
	 * @return array
	 */
	public function parse_multi_change_note_row( array $row ) {
		return $this->parse_change_row(
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
	 * @return array
	 */
	public function sort_entries_by_date( array $entries ) {
		usort(
			$entries,
			static function ( $a, $b ) {
				$date_cmp = Helpers::parse_date_to_timestamp( $a['date_str'] ) <=> Helpers::parse_date_to_timestamp( $b['date_str'] );
				if ( $date_cmp !== 0 ) {
					return $date_cmp;
				}

				return Helpers::get_entry_sort_timestamp( $a ) <=> Helpers::get_entry_sort_timestamp( $b );
			}
		);

		return $entries;
	}

	/**
	 * Collect all rows from Multi Change Note blocks in parsed content.
	 *
	 * @param array $blocks Parsed block list.
	 * @return array
	 */
	public function collect_multi_change_note_items( array $blocks ) {
		$items = [];
		$names = [ Helpers::BLOCK_MULTI_CHANGE_NOTE, Helpers::LEGACY_BLOCK_MULTI_CHANGE_NOTE ];

		foreach ( Helpers::find_blocks_by_names( $blocks, $names ) as $block ) {
			$rows = ! empty( $block['attrs']['rows'] ) && is_array( $block['attrs']['rows'] ) ? $block['attrs']['rows'] : [];

			foreach ( $rows as $row ) {
				if ( empty( trim( $row['comment'] ?? '' ) ) ) {
					continue;
				}

				$items[] = $this->parse_multi_change_note_row( $row );
			}
		}

		return $this->sort_entries_by_date( $items );
	}

	/**
	 * Collect all change notes from post content (single and multi blocks).
	 *
	 * @param string $post_content Raw post content.
	 * @return array
	 */
	public function collect_change_items( $post_content ) {
		$blocks       = parse_blocks( $post_content );
		$change_items = [];
		$single_names = [ Helpers::BLOCK_SINGLE_CHANGE_NOTE, Helpers::LEGACY_BLOCK_SINGLE_CHANGE_NOTE ];

		foreach ( Helpers::find_blocks_by_names( $blocks, $single_names ) as $block ) {
			if ( empty( trim( $block['attrs']['comment'] ?? '' ) ) ) {
				continue;
			}

			$change_items[] = $this->parse_single_change_note_block( $block );
		}

		$change_items = array_merge( $change_items, $this->collect_multi_change_note_items( $blocks ) );

		return $this->sort_entries_by_date( $change_items );
	}

	/**
	 * Build the synthetic "Post created" changelog entry.
	 *
	 * @param WP_Post $post         Post object.
	 * @param array   $change_items Already collected changelog entries.
	 * @return array
	 */
	public function build_creation_entry( WP_Post $post, array $change_items = [] ) {
		$earliest = 0;

		foreach ( $change_items as $item ) {
			$ts = isset( $item['timestamp'] ) ? (int) $item['timestamp'] : 0;
			if ( $ts > 0 && ( 0 === $earliest || $ts < $earliest ) ) {
				$earliest = $ts;
			}
		}

		if ( ! $earliest ) {
			$earliest = Helpers::get_post_earliest_version_timestamp( $post );
		}

		$author_obj = get_user_by( 'id', $post->post_author );

		return [
			'timestamp'   => $earliest,
			'changed_at'  => $earliest,
			'date_str'    => Helpers::format_timestamp( $earliest ),
			'comment'     => '<strong>' . esc_html__( 'Post created', 'wp-changelog' ) . '</strong>',
			'author'      => $author_obj ? $author_obj->display_name : __( 'System', 'wp-changelog' ),
			'is_creation' => true,
		];
	}

	/**
	 * Sample entries shown when previewing inside block patterns or templates.
	 *
	 * @return array
	 */
	public function get_template_preview_entries() {
		return [
			[
				'timestamp'   => time() - DAY_IN_SECONDS,
				'changed_at'  => time() - DAY_IN_SECONDS,
				'date_str'    => Helpers::format_timestamp( time() - DAY_IN_SECONDS ),
				'comment'     => '<strong>' . esc_html__( 'Post created', 'wp-changelog' ) . '</strong>',
				'author'      => __( 'System', 'wp-changelog' ),
				'is_creation' => true,
			],
			[
				'timestamp'  => time(),
				'changed_at' => time(),
				'date_str'   => Helpers::format_timestamp( time() ),
				'comment'    => esc_html__( 'Example: Replaced header image and updated text layout.', 'wp-changelog' ),
				'author'     => __( 'John Doe', 'wp-changelog' ),
			],
		];
	}

	/**
	 * Build the full entry list for a post, including the creation row.
	 *
	 * @param WP_Post|null $post                Post object.
	 * @param bool         $is_template_preview Whether dummy preview data should be returned.
	 * @return array
	 */
	public function collect_changelog_entries( $post, $is_template_preview ) {
		if ( $is_template_preview ) {
			return $this->get_template_preview_entries();
		}

		$change_items   = $this->collect_change_items( $post->post_content );
		$creation_entry = $this->build_creation_entry( $post, $change_items );

		return array_merge( [ $creation_entry ], $change_items );
	}

	/**
	 * Sort changelog entries by display date for the table Sort Order setting.
	 *
	 * @param array  $entries    Changelog entries.
	 * @param string $sort_order "asc" or "desc".
	 * @return array
	 */
	public function sort_changelog_entries( array $entries, $sort_order ) {
		$sort_order = ( 'asc' === $sort_order ) ? 'asc' : 'desc';

		usort(
			$entries,
			static function ( $a, $b ) use ( $sort_order ) {
				$a_ts = isset( $a['timestamp'] ) ? (int) $a['timestamp'] : Helpers::parse_date_to_timestamp( $a['date_str'] ?? '' );
				$b_ts = isset( $b['timestamp'] ) ? (int) $b['timestamp'] : Helpers::parse_date_to_timestamp( $b['date_str'] ?? '' );

				if ( $a_ts === $b_ts ) {
					$cmp = Helpers::get_entry_sort_timestamp( $a ) <=> Helpers::get_entry_sort_timestamp( $b );
				} else {
					$cmp = $a_ts <=> $b_ts;
				}

				return 'asc' === $sort_order ? $cmp : -$cmp;
			}
		);

		return $entries;
	}

	/**
	 * Sort consolidated table rows by display date.
	 *
	 * @param array  $rows       Consolidated rows with date_str, author, and items.
	 * @param string $sort_order "asc" or "desc".
	 * @return array
	 */
	public function sort_consolidated_rows( array $rows, $sort_order ) {
		$sort_order = ( 'asc' === $sort_order ) ? 'asc' : 'desc';

		usort(
			$rows,
			static function ( $a, $b ) use ( $sort_order ) {
				$a_ts = Helpers::parse_date_to_timestamp( $a['date_str'] ?? '' );
				$b_ts = Helpers::parse_date_to_timestamp( $b['date_str'] ?? '' );
				$cmp  = $a_ts <=> $b_ts;

				if ( 0 === $cmp ) {
					$a_items   = $a['items'] ?? [];
					$b_items   = $b['items'] ?? [];
					$a_changed = 0;
					$b_changed = 0;
					foreach ( $a_items as $item ) {
						$a_changed = max( $a_changed, (int) ( $item['changed_at'] ?? 0 ) );
					}
					foreach ( $b_items as $item ) {
						$b_changed = max( $b_changed, (int) ( $item['changed_at'] ?? 0 ) );
					}
					$cmp = $a_changed <=> $b_changed;
				}

				return 'asc' === $sort_order ? $cmp : -$cmp;
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
	 * @return array
	 */
	public function make_change_item( $comment, $changed_at, $is_creation = false ) {
		return [
			'text'        => $comment,
			'changed_at'  => (int) $changed_at,
			'is_creation' => (bool) $is_creation,
		];
	}

	/**
	 * Split a multiline comment into multiple change items.
	 *
	 * @param string $comment     Raw comment text.
	 * @param int    $changed_at  Sort timestamp applied to each derived item.
	 * @param bool   $is_creation Whether the source entry is the post-created row.
	 * @return array
	 */
	public function expand_comment_to_change_items( $comment, $changed_at, $is_creation = false ) {
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

				$items[] = $this->make_change_item( esc_html( $line ), $changed_at, $is_creation );
			}

			return $items;
		}

		return [ $this->make_change_item( $comment, $changed_at, $is_creation ) ];
	}

	/**
	 * Convert a full changelog entry into renderable change items.
	 *
	 * @param array $entry Changelog entry from collectors.
	 * @return array
	 */
	public function entry_to_change_items( array $entry ) {
		return $this->expand_comment_to_change_items(
			$entry['comment'] ?? '',
			Helpers::get_entry_sort_timestamp( $entry ),
			! empty( $entry['is_creation'] )
		);
	}

	/**
	 * Merge entries that share the same display date and author into single rows.
	 *
	 * @param array $entries Sorted changelog entries.
	 * @return array
	 */
	public function consolidate_entries_by_date_and_author( array $entries ) {
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

			foreach ( $this->entry_to_change_items( $entry ) as $item ) {
				$consolidated[ $group_key ]['items'][] = $item;
			}
		}

		return $consolidated;
	}

	/**
	 * Prepare normalized table rows for rendering.
	 *
	 * @param array $entries           Sorted changelog entries.
	 * @param bool  $consolidate_dates Whether rows should be merged by date and author.
	 * @return array
	 */
	public function build_change_log_table_rows( array $entries, $consolidate_dates ) {
		if ( $consolidate_dates ) {
			return $this->consolidate_entries_by_date_and_author( $entries );
		}

		$rows = [];

		foreach ( $entries as $entry ) {
			$rows[] = [
				'date_str' => $entry['date_str'],
				'author'   => $entry['author'],
				'items'    => $this->entry_to_change_items( $entry ),
			];
		}

		return $rows;
	}

	/**
	 * Format changelog entries as plain multiline text for export blocks.
	 *
	 * @param array $entries     Sorted changelog entries.
	 * @param bool  $show_author Whether to append the author column.
	 * @return string
	 */
	public function format_entries_as_multiline_text( array $entries, $show_author ) {
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
	 * Sort Log-Liste rows by date/comment/author using asc/desc order.
	 *
	 * @param array  $rows       Row objects from multi-change-note attributes.
	 * @param string $sort_field "date", "comment", or "author".
	 * @param string $sort_order "asc" or "desc".
	 * @return array
	 */
	public function sort_multi_note_rows( array $rows, $sort_field = 'date', $sort_order = 'desc' ) {
		$sort_field = in_array( $sort_field, [ 'date', 'comment', 'author' ], true ) ? $sort_field : 'date';
		$sort_order = $sort_order === 'asc' ? 'asc' : 'desc';

		usort(
			$rows,
			static function ( $a, $b ) use ( $sort_field, $sort_order ) {
				switch ( $sort_field ) {
					case 'comment':
						$cmp = strcasecmp( (string) ( $a['comment'] ?? '' ), (string) ( $b['comment'] ?? '' ) );
						break;
					case 'author':
						$cmp = strcasecmp( (string) ( $a['author'] ?? '' ), (string) ( $b['author'] ?? '' ) );
						break;
					case 'date':
					default:
						$cmp = Helpers::parse_date_to_timestamp( $a['date'] ?? '' ) <=> Helpers::parse_date_to_timestamp( $b['date'] ?? '' );
						break;
				}

				if ( $cmp === 0 ) {
					$cmp = (int) ( $a['changedAt'] ?? 0 ) <=> (int) ( $b['changedAt'] ?? 0 );
				}

				return $sort_order === 'asc' ? $cmp : -$cmp;
			}
		);

		return $rows;
	}

	/**
	 * Recursively sort Multi Change Note rows inside parsed blocks.
	 *
	 * @param array $blocks Parsed block list.
	 * @return array{blocks: array, changed: bool}
	 */
	public function sort_multi_note_blocks_in_parsed_blocks( array $blocks ) {
		$changed = false;

		foreach ( $blocks as &$block ) {
			if ( ! empty( $block['innerBlocks'] ) ) {
				$inner                = $this->sort_multi_note_blocks_in_parsed_blocks( $block['innerBlocks'] );
				$block['innerBlocks'] = $inner['blocks'];
				$changed              = $changed || $inner['changed'];
			}

			if ( empty( $block['blockName'] ) || ! in_array( $block['blockName'], Helpers::multi_change_note_block_names(), true ) ) {
				continue;
			}

			$attrs = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : [];
			$rows  = ! empty( $attrs['rows'] ) && is_array( $attrs['rows'] ) ? $attrs['rows'] : [];

			if ( empty( $rows ) ) {
				continue;
			}

			$sort_field = ! empty( $attrs['sortField'] ) ? $attrs['sortField'] : 'date';
			$sort_order = ! empty( $attrs['sortOrder'] ) ? $attrs['sortOrder'] : 'desc';
			$sorted     = $this->sort_multi_note_rows( $rows, $sort_field, $sort_order );
			$ids        = array_values(
				array_map(
					static function ( $row ) {
						return is_array( $row ) ? ( $row['id'] ?? '' ) : '';
					},
					$rows
				)
			);
			$sorted_ids = array_values(
				array_map(
					static function ( $row ) {
						return is_array( $row ) ? ( $row['id'] ?? '' ) : '';
					},
					$sorted
				)
			);

			if ( $ids !== $sorted_ids ) {
				$attrs['rows']  = $sorted;
				$block['attrs'] = $attrs;
				$changed        = true;
			}
		}
		unset( $block );

		return [
			'blocks'  => $blocks,
			'changed' => $changed,
		];
	}

	/**
	 * Persist Log-Liste row order when a post is saved.
	 *
	 * @param array $data    Sanitized post data.
	 * @param array $postarr Raw post data.
	 * @return array
	 */
	public function sort_multi_note_rows_on_save( $data, $postarr ) {
		if ( empty( $data['post_content'] ) || ! is_string( $data['post_content'] ) ) {
			return $data;
		}

		if ( ! has_blocks( $data['post_content'] ) ) {
			return $data;
		}

		if (
			false === strpos( $data['post_content'], 'wp:wpc/multi-change-note' )
			&& false === strpos( $data['post_content'], 'wp:wpc/multi-note' )
		) {
			return $data;
		}

		$result = $this->sort_multi_note_blocks_in_parsed_blocks( parse_blocks( $data['post_content'] ) );

		if ( $result['changed'] ) {
			$data['post_content'] = serialize_blocks( $result['blocks'] );
		}

		return $data;
	}
}
