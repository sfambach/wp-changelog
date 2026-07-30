<?php
/**
 * Settings page template.
 *
 * Expected vars: $settings, $option_key, $post_types, $settings_ui (Settings instance).
 *
 * @package WPChangelog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap">
	<h1><?php echo esc_html__( 'Change Log Settings', 'wp-changelog' ); ?></h1>
	<p><?php echo esc_html__( 'Automatically append the Change Log table to supported content types. Manual Change Log blocks in the editor are unchanged and take precedence on individual pages.', 'wp-changelog' ); ?></p>

	<form method="post" action="options.php">
		<?php settings_fields( 'wpc_global_change_log' ); ?>

		<h2><?php echo esc_html__( 'Global integration', 'wp-changelog' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php
			$settings_ui->render_settings_field(
				__( 'Enable on all configured pages', 'wp-changelog' ),
				'<label><input type="checkbox" name="' . esc_attr( $option_key ) . '[enabled]" value="1" ' . checked( ! empty( $settings['enabled'] ), true, false ) . ' /> ' . esc_html__( 'Append the Change Log table automatically', 'wp-changelog' ) . '</label>',
				__( 'When enabled, the table is added to the end of the content on the selected post types unless a Change Log block is already present.', 'wp-changelog' )
			);

			$post_type_fields = '';
			foreach ( $post_types as $post_type => $object ) {
				$checked           = in_array( $post_type, (array) $settings['post_types'], true ) ? 'checked="checked"' : '';
				$post_type_fields .= '<label style="display:block;margin-bottom:4px;">';
				$post_type_fields .= '<input type="checkbox" name="' . esc_attr( $option_key ) . '[post_types][]" value="' . esc_attr( $post_type ) . '" ' . $checked . ' /> ';
				$post_type_fields .= esc_html( $object->labels->singular_name ) . ' <code>' . esc_html( $post_type ) . '</code>';
				$post_type_fields .= '</label>';
			}

			$settings_ui->render_settings_field(
				__( 'Post types', 'wp-changelog' ),
				$post_type_fields,
				__( 'Choose which public content types should receive the global Change Log.', 'wp-changelog' )
			);
			?>
		</table>

		<h2><?php echo esc_html__( 'Editor shortcut', 'wp-changelog' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php
			$settings_ui->render_settings_field(
				__( 'Create note from prefix', 'wp-changelog' ),
				'<label><input type="checkbox" name="' . esc_attr( $option_key ) . '[shortcutEnabled]" value="1" ' . checked( ! empty( $settings['shortcutEnabled'] ), true, false ) . ' /> ' . esc_html__( 'Convert typed prefix into a Single Change Note', 'wp-changelog' ) . '</label>',
				__( 'In a paragraph, type the prefix and a space (e.g. “#log Fixed typo”). WordPress turns that into a Single Change Note; the text after the prefix becomes the change description.', 'wp-changelog' )
			);

			$settings_ui->render_settings_field(
				__( 'Prefix', 'wp-changelog' ),
				'<input type="text" class="regular-text" name="' . esc_attr( $option_key ) . '[shortcutPrefix]" value="' . esc_attr( $settings['shortcutPrefix'] ?? '#log' ) . '" />',
				__( 'Case-sensitive. Default: #log', 'wp-changelog' )
			);
			?>
		</table>

		<h2><?php echo esc_html__( 'Table display', 'wp-changelog' ); ?></h2>
		<p class="description"><?php echo esc_html__( 'These options mirror the Change Log block sidebar settings.', 'wp-changelog' ); ?></p>
		<table class="form-table" role="presentation">
			<?php
			$settings_ui->render_settings_field(
				__( 'Sort Order', 'wp-changelog' ),
				'<select name="' . esc_attr( $option_key ) . '[sortOrder]">
					<option value="desc"' . selected( $settings['sortOrder'], 'desc', false ) . '>' . esc_html__( 'Newest on top', 'wp-changelog' ) . '</option>
					<option value="asc"' . selected( $settings['sortOrder'], 'asc', false ) . '>' . esc_html__( 'Oldest on top', 'wp-changelog' ) . '</option>
				</select>',
				__( 'Which date row appears at the top of the table.', 'wp-changelog' )
			);

			$settings_ui->render_settings_field(
				__( 'Visible on page', 'wp-changelog' ),
				'<label><input type="checkbox" name="' . esc_attr( $option_key ) . '[visibleOnPage]" value="1" ' . checked( ! empty( $settings['visibleOnPage'] ), true, false ) . ' /> ' . esc_html__( 'Show on the public site', 'wp-changelog' ) . '</label>',
				__( 'When off, the global table is hidden on the public site. It is still available in wp-admin previews.', 'wp-changelog' )
			);

			$settings_ui->render_settings_field(
				__( 'Show Author', 'wp-changelog' ),
				'<label><input type="checkbox" name="' . esc_attr( $option_key ) . '[showAuthor]" value="1" ' . checked( ! empty( $settings['showAuthor'] ), true, false ) . ' /> ' . esc_html__( 'Display the Author column', 'wp-changelog' ) . '</label>'
			);

			$settings_ui->render_settings_field(
				__( 'Fixed width table cells', 'wp-changelog' ),
				'<label><input type="checkbox" name="' . esc_attr( $option_key ) . '[hasFixedLayout]" value="1" ' . checked( ! empty( $settings['hasFixedLayout'] ), true, false ) . ' /> ' . esc_html__( 'Use fixed table layout', 'wp-changelog' ) . '</label>'
			);

			$settings_ui->render_settings_field(
				__( 'Table style', 'wp-changelog' ),
				'<select name="' . esc_attr( $option_key ) . '[tableStyle]">
					<option value="default"' . selected( $settings['tableStyle'] ?? 'default', 'default', false ) . '>' . esc_html__( 'Default', 'wp-changelog' ) . '</option>
					<option value="stripes"' . selected( $settings['tableStyle'] ?? 'default', 'stripes', false ) . '>' . esc_html__( 'Stripes', 'wp-changelog' ) . '</option>
				</select>'
			);

			$settings_ui->render_settings_field(
				__( 'Table alignment', 'wp-changelog' ),
				'<select name="' . esc_attr( $option_key ) . '[align]">
					<option value=""' . selected( $settings['align'], '', false ) . '>' . esc_html__( 'Default', 'wp-changelog' ) . '</option>
					<option value="left"' . selected( $settings['align'], 'left', false ) . '>' . esc_html__( 'Left', 'wp-changelog' ) . '</option>
					<option value="center"' . selected( $settings['align'], 'center', false ) . '>' . esc_html__( 'Center', 'wp-changelog' ) . '</option>
					<option value="right"' . selected( $settings['align'], 'right', false ) . '>' . esc_html__( 'Right', 'wp-changelog' ) . '</option>
					<option value="wide"' . selected( $settings['align'], 'wide', false ) . '>' . esc_html__( 'Wide', 'wp-changelog' ) . '</option>
					<option value="full"' . selected( $settings['align'], 'full', false ) . '>' . esc_html__( 'Full width', 'wp-changelog' ) . '</option>
				</select>'
			);

			$settings_ui->render_settings_field(
				__( 'Additional CSS class', 'wp-changelog' ),
				'<input type="text" class="regular-text" name="' . esc_attr( $option_key ) . '[className]" value="' . esc_attr( $settings['className'] ) . '" />'
			);
			?>
		</table>

		<h2><?php echo esc_html__( 'Change field options', 'wp-changelog' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php
			$settings_ui->render_settings_field(
				__( 'Consolidate identical dates', 'wp-changelog' ),
				'<label><input type="checkbox" name="' . esc_attr( $option_key ) . '[consolidateDates]" value="1" ' . checked( ! empty( $settings['consolidateDates'] ), true, false ) . ' /> ' . esc_html__( 'Merge entries with the same date and author into one row', 'wp-changelog' ) . '</label>'
			);

			$settings_ui->render_settings_field(
				__( 'List for changes', 'wp-changelog' ),
				'<label><input type="checkbox" name="' . esc_attr( $option_key ) . '[listChanges]" value="1" ' . checked( ! empty( $settings['listChanges'] ), true, false ) . ' /> ' . esc_html__( 'Always display changes as a bullet list in the Change column', 'wp-changelog' ) . '</label>'
			);

			$settings_ui->render_settings_field(
				__( 'Order merged changes by', 'wp-changelog' ),
				'<select name="' . esc_attr( $option_key ) . '[changeFieldSort]">
					<option value="time"' . selected( $settings['changeFieldSort'], 'time', false ) . '>' . esc_html__( 'Time of change', 'wp-changelog' ) . '</option>
					<option value="alpha"' . selected( $settings['changeFieldSort'], 'alpha', false ) . '>' . esc_html__( 'Alphabetically', 'wp-changelog' ) . '</option>
				</select>'
			);

			$settings_ui->render_settings_field(
				__( 'Merged changes sort order', 'wp-changelog' ),
				'<select name="' . esc_attr( $option_key ) . '[changeFieldOrder]">
					<option value="oldest_first"' . selected( $settings['changeFieldOrder'], 'oldest_first', false ) . '>' . esc_html__( 'Oldest on top', 'wp-changelog' ) . '</option>
					<option value="newest_first"' . selected( $settings['changeFieldOrder'], 'newest_first', false ) . '>' . esc_html__( 'Newest on top', 'wp-changelog' ) . '</option>
				</select>',
				__( 'Controls the order of multiple changes within one table cell.', 'wp-changelog' )
			);
			?>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
