<?php
/**
 * Change Log table markup template.
 *
 * Expected vars: $table_class, $wrapper_class, $show_author, $show_caption,
 * $caption, $body_rows, $editor_preview.
 *
 * @package WPChangelog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$table  = '<table class="' . esc_attr( $table_class ) . '">';
$table .= '<thead><tr>';
$table .= '<th class="wpc-changelog-col-date">' . esc_html__( 'Date', 'wp-changelog' ) . '</th>';
$table .= '<th class="wpc-changelog-col-change">' . esc_html__( 'Change', 'wp-changelog' ) . '</th>';

if ( $show_author ) {
	$table .= '<th class="wpc-changelog-col-author">' . esc_html__( 'Author', 'wp-changelog' ) . '</th>';
}

$table .= '</tr></thead><tbody>';
$table .= $body_rows; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped cells.
$table .= '</tbody></table>';

if ( ! empty( $editor_preview ) ) {
	echo $table; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

$output  = '<figure class="' . esc_attr( $wrapper_class ) . '">';
$output .= $table;
if ( $show_caption && '' !== $caption ) {
	$output .= '<figcaption class="wp-block-table__caption">' . esc_html( $caption ) . '</figcaption>';
}
$output .= '</figure>';

echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
