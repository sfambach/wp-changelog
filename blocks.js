( function( blocks, element, blockEditor, components, data, i18n ) {
    var el = element.createElement;
    var TextControl = components.TextControl;
    var ToggleControl = components.ToggleControl;
    var SelectControl = components.SelectControl;
    var PanelBody = components.PanelBody;

    var InspectorControls = blockEditor.InspectorControls;
    var BlockControls = blockEditor.BlockControls;
    var BlockAlignmentControl = blockEditor.BlockAlignmentControl;
    
    var __ = i18n.__;

    /**
     * BLOCK 1: Der Änderungs-Eintrag (Radikal minimalistisch, grau, ohne Labels)
     */
    blocks.registerBlockType( 'wpc/change-item', {
        title: __( 'Change Note (Backend Only)', 'wp-changelog' ),
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
            var currentUser = data.select( 'core' ).getCurrentUser();

            element.useEffect( function() {
                if ( ! attributes.date ) {
                    var today = new Date();
                    var formattedDate = today.getDate().toString().padStart(2, '0') + '.' +
                        (today.getMonth() + 1).toString().padStart(2, '0') + '.' +
                        today.getFullYear();
                    setAttributes( { date: formattedDate } );
                }
                if ( currentUser && ! attributes.author ) {
                    setAttributes( { author: currentUser.name } );
                }
            }, [ currentUser ] );

            // Minimalistisches, graues Zeilenlayout ohne sichtbare Labels
                        // Radikal flaches Zeilenlayout (Hälfte der vorherigen Höhe)
            return el( 'div', { 
                style: { 
                    display: 'flex', 
                    alignItems: 'center', 
                    gap: '8px', 
                    padding: '2px 6px', // Extrem reduziertes Padding
                    backgroundColor: '#f5f5f5', 
                    marginBottom: '4px',
                    borderRadius: '2px',
                    borderLeft: '3px solid #007cba' // Ein dezenter linker Streifen statt vollem Rahmen
                } 
            },
                // 1. Feld: Datum
                el( 'div', { style: { width: '90px' }, className: 'wpc-minimal-input' },
                    el( TextControl, {
                        value: attributes.date,
                        onChange: function( value ) { setAttributes( { date: value } ); },
                        placeholder: __( 'Date', 'wp-changelog' ),
                        style: { height: '28px', fontSize: '12px' } // Flachere Input-Höhe
                    } )
                ),
                // 2. Feld: Beschreibung
                el( 'div', { style: { flex: '1' }, className: 'wpc-minimal-input' },
                    el( TextControl, {
                        value: attributes.comment,
                        onChange: function( value ) { setAttributes( { comment: value } ); },
                        placeholder: __( 'What was changed? (e.g. Fixed typo...)', 'wp-changelog' ),
                        style: { height: '28px', fontSize: '12px' }
                    } )
                ),
                // 3. Feld: Autor (Ausgegraut)
                el( 'div', { style: { width: '110px', opacity: '0.6' }, className: 'wpc-minimal-input' },
                    el( TextControl, {
                        value: attributes.author || __( 'Loading...', 'wp-changelog' ),
                        disabled: true,
                        style: { height: '28px', fontSize: '12px' }
                    } )
                )
            );
        },
        save: function() { return null; }
    } );

    /**
     * BLOCK 2: Die Änderungs-Tabelle
     */
    blocks.registerBlockType( 'wpc/change-table', {
        title: __( 'Changelog Table', 'wp-changelog' ),
        icon: 'editor-table',
        category: 'common',
        supports: {
            align: [ 'left', 'center', 'right', 'wide', 'full' ],
            className: true,
            styles: true
        },
        attributes: {
            showAuthor: { type: 'boolean', default: true },
            hasFixedLayout: { type: 'boolean', default: false },
            consolidateDates: { type: 'boolean', default: false }, // Neue Option: Konsolidieren
            sortOrder: { type: 'string', default: 'desc' },         // Neue Option: Sortierung (desc/asc)
            align: { type: 'string', default: '' },
            className: { type: 'string', default: '' }
        },
        edit: function( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            var currentPostId = data.select( 'core/editor' ).getCurrentPostId();

            // VORSCHAU-FIX FÜR VORLAGEN: 
            // Registriert jede Inhaltsänderung im gesamten Editor, um ServerSideRender zum Neuladen zu zwingen
            var blocksContentHash = data.useSelect( function( select ) {
                var editor = select( 'core/editor' );
                return editor ? editor.getEditedPostContent() : '';
            }, [] );

            return [
                el( BlockControls, { key: 'controls' },
                    el( BlockAlignmentControl, {
                        value: attributes.align,
                        onChange: function( nextAlign ) { setAttributes( { align: nextAlign } ); }
                    } )
                ),
                
                el( InspectorControls, { key: 'inspector' },
                    el( PanelBody, { title: __( 'Table Settings', 'wp-changelog' ), initialOpen: true },
                        el( ToggleControl, {
                            label: __( 'Consolidate identical dates', 'wp-changelog' ),
                            help: __( 'Merges entries with the same date into a list within one row.', 'wp-changelog' ),
                            checked: attributes.consolidateDates,
                            onChange: function( value ) { setAttributes( { consolidateDates: value } ); }
                        } ),
                        el( SelectControl, {
                            label: __( 'Sort Order', 'wp-changelog' ),
                            value: attributes.sortOrder,
                            options: [
                                { label: __( 'Newest first (Descending)', 'wp-changelog' ), value: 'desc' },
                                { label: __( 'Oldest first (Ascending)', 'wp-changelog' ), value: 'asc' }
                            ],
                            onChange: function( value ) { setAttributes( { sortOrder: value } ); }
                        } ),
                        el( ToggleControl, {
                            label: __( 'Show Author', 'wp-changelog' ),
                            checked: attributes.showAuthor,
                            onChange: function( value ) { setAttributes( { showAuthor: value } ); }
                        } ),
                        el( ToggleControl, {
                            label: __( 'Fixed width table cells', 'wp-changelog' ),
                            checked: attributes.hasFixedLayout,
                            onChange: function( value ) { setAttributes( { hasFixedLayout: value } ); }
                        } )
                    )
                ),

                el( 'div', { key: 'preview', style: { border: '1px dashed #ccc', padding: '10px', backgroundColor: '#fafafa' } },
                    el( 'span', { style: { display: 'block', fontSize: '11px', color: '#999', marginBottom: '5px', textTransform: 'uppercase' } }, __( 'Table Live Preview:', 'wp-changelog' ) ),
                    el( wp.serverSideRender, {
                        block: 'wpc/change-table',
                        attributes: attributes,
                        // Der blocksContentHash zwingt die Komponente bei jedem Tastendruck/Inhaltswechsel zum Render-Update
                        urlQueryArgs: { post_id: currentPostId, trigger: blocksContentHash ? blocksContentHash.length : 0 }
                    } )
                )
            ];
        },
        save: function() { return null; }
    } );

} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.data, window.wp.i18n );
