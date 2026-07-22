<?php
/**
 * Admin settings page for global Change Log integration.
 *
 * @package WP_Changelog
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** @var string Option name for global Change Log settings. */
define( 'WPC_GLOBAL_CHANGE_LOG_OPTION', 'wpc_global_change_log_settings' );

/**
 * Register the plugin settings page.
 *
 * @return void
 */
function wpc_register_settings_page() {
    add_options_page(
        __( 'Change Log Settings', 'wp-changelog' ),
        __( 'Change Log', 'wp-changelog' ),
        'manage_options',
        'wp-changelog',
        'wpc_render_settings_page'
    );
}
add_action( 'admin_menu', 'wpc_register_settings_page' );

/**
 * Register global settings and their sanitization callback.
 *
 * @return void
 */
function wpc_register_settings() {
    register_setting(
        'wpc_global_change_log',
        WPC_GLOBAL_CHANGE_LOG_OPTION,
        [
            'type'              => 'array',
            'sanitize_callback' => 'wpc_sanitize_global_change_log_settings',
            'default'           => wpc_default_global_change_log_settings(),
        ]
    );
}
add_action( 'admin_init', 'wpc_register_settings' );

/**
 * Sanitize settings submitted from the admin page.
 *
 * @param mixed $input Raw submitted settings.
 * @return array
 */
function wpc_sanitize_global_change_log_settings( $input ) {
    $defaults = wpc_default_global_change_log_settings();
    $input    = is_array( $input ) ? $input : [];
    $clean    = $defaults;

    $clean['enabled'] = ! empty( $input['enabled'] );

    $allowed_post_types = array_keys( wpc_get_selectable_post_types() );
    $submitted_types    = isset( $input['post_types'] ) && is_array( $input['post_types'] ) ? $input['post_types'] : [];
    $clean['post_types'] = array_values(
        array_intersect(
            array_map( 'sanitize_key', $submitted_types ),
            $allowed_post_types
        )
    );

    if ( empty( $clean['post_types'] ) ) {
        $clean['post_types'] = [ 'page' ];
    }

    $clean['showAuthor']       = ! empty( $input['showAuthor'] );
    $clean['hasFixedLayout']   = ! empty( $input['hasFixedLayout'] );
    $clean['consolidateDates'] = ! empty( $input['consolidateDates'] );
    $clean['listChanges']      = ! empty( $input['listChanges'] );
    $clean['visibleOnPage']    = ! empty( $input['visibleOnPage'] );

    $clean['tableStyle'] = in_array( $input['tableStyle'] ?? '', [ 'default', 'stripes' ], true )
        ? $input['tableStyle']
        : $defaults['tableStyle'];

    $clean['sortOrder'] = in_array( $input['sortOrder'] ?? '', [ 'asc', 'desc' ], true )
        ? $input['sortOrder']
        : $defaults['sortOrder'];

    $clean['changeFieldSort'] = in_array( $input['changeFieldSort'] ?? '', [ 'time', 'alpha' ], true )
        ? $input['changeFieldSort']
        : $defaults['changeFieldSort'];

    $clean['changeFieldOrder'] = wpc_normalize_merged_change_order( $input['changeFieldOrder'] ?? $defaults['changeFieldOrder'] );

    $allowed_align = [ '', 'left', 'center', 'right', 'wide', 'full' ];
    $clean['align'] = in_array( $input['align'] ?? '', $allowed_align, true )
        ? $input['align']
        : $defaults['align'];

    $clean['className'] = sanitize_text_field( $input['className'] ?? '' );

    return $clean;
}

/**
 * Public post types that can receive the global Change Log.
 *
 * @return array<string, WP_Post_Type>
 */
function wpc_get_selectable_post_types() {
    $post_types = get_post_types(
        [
            'public'  => true,
            'show_ui' => true,
        ],
        'objects'
    );

    unset( $post_types['attachment'] );

    return $post_types;
}

/**
 * Render one admin settings field.
 *
 * @param string $label       Field label.
 * @param string $field_html  Input markup.
 * @param string $description Optional help text.
 * @return void
 */
function wpc_render_settings_field( $label, $field_html, $description = '' ) {
    echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
    echo $field_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in callers.
    if ( $description !== '' ) {
        echo '<p class="description">' . esc_html( $description ) . '</p>';
    }
    echo '</td></tr>';
}

/**
 * Output the plugin settings page.
 *
 * @return void
 */
