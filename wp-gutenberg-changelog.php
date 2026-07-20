<?php
/**
 * Plugin Name: Gutenberg Änderungsprotokoll
 * Description: Fügt Änderungsnotizen hinzu und listet sie in einer flexiblen Tabelle auf.
 * Version: 1.1.0
 * Author: Ihr Name
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function wpc_register_changelog_blocks() {
    // 1. Änderungs-Eintrag
    register_block_type( 'wpc/change-item', [
        'editor_script' => 'wpc-blocks-js',
        'attributes'      => [
            'date'    => [ 'type' => 'string', 'default' => '' ],
            'comment' => [ 'type' => 'string', 'default' => '' ],
            'author'  => [ 'type' => 'string', 'default' => '' ], // Speichert den Autorennamen
        ],
    ]);

    // 2. Tabellen-Block mit echten WP-Tabellen-Eigenschaften
    register_block_type( 'wpc/change-table', [
        'editor_script'   => 'wpc-blocks-js',
        'render_callback' => 'wpc_render_change_table',
        'attributes'      => [
            'postId'       => [ 'type' => 'number', 'default' => 0 ],
            'showAuthor'   => [ 'type' => 'boolean', 'default' => true ], // Schalter für Autor
            'hasFixedLayout' => [ 'type' => 'boolean', 'default' => false ], // WP Standard
            'align'        => [ 'type' => 'string', 'default' => '' ], // WP Ausrichtung
            'className'    => [ 'type' => 'string', 'default' => '' ], // Für Styles wie is-style-stripes
        ],
    ]);
}
add_action( 'init', 'wpc_register_changelog_blocks' );

// Skripte für den Editor laden
function wpc_enqueue_block_editor_assets() {
    wp_enqueue_script(
        'wpc-blocks-js',
        plugins_url( 'blocks.js', __FILE__ ),
        [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-block-editor' ],
        filemtime( plugin_dir_path( __FILE__ ) . 'blocks.js' )
    );
}
add_action( 'enqueue_block_editor_assets', 'wpc_enqueue_block_editor_assets' );


// PHP-Rendering für die Tabelle
function wpc_render_change_table( $attributes, $content ) {
    $post_id = !empty($attributes['postId']) ? intval($attributes['postId']) : get_the_ID();
    if ( ! $post_id ) {
        return '<p style="padding:10px; background:#fff3cd;">Speichere den Beitrag einmal als Entwurf, um die Historie zu laden.</p>';
    }

    $post = get_post($post_id);
    if ( ! $post ) return '';

    $show_author = isset($attributes['showAuthor']) ? $attributes['showAuthor'] : true;

    // 1. Daten sammeln
    $changelog_entries = [];

    // Erstellungseintrag sammeln
    $author_obj = get_user_by('id', $post->post_author);
    $changelog_entries[] = [
        'timestamp' => (int)get_the_date( 'U', $post_id ),
        'date_str'  => get_the_date( 'd.m.Y', $post_id ),
        'comment'   => '<strong>Beitrag erstellt</strong>',
        'author'    => $author_obj ? $author_obj->display_name : 'System'
    ];

    // Änderungs-Blöcke durchsuchen
    $blocks = parse_blocks( $post->post_content );
    foreach ( $blocks as $block ) {
        if ( $block['blockName'] === 'wpc/change-item' ) {
            $date_str = !empty($block['attrs']['date']) ? esc_html($block['attrs']['date']) : '';
            $comment  = !empty($block['attrs']['comment']) ? esc_html($block['attrs']['comment']) : '';
            $author   = !empty($block['attrs']['author']) ? esc_html($block['attrs']['author']) : 'Unbekannt';
            
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

    // 2. Absteigend sortieren
    usort($changelog_entries, function($a, $b) {
        return $b['timestamp'] <=> $a['timestamp'];
    });

    // 3. WP-Tabellen-Klassen dynamisch zusammenbauen
    $wrapper_classes = [ 'wp-block-table', 'wp-block-changelog-table' ];
    
    // Block-Ausrichtung (z.B. alignwide, alignfull) anhängen
    if ( ! empty( $attributes['align'] ) ) {
        $wrapper_classes[] = 'align' . $attributes['align'];
    }
    // Zusätzliche Styles (z.B. Klassen vom Theme oder "is-style-stripes")
    if ( ! empty( $attributes['className'] ) ) {
        $wrapper_classes[] = $attributes['className'];
    }

    $table_classes = [];
    if ( ! empty( $attributes['hasFixedLayout'] ) ) {
        $table_classes[] = 'has-fixed-layout';
    }

    $wrapper_class_str = implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) );
    $table_class_str   = implode( ' ', array_map( 'sanitize_html_class', $table_classes ) );

    // 4. HTML-Struktur generieren
    $output = '<div class="' . $wrapper_class_str . '">';
    $output .= '<table class="' . $table_class_str . '">';
    
    // Tabellenkopf anpassen je nach Autoren-Sichtbarkeit
    $output .= '<thead><tr>';
    $output .= '<th>Datum</th>';
    if ( $show_author ) {
        $output .= '<th>Autor</th>';
    }
    $output .= '<th>Änderung</th>';
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
