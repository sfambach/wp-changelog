( function( blocks, element, blockEditor, components, data ) {
    var el = element.createElement;
    var TextControl = components.TextControl;
    var ToggleControl = components.ToggleControl;
    var PanelBody = components.PanelBody;
    var useSelect = data.useSelect;

    // Neue WP-Gutenberg Komponenten für echte Toolbar/Sidebar Einstellungen
    var InspectorControls = blockEditor.InspectorControls;
    var BlockControls = blockEditor.BlockControls;
    var BlockAlignmentControl = blockEditor.BlockAlignmentControl;

    /**
     * BLOCK 1: Der Änderungs-Eintrag (Erweitert um automatischen Autorennamen)
     */
    blocks.registerBlockType( 'wpc/change-item', {
        title: 'Änderungs-Notiz (Nur Backend)',
        icon: 'edit',
        category: 'common',
        attributes: {
            date: { type: 'string', default: '' },
            comment: { type: 'string', default: '' },
            author: { type: 'string', default: '' }
        },
        edit: function( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            // Holt den aktuell angemeldeten Benutzer aus der WP-Datenbank
            var currentUser = data.select( 'core' ).getCurrentUser();

            element.useEffect( function() {
                // 1. Datum initialisieren
                if ( ! attributes.date ) {
                    var today = new Date();
                    var formattedDate = today.getDate().toString().padStart(2, '0') + '.' +
                        (today.getMonth() + 1).toString().padStart(2, '0') + '.' +
                        today.getFullYear();
                    setAttributes( { date: formattedDate } );
                }
                // 2. Autorennamen automatisch festlegen und mitspeichern
                if ( currentUser && ! attributes.author ) {
                    setAttributes( { author: currentUser.name } );
                }
            }, [ currentUser ] );

            return el( 'div', { style: { padding: '15px', border: '1px dashed #007cba', backgroundColor: '#f0f6fa', marginBottom: '10px' } },
                el( 'p', { style: { margin: '0 0 8px 0', fontWeight: 'bold', color: '#007cba' } }, 
                    '📝 Interne Änderung vom: ' + attributes.date + ' (Autor: ' + (attributes.author || 'Lade...') + ')'
                ),
                el( TextControl, {
                    label: 'Was wurde geändert?',
                    value: attributes.comment,
                    onChange: function( value ) {
                        setAttributes( { comment: value } );
                    },
                    placeholder: 'z.B. Tippfehler korrigiert, Bild ausgetauscht...'
                } )
            );
        },
        save: function() {
            return null; // Nur im Backend sichtbar
        }
    } );

    /**
     * BLOCK 2: Die Änderungs-Tabelle (Mit identischen Steuerungen wie WP-Tabellen)
     */
    blocks.registerBlockType( 'wpc/change-table', {
        title: 'Änderungshistorie Tabelle',
        icon: 'editor-table',
        category: 'common',
        // Aktiviert WP-Standard-Ausrichtungen (Weite Breite, Volle Breite) und das originale Tabellen-Stile-System (Standard / Gestreift)
        supports: {
            align: [ 'left', 'center', 'right', 'wide', 'full' ],
            className: true, // Erlaubt Themes, Stile wie .is-style-stripes zu injizieren
            styles: true     // Bindet die WP-Designauswahl im Editor ein
        },
        attributes: {
            postId: { type: 'number', default: 0 },
            showAuthor: { type: 'boolean', default: true },
            hasFixedLayout: { type: 'boolean', default: false },
            align: { type: 'string', default: '' },
            className: { type: 'string', default: '' }
        },
        edit: function( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            var currentPostId = data.select( 'core/editor' ).getCurrentPostId();

            element.useEffect( function() {
                if ( currentPostId && attributes.postId !== currentPostId ) {
                    setAttributes( { postId: currentPostId } );
                }
            }, [ currentPostId ] );

            if ( ! currentPostId ) {
                return el( 'div', { style: { padding: '15px', background: '#fff3cd', border: '1px solid #ffeeba' } },
                    'Bitte speichere den Beitrag zuerst als Entwurf, um die Live-Vorschau der Tabelle zu aktivieren.'
                );
            }

            return [
                // 1. WP-Toolbar: Ausrichtungskontrolle direkt über dem Block einblenden
                el( BlockControls, { key: 'controls' },
                    el( BlockAlignmentControl, {
                        value: attributes.align,
                        onChange: function( nextAlign ) {
                            setAttributes( { align: nextAlign } );
                        }
                    } )
                ),
                
                // 2. WP-Sidebar: Einstellungs-Panels auf der rechten Seite einblenden
                el( InspectorControls, { key: 'inspector' },
                    el( PanelBody, { title: 'Tabelleneinstellungen', initialOpen: true },
                        // Unser benutzerdefinierter Ein-/Ausschalter für Autoren
                        el( ToggleControl, {
                            label: 'Autor anzeigen',
                            help: 'Zeigt den Namen des Änderungs-Autors in der Tabelle an.',
                            checked: attributes.showAuthor,
                            onChange: function( value ) {
                                setAttributes( { showAuthor: value } );
                            }
                        } ),
                        // Originale WP-Tabellenfunktion: Zellen mit fester Breite
                        el( ToggleControl, {
                            label: 'Zellen mit fester Breite',
                            help: 'Definiert feste Spaltenbreiten statt variabler Breiten.',
                            checked: attributes.hasFixedLayout,
                            onChange: function( value ) {
                                setAttributes( { hasFixedLayout: value } );
                            }
                        } )
                    )
                ),

                // 3. Editor-Vorschau der Tabelle
                el( 'div', { key: 'preview', style: { border: '1px dashed #ccc', padding: '10px', backgroundColor: '#fafafa' } },
                    el( 'span', { style: { display: 'block', fontSize: '11px', color: '#999', marginBottom: '5px', textTransform: 'uppercase' } }, 'Live-Vorschau der Tabelle:' ),
                    el( wp.serverSideRender, {
                        block: 'wpc/change-table',
                        attributes: attributes
                    } )
                )
            ];
        },
        save: function() {
            return null; // Dynamischer Block
        }
    } );

} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.data );
