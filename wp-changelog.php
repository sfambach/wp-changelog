<?php
/**
 * Plugin Name: Gutenberg Changelog & Version History
 * Description: Adds change notes and lists them in a flexible, interactive table.
 * Version: 1.3.0
 * Author: Ihr Name
 * Text Domain: wp-changelog
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function wpc_load_textdomain() {
    load_plugin_textdomain( 'wp-changelog', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'wpc_load_textdomain' );

// CSS für das Backend einbinden, um Abstände der labels in TextControl zu entfernen
function wpc_editor_styles() {
    echo '<style>.wpc-minimal-input .components-base-control__field { margin-bottom: 0 !important; }</style>';
}
add_action( 'admin_head', 'wpc_editor_styles' );


function wpc_register_changelog_blocks() {
    register_block_type( 'wpc/change-item', [
        'editor_script' => 'wpc-blocks-js',
        'attributes'      => [
            'date'    => [ 'type' => 'string', 'default' => '' ],
            'comment' => [ 'type' => 'string', 'default' => '' ],
            'author'  => [ 'type' => 'string', 'default' => '' ],
        ],
    ]);

    register_block_type( 'wpc/change-table', [
        'editor_script'   => 'wpc-blocks-js',
        'render_callback' => 'wpc_render_change_table',
        'attributes'      => [
            'showAuthor'       => [ 'type' => 'boolean', 'default' => true ],
            'hasFixedLayout'   => [ 'type' => 'boolean', 'default' => false ],
            'consolidateDates' => [ 'type' => 'boolean', 'default' => false ],
            'sortOrder'        => [ 'type' => 'string', 'default' => 'desc' ],
            'align'            => [ 'type' => 'string', 'default' => '' ],
            'className'        => [ 'type' => 'string', 'default' => '' ],
        ],
    ]);

    if ( function_exists( 'register_block_style' ) ) {
        register_block_style( 'wpc/change-table', [ 'name' => 'default', 'label' => __( 'Default', 'wp-changelog' ), 'is_default' => true ] );
        register_block_style( 'wpc/change-table', [ 'name' => 'stripes', 'label' => __( 'Stripes', 'wp-changelog' ) ] );
    }
}
add_action( 'init', 'wpc_register_changelog_blocks' );

function wpc_enqueue_block_editor_assets() {
    wp_enqueue_script(
        'wpc-blocks-js',
        plugins_url( 'blocks.js', __FILE__ ),
        [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-block-editor', 'wp-i18n' ],
        filemtime( plugin_dir_path( __FILE__ ) . 'blocks.js' )
    );
    wp_set_script_translations( 'wpc-blocks-js', 'wp-changelog', plugin_dir_path( __FILE__ ) . 'languages' );
}
add_action( 'enqueue_block_editor_assets', 'wpc_enqueue_block_editor_assets' );

// PHP-Rendering für die Tabelle mit flexibler Konsolidierung und Sortierung
function wpc_render_change_table( $attributes, $content ) {
    $post_id = false;
    if (defined('REST_REQUEST') && REST_REQUEST && !empty($_GET['context']) && !empty($_GET['post_id'])) {
        $post_id = intval($_GET['post_id']);
    }
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    if ( ! $post_id ) {
        return '<p style="padding:10px; background:#fff3cd;">' . esc_html__( 'Please save the post as a draft to load the history.', 'wp-changelog' ) . '</p>';
    }

    $post = get_post($post_id);
    if ( ! $post || $post->post_type === 'wp_block' ) {
        return '<p style="padding:10px; background:#fafafa; border:1px dashed #ccc; font-size:13px; color:#666;">' . esc_html__( 'Changelog Table (Preview inside template)', 'wp-changelog' ) . '</p>';
    }

    $show_author       = isset($attributes['showAuthor']) ? $attributes['showAuthor'] : true;
    $consolidate_dates = isset($attributes['consolidateDates']) ? $attributes['consolidateDates'] : false;
    $sort_order        = isset($attributes['sortOrder']) ? $attributes['sortOrder'] : 'desc';
    $custom_date       = !empty($attributes['customCreationDate']) ? trim(esc_html($attributes['customCreationDate'])) : '';

    $changelog_entries = [];
	
	// WENN WIR IN EINER VORLAGE SIND: Erzeuge zwei hübsche Dummy-Einträge für das Live-Styling
    if ( $is_template_preview ) {
        $changelog_entries[] = [
            'timestamp' => time() - 86400,
            'date_str'  => date('d.m.Y', time() - 86400),
            'comment'   => '<strong>' . esc_html__( 'Post created', 'wp-changelog' ) . '</strong>',
            'author'    => __( 'System', 'wp-changelog' )
        ];
        $changelog_entries[] = [
            'timestamp' => time(),
            'date_str'  => date('d.m.Y'),
            'comment'   => esc_html__( 'Example: Replaced header image and updated text layout.', 'wp-changelog' ),
            'author'    => __( 'John Doe', 'wp-changelog' )
        ];
    } else {
    // WENN ES EIN ECHTER BEITRAG IST: Normale Logik ausführen
		$change_items = [];
		$first_change_timestamp = null;
		$first_change_date_str = '';

		// 1. Alle Änderungs-Blöcke vorab durchlaufen und timestamps berechnen
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

				// Den ältesten (kleinsten) Zeitstempel für das "Datum der ersten Änderung" finden
				if ( $first_change_timestamp === null || $timestamp < $first_change_timestamp ) {
					$first_change_timestamp = $timestamp;
					$first_change_date_str  = $date_str;
				}

				$change_items[] = [
					'timestamp' => $timestamp,
					'date_str'  => $date_str,
					'comment'   => $comment,
					'author'    => $author
				];
			}
		}
	}
    // 2. Erstellungseintrag bestimmen
    $author_obj = get_user_by('id', $post->post_author);
    
    if ( !empty($custom_date) ) {
        // Option A: Überschreibung per Sidebar-Eingabe hat höchste Priorität
        $created_date_str  = $custom_date;
        $created_timestamp = strtotime( str_replace('.', '-', $custom_date) ) ?: strtotime($post->post_date);
    } elseif ( !empty($first_change_date_str) ) {
        // Option B: Nutze automatisch das Datum der allerersten Änderung
        $created_date_str  = $first_change_date_str;
        $created_timestamp = $first_change_timestamp - 1; // -1 Sekunde, damit es bei gleicher Sortierung logisch vor der ersten Änderung steht
    } else {
        // Option C: Fallback auf das WP-Datenbankdatum, falls noch überhaupt keine Blöcke existieren
        $created_timestamp = strtotime($post->post_date);
        $created_date_str  = $created_timestamp ? date('d.m.Y', $created_timestamp) : get_the_date('d.m.Y', $post_id);
    }

    // Erstellungseintrag dem Array hinzufügen
    $changelog_entries[] = [
        'timestamp' => (int)$created_timestamp,
        'date_str'  => $created_date_str,
        'comment'   => '<strong>' . esc_html__( 'Post created', 'wp-changelog' ) . '</strong>',
        'author'    => $author_obj ? $author_obj->display_name : __( 'System', 'wp-changelog' )
    ];

    // Die gesammelten Änderungs-Einträge ebenfalls ins Haupt-Array überführen
    $changelog_entries = array_merge($changelog_entries, $change_items);

    // 3. Sortierung (Aufsteigend oder Absteigend) auf das Gesamtergebnis anwenden
    usort($changelog_entries, function($a, $b) use ($sort_order) {
        if ($sort_order === 'asc') {
            return $a['timestamp'] <=> $b['timestamp'];
        }
        return $b['timestamp'] <=> $a['timestamp'];
    });

    // ... [Der restliche HTML-Ausgabecode ab hier bleibt identisch] ...


    // Falls Konsolidierung aktiv ist: Einträge nach Datum verschmelzen
    if ( $consolidate_dates ) {
        $final_entries = [];
        foreach ( $changelog_entries as $entry ) {
            $d = $entry['date_str'];
            if ( ! isset( $final_entries[$d] ) ) {
                $final_entries[$d] = [
                    'date_str' => $d,
                    'comments' => [ $entry['comment'] ],
                    'authors'  => [ $entry['author'] ]
                ];
            } else {
                $final_entries[$d]['comments'][] = $entry['comment'];
                if ( ! in_array( $entry['author'], $final_entries[$d]['authors'] ) ) {
                    $final_entries[$d]['authors'][] = $entry['author'];
                }
            }
        }
    }

    // HTML Rendering Klassen vorbereiten
    $wrapper_classes = [ 'wp-block-table', 'wp-block-changelog-table' ];
    if ( ! empty( $attributes['align'] ) ) $wrapper_classes[] = 'align' . $attributes['align'];
    if ( ! empty( $attributes['className'] ) ) $wrapper_classes[] = $attributes['className'];
    $table_classes = ! empty( $attributes['hasFixedLayout'] ) ? [ 'has-fixed-layout' ] : [];

    $wrapper_class_str = implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) );
    $table_class_str   = implode( ' ', array_map( 'sanitize_html_class', $table_classes ) );

    $output = '<div class="' . $wrapper_class_str . '">';
    $output .= '<table class="' . $table_class_str . '">';
    $output .= '<thead><tr><th>' . esc_html__( 'Date', 'wp-changelog' ) . '</th><th>' . esc_html__( 'Change', 'wp-changelog' ) . '</th>';
    if ( $show_author ) $output .= '<th>' . esc_html__( 'Author', 'wp-changelog' ) . '</th>';
    $output .= '</tr></thead><tbody>';

    // Zeilen ausgeben je nachdem, ob konsolidiert wurde oder nicht
    if ( $consolidate_dates ) {
        foreach ( $final_entries as $row ) {
            $output .= '<tr>';
            $output .= sprintf( '<td>%s</td>', $row['date_str'] );
            
            // Kommentare als kompakte Liste ausgeben, falls mehr als einer existiert
            if ( count($row['comments']) > 1 ) {
                $output .= '<td><ul style="margin:0; padding-left:16px;">';
                foreach ($row['comments'] as $com) {
                    $output .= sprintf('<li>%s</li>', $com);
                }
                $output .= '</ul></td>';
            } else {
                $output .= sprintf( '<td>%s</td>', $row['comments'][0] );
            }

            if ( $show_author ) {
                $output .= sprintf( '<td>%s</td>', implode(', ', $row['authors']) );
            }
            $output .= '</tr>';
        }
    } else {
        foreach ( $changelog_entries as $entry ) {
            $output .= '<tr>';
            $output .= sprintf( '<td>%s</td>', $entry['date_str'] );
            $output .= sprintf( '<td>%s</td>', $entry['comment'] );
            if ( $show_author ) $output .= sprintf( '<td>%s</td>', $entry['author'] );
            $output .= '</tr>';
        }
    }

    $output .= '</tbody></table></div>';
    return $output;
}
