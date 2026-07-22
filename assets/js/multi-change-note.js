/**
 * Registers the Multi Change Note block and its legacy slug.
 */
( function( blocks, element, components, data, i18n ) {
    var el = element.createElement;
    var TextControl = components.TextControl;
    var Button = components.Button;
    var wpc = window.wpcChangelog;
    var __ = i18n.__;

    /**
     * Register one Multi Change Note block variant.
     *
     * @param {string} blockName Block slug.
     * @param {Object} options   title and optional supports.
     */
    function registerMultiChangeNoteBlock( blockName, options ) {
        blocks.registerBlockType( blockName, {
            title: options.title,
            icon: 'list-view',
            category: 'common',
            supports: options.supports || {},
            attributes: {
                rows: { type: 'array', default: [] }
            },
            edit: function( props ) {
                var attributes = props.attributes;
                var setAttributes = props.setAttributes;
                var currentUser = data.select( 'core' ).getCurrentUser();
                var authorName = currentUser ? currentUser.name : '';

                element.useEffect( function() {
                    if ( ! attributes.rows || ! attributes.rows.length ) {
                        setAttributes( { rows: [ wpc.createRow( authorName ) ] } );
                    }
                }, [] );

                var rows = wpc.sortRowsByDate( attributes.rows || [] );

                function updateRows( nextRows ) {
                    setAttributes( { rows: wpc.sortRowsByDate( nextRows ) } );
                }

                function updateRow( rowId, patch ) {
                    updateRows( ( attributes.rows || [] ).map( function( row ) {
                        if ( row.id !== rowId ) {
                            return row;
                        }
                        return Object.assign( {}, row, patch );
                    } ) );
                }

                function addRow() {
                    updateRows( ( attributes.rows || [] ).concat( [ wpc.createRow( authorName ) ] ) );
                }

                function removeRow( rowId ) {
                    var nextRows = ( attributes.rows || [] ).filter( function( row ) {
                        return row.id !== rowId;
                    } );
                    updateRows( nextRows.length ? nextRows : [ wpc.createRow( authorName ) ] );
                }

                return el( 'div', { className: 'wpc-multi-note-wrap' },
                    el( 'table', { className: 'wpc-multi-note-table' },
                        el( 'thead', null,
                            el( 'tr', null,
                                el( 'th', { style: { width: '90px' } }, __( 'Date', 'wp-changelog' ) ),
                                el( 'th', null, __( 'Change', 'wp-changelog' ) ),
                                el( 'th', { style: { width: '110px' } }, __( 'Author', 'wp-changelog' ) ),
                                el( 'th', { style: { width: '36px' } }, '' )
                            )
                        ),
                        el( 'tbody', null,
                            rows.map( function( row ) {
                                return el( 'tr', { key: row.id },
                                    el( 'td', { className: 'wpc-minimal-input' },
                                        el( TextControl, {
                                            value: row.date,
                                            onChange: function( value ) { updateRow( row.id, { date: value } ); },
                                            placeholder: __( 'Date', 'wp-changelog' ),
                                            style: { height: '28px', fontSize: '12px' }
                                        } )
                                    ),
                                    el( 'td', { className: 'wpc-minimal-input' },
                                        el( TextControl, {
                                            value: row.comment,
                                            onChange: function( value ) {
                                                updateRow( row.id, {
                                                    comment: value,
                                                    changedAt: Math.floor( Date.now() / 1000 ),
                                                    author: row.author || authorName
                                                } );
                                            },
                                            placeholder: __( 'What was changed? (e.g. Fixed typo...)', 'wp-changelog' ),
                                            style: { height: '28px', fontSize: '12px' }
                                        } )
                                    ),
                                    el( 'td', { className: 'wpc-minimal-input', style: { opacity: '0.6' } },
                                        el( TextControl, {
                                            value: row.author || authorName || __( 'Loading...', 'wp-changelog' ),
                                            disabled: true,
                                            style: { height: '28px', fontSize: '12px' }
                                        } )
                                    ),
                                    el( 'td', null,
                                        el( Button, {
                                            icon: 'trash',
                                            label: __( 'Remove row', 'wp-changelog' ),
                                            isDestructive: true,
                                            isSmall: true,
                                            onClick: function() { removeRow( row.id ); }
                                        } )
                                    )
                                );
                            } )
                        )
                    ),
                    el( Button, {
                        variant: 'secondary',
                        isSmall: true,
                        onClick: addRow
                    }, __( 'Add row', 'wp-changelog' ) )
                );
            },
            save: function() { return null; }
        } );
    }

    registerMultiChangeNoteBlock( 'wpc/multi-change-note', {
        title: __( 'Multi Change Note', 'wp-changelog' )
    } );

    registerMultiChangeNoteBlock( 'wpc/multi-note', {
        title: __( 'Multi Change Note', 'wp-changelog' ),
        supports: { inserter: false }
    } );
} )( window.wp.blocks, window.wp.element, window.wp.components, window.wp.data, window.wp.i18n );