function wpc_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $settings   = wpc_get_global_change_log_settings();
    $option_key = WPC_GLOBAL_CHANGE_LOG_OPTION;
    $post_types = wpc_get_selectable_post_types();
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Change Log Settings', 'wp-changelog' ); ?></h1>
        <p><?php echo esc_html__( 'Automatically append the Change Log table to supported content types. Manual Change Log blocks in the editor are unchanged and take precedence on individual pages.', 'wp-changelog' ); ?></p>

        <form method="post" action="options.php">
            <?php settings_fields( 'wpc_global_change_log' ); ?>

            <h2><?php echo esc_html__( 'Global integration', 'wp-changelog' ); ?></h2>
            <table class="form-table" role="presentation">
                <?php
                wpc_render_settings_field(
                    __( 'Enable on all configured pages', 'wp-changelog' ),
                    '<label><input type="checkbox" name="' . esc_attr( $option_key ) . '[enabled]" value="1" ' . checked( ! empty( $settings['enabled'] ), true, false ) . ' /> ' . esc_html__( 'Append the Change Log table automatically', 'wp-changelog' ) . '</label>',
                    __( 'When enabled, the table is added to the end of the content on the selected post types unless a Change Log block is already present.', 'wp-changelog' )
                );

                $post_type_fields = '';
                foreach ( $post_types as $post_type => $object ) {
                    $checked = in_array( $post_type, (array) $settings['post_types'], true ) ? 'checked="checked"' : '';
                    $post_type_fields .= '<label style="display:block;margin-bottom:4px;">';
                    $post_type_fields .= '<input type="checkbox" name="' . esc_attr( $option_key ) . '[post_types][]" value="' . esc_attr( $post_type ) . '" ' . $checked . ' /> ';
                    $post_type_fields .= esc_html( $object->labels->singular_name ) . ' <code>' . esc_html( $post_type ) . '</code>';
                    $post_type_fields .= '</label>';
                }

                wpc_render_settings_field(
                    __( 'Post types', 'wp-changelog' ),
                    $post_type_fields,
                    __( 'Choose which public content types should receive the global Change Log.', 'wp-changelog' )
                );
                ?>
            </table>

            <h2><?php echo esc_html__( 'Table display', 'wp-changelog' ); ?></h2>
            <p class="description"><?php echo esc_html__( 'These options mirror the Change Log block sidebar settings.', 'wp-changelog' ); ?></p>
            <table class="form-table" role="presentation">
                <?php
                wpc_render_settings_field(
                    __( 'Sort Order', 'wp-changelog' ),
                    '<select name="' . esc_attr( $option_key ) . '[sortOrder]">
                        <option value="desc"' . selected( $settings['sortOrder'], 'desc', false ) . '>' . esc_html__( 'Newest on top', 'wp-changelog' ) . '</option>
                        <option value="asc"' . selected( $settings['sortOrder'], 'asc', false ) . '>' . esc_html__( 'Oldest on top', 'wp-changelog' ) . '</option>
                    </select>',
                    __( 'Which date row appears at the top of the table.', 'wp-changelog' )
                );

                wpc_render_settings_field(
                    __( 'Visible on page', 'wp-changelog' ),
                    '<label><input type="checkbox" name="' . esc_attr( $option_key ) . '[visibleOnPage]" value="1" ' . checked( ! empty( $settings['visibleOnPage'] ), true, false ) . ' /> ' . esc_html__( 'Show on the public site', 'wp-changelog' ) . '</label>',
                    __( 'When off, the global table is hidden on the public site. It is still available in wp-admin previews.', 'wp-changelog' )
                );

                wpc_render_settings_field(
                    __( 'Show Author', 'wp-changelog' ),
                    '<label><input type="checkbox" name="' . esc_attr( $option_key ) . '[showAuthor]" value="1" ' . checked( ! empty( $settings['showAuthor'] ), true, false ) . ' /> ' . esc_html__( 'Display the Author column', 'wp-changelog' ) . '</label>'
                );

                wpc_render_settings_field(
                    __( 'Fixed width table cells', 'wp-changelog' ),
                    '<label><input type="checkbox" name="' . esc_attr( $option_key ) . '[hasFixedLayout]" value="1" ' . checked( ! empty( $settings['hasFixedLayout'] ), true, false ) . ' /> ' . esc_html__( 'Use fixed table layout', 'wp-changelog' ) . '</label>'
                );

                wpc_render_settings_field(
                    __( 'Table style', 'wp-changelog' ),
                    '<select name="' . esc_attr( $option_key ) . '[tableStyle]">
                        <option value="default"' . selected( $settings['tableStyle'] ?? 'default', 'default', false ) . '>' . esc_html__( 'Default', 'wp-changelog' ) . '</option>
                        <option value="stripes"' . selected( $settings['tableStyle'] ?? 'default', 'stripes', false ) . '>' . esc_html__( 'Stripes', 'wp-changelog' ) . '</option>
                    </select>'
                );

                wpc_render_settings_field(
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

                wpc_render_settings_field(
                    __( 'Additional CSS class', 'wp-changelog' ),
                    '<input type="text" class="regular-text" name="' . esc_attr( $option_key ) . '[className]" value="' . esc_attr( $settings['className'] ) . '" />'
                );
                ?>
            </table>

            <h2><?php echo esc_html__( 'Change field options', 'wp-changelog' ); ?></h2>
            <table class="form-table" role="presentation">
                <?php
                wpc_render_settings_field(
                    __( 'Consolidate identical dates', 'wp-changelog' ),
                    '<label><input type="checkbox" name="' . esc_attr( $option_key ) . '[consolidateDates]" value="1" ' . checked( ! empty( $settings['consolidateDates'] ), true, false ) . ' /> ' . esc_html__( 'Merge entries with the same date and author into one row', 'wp-changelog' ) . '</label>'
                );

                wpc_render_settings_field(
                    __( 'List for changes', 'wp-changelog' ),
                    '<label><input type="checkbox" name="' . esc_attr( $option_key ) . '[listChanges]" value="1" ' . checked( ! empty( $settings['listChanges'] ), true, false ) . ' /> ' . esc_html__( 'Always display changes as a bullet list in the Change column', 'wp-changelog' ) . '</label>'
                );

                wpc_render_settings_field(
                    __( 'Order merged changes by', 'wp-changelog' ),
                    '<select name="' . esc_attr( $option_key ) . '[changeFieldSort]">
                        <option value="time"' . selected( $settings['changeFieldSort'], 'time', false ) . '>' . esc_html__( 'Time of change', 'wp-changelog' ) . '</option>
                        <option value="alpha"' . selected( $settings['changeFieldSort'], 'alpha', false ) . '>' . esc_html__( 'Alphabetically', 'wp-changelog' ) . '</option>
                    </select>'
                );

                wpc_render_settings_field(
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
    <?php
}
