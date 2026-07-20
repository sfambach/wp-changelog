<?php
/**
 * Plugin Name: Gutenberg Änderungsprotokoll
 * Description: Fügt Änderungsnotizen hinzu und listet sie in einer flexiblen Tabelle auf.
 * Version: 1.1.0
 * Author: Ihr Name
 * Text Domain: wp-changelog
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Textdomain laden für PHP-Übersetzungen
function wpc_load_textdomain() {
    load_plugin_textdomain( 'wp-changelog', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'wpc_load_textdomain' );

function wpc_register_changelog_blocks() {
    // 1. Änderungs-Eintrag
    register_block_type( 'wpc/change-item', [
        'editor_script' => 'wpc-blocks-js',
        'attributes'      => [
            'date'    => [ 'type' => 'string', 'default' => '' ],
            'comment' => [ 'type' => 'string', 'default' => '' ],
            'author'  => [ 'type' => 'string', 'default' => '' ],
        ],
    ]);

    // 2. Tabellen-Block
    register_block_type( 'wpc/change-table', [
        'editor_script'   => 'wpc-blocks-js',
        'render_callback' => 'wpc_render_change_table',
        'attributes'      => [
            'postId'       => [ 'type' => 'number', 'default' => 0 ],
            'showAuthor'   => [ 'type' => 'boolean', 'default' => true ],
            'hasFixedLayout' => [ 'type' => 'boolean', 'default' => false ],
            'align'        => [ 'type' => 'string', 'default' => '' ],
            'className'    => [ 'type' => 'string', 'default' => '' ],
        ],
    ]);
	
	 if ( function_exists( 'register_block_style' ) ) {
        // Standard-Stil
        register_block_style( 'wpc/change-table', [
            'name'         => 'default',
            'label'        => __( 'Default', 'wp-changelog' ),
            'is_default'   => true,
        ] );

        // Gestreifter Stil
        register_block_style( 'wpc/change-table', [
            'name'         => 'stripes',
            'label'        => __( 'Stripes', 'wp-changelog' ),
        ] );
    }
	
}
add_action( 'init', 'wpc_register_changelog_blocks' );

// Skripte und deren Übersetzungen für den Editor laden
function wpc_enqueue_block_editor_assets() {
    wp_enqueue_script(
        'wpc-blocks-js',
        plugins_url( 'blocks.js', __FILE__ ),
        [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-block-editor', 'wp-i18n' ], // 'wp-i18n' hinzugefügt!
        filemtime( plugin_dir_path( __FILE__ ) . 'blocks.js' )
    );

    // Verknüpft die Übersetzungen mit dem JavaScript-Handle
    wp_set_script_translations( 'wpc-blocks-js', 'wp-changelog', plugin_dir_path( __FILE__ ) . 'languages' );
}
add_action( 'enqueue_block_editor_assets', 'wpc_enqueue_block_editor_assets' );


// PHP-Rendering für die Tabelle
function wpc_render_change_table( $attributes, $content ) {
    $post_id = !empty($attributes['postId']) ? intval($attributes['postId']) : get_the_ID();
    if ( ! $post_id ) {
        return '<p style="padding:10px; background:#fff3cd;">' . esc_html__( 'Please save the post as a draft to load the history.', 'wp-changelog' ) . '</p>';
    }

    $post = get_post($post_id);
    if ( ! $post ) return '';

    $show_author = isset($attributes['showAuthor']) ? $attributes['showAuthor'] : true;
    $changelog_entries = [];

    // Erstellungseintrag sammeln
    $author_obj = get_user_by('id', $post->post_author);
    $changelog_entries[] = [
        'timestamp' => (int)get_the_date( 'U', $post_id ),
        'date_str'  => get_the_date( 'd.m.Y', $post_id ),
        'comment'   => '<strong>' . esc_html__( 'Post created', 'wp-changelog' ) . '</strong>',
        'author'    => $author_obj ? $author_obj->display_name : __( 'System', 'wp-changelog' )
    ];

    // Änderungs-Blöcke durchsuchen
    $blocks = parse_blocks( $post->post_content );
    foreach ( $blocks as $block ) {
        if ( $block['blockName'] === 'wpc/change-item' ) {
            $date_str = !empty($block['attrs']['date']) ? esc_html($block['attrs']['date']) : '';
            $comment  = !empty($block['attrs']['comment']) ? esc_html($block['attrs']['comment']) : '';
            $author   = !empty($block['attrs']['author']) ? esc_html($block['attrs']['author']) : __( 'Unknown', 'wp-changelog' );
            
            if ( !empty($date_str) ) {
                $timestamp = strtotime( str_replace('.', '-', $date_str) );
                if ( !$timestamp ) $timestamp = time();
            } else {
                $date_str  = '-';
                $timestamp = time();
            }

            $changelog_entries[] = [
                'timestamp' => $timestamp,
                'date_str'  => $date_str,
                'comment'   => $comment,
                'author'    => $author
            ];
        }
    }

    // Absteigend sortieren
    usort($changelog_entries, function($a, $b) {
        return $b['timestamp'] <=> $a['timestamp'];
    });

    $wrapper_classes = [ 'wp-block-table', 'wp-block-changelog-table' ];
    if ( ! empty( $attributes['align'] ) ) {
        $wrapper_classes[] = 'align' . $attributes['align'];
    }
    if ( ! empty( $attributes['className'] ) ) {
        $wrapper_classes[] = $attributes['className'];
    }

    $table_classes = [];
    if ( ! empty( $attributes['hasFixedLayout'] ) ) {
        $table_classes[] = 'has-fixed-layout';
    }

    $wrapper_class_str = implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) );
    $table_class_str   = implode( ' ', array_map( 'sanitize_html_class', $table_classes ) );

    $output = '<div class="' . $wrapper_class_str . '">';
    $output .= '<table class="' . $table_class_str . '">';
    
    $output .= '<thead><tr>';
    $output .= '<th>' . esc_html__( 'Date', 'wp-changelog' ) . '</th>';
    if ( $show_author ) {
        $output .= '<th>' . esc_html__( 'Author', 'wp-changelog' ) . '</th>';
    }
    $output .= '<th>' . esc_html__( 'Change', 'wp-changelog' ) . '</th>';
    $output .= '</tr></thead>';
    
    $output .= '<tbody>';
    foreach ( $changelog_entries as $entry ) {
        $output .= '<tr>';
        $output .= sprintf( '<td>%s</td>', $entry['date_str'] );
        if ( $show_author ) {
            $output .= sprintf( '<td>%s</td>', $entry['author'] );
        }
        $output .= sprintf( '<td>%s</td>', $entry['comment'] );
        $output .= '</tr>';
    }
    $output .= '</tbody></table></div>';

    return $output;
}

