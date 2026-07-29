/**
 * Registers the Multi Change Note block and its legacy slug.
 */
( function( blocks, element, blockEditor, components, data, i18n ) {
    var el = element.createElement;
    var TextControl = components.TextControl;
    var SelectControl = components.SelectControl;
    var PanelBody = components.PanelBody;
    var Button = components.Button;
    var InspectorControls = blockEditor.InspectorControls;
    var wpc = window.wpcChangelog;
    var __ = i18n.__;

    var sortFieldOptions = [
        { label: __( 'Date', 'wp-changelog' ), value: 'date' },
        { label: __( 'Change', 'wp-changelog' ), value: 'comment' },
        { label: __( 'Author', 'wp-changelog' ), value: 'author' }
    ];

    var sortOrderOptions = [
        { label: __( 'Oldest on top', 'wp-changelog' ), value: 'asc' },
        { label: __( 'Newest on top', 'wp-changelog' ), value: 'desc' }
    ];

    /**
     * Register one Multi Change Note block variant.
     *
     * @param {string} blockName Block slug.
     * @param {Object} options   title and optional supports.
     */
    function registerMultiChangeNoteBlock( blockName, options ) {
        var blockTitle = options.title || '';

        blocks.registerBlockType( blockName, {
            title: options.title,
            icon: 'list-view',
            category: 'common',
            supports: options.supports || {},
            attributes: {
                rows: { type: 'array', default: [] },
                sortField: { type: 'string', default: 'date' },
                sortOrder: { type: 'string', default: 'desc' }
            },
            edit: function( props ) {
                var attributes = props.attributes;
                var setAttributes = props.setAttributes;
                var useEffect = element.useEffect;
                var useRef = element.useRef;
                var useSelect = typeof data.useSelect === 'function' ? data.useSelect : null;
                var currentUser = data.select( 'core' ).getCurrentUser();
                var authorName = currentUser ? currentUser.name : '';
                var sortField = attributes.sortField || 'date';
                var sortOrder = attributes.sortOrder === 'asc' ? 'asc' : 'desc';
                var isSaving = useSelect
                    ? useSelect( function( select ) {
                        var editor = select( 'core/editor' );
                        return editor ? ( editor.isSavingPost() && ! editor.isAutosavingPost() ) : false;
                    }, [] )
                    : false;
                var wasSavingRef = useRef( false );

                // Always show rows according to sidebar sort settings.
                var rows = wpc.sortRowsByField( attributes.rows || [], sortField, sortOrder );

                useEffect( function() {
                    if ( ! attributes.rows || ! attributes.rows.length ) {
                        setAttributes( { rows: [ wpc.createRow( authorName ) ] } );
                    }
                }, [] );

                // Persist sorted order after manual save (PHP also sorts on save).
                useEffect( function() {
                    if ( wasSavingRef.current && ! isSaving ) {
                        var currentRows = attributes.rows || [];
                        var sorted = wpc.sortRowsByField( currentRows, sortField, sortOrder );
                        var changed = sorted.length !== currentRows.length;

                        if ( ! changed ) {
                            for ( var i = 0; i < sorted.length; i++ ) {
                                if ( ! currentRows[ i ] || sorted[ i ].id !== currentRows[ i ].id ) {
                                    changed = true;
                                    break;
                                }
                            }
                        }

                        if ( changed ) {
                            var markNotPersistent = data.dispatch( 'core/block-editor' ).__unstableMarkNextChangeAsNotPersistent;
                            if ( typeof markNotPersistent === 'function' ) {
                                markNotPersistent();
                            }
                            setAttributes( { rows: sorted } );
                        }
                    }
                    wasSavingRef.current = isSaving;
                }, [ isSaving, sortField, sortOrder ] );

                function updateRows( nextRows ) {
                    setAttributes( { rows: nextRows } );
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

                return [
                    el( InspectorControls, { key: 'inspector' },
                        el( PanelBody, { title: __( 'Table Settings', 'wp-changelog' ), initialOpen: true },
                            el( SelectControl, {
                                label: __( 'Order by', 'wp-changelog' ),
                                value: sortField,
                                options: sortFieldOptions,
                                onChange: function( value ) { setAttributes( { sortField: value } ); }
                            } ),
                            el( SelectControl, {
                                label: __( 'Sort direction', 'wp-changelog' ),
                                value: sortOrder,
                                options: sortOrderOptions,
                                onChange: function( value ) { setAttributes( { sortOrder: value } ); }
                            } )
                        )
                    ),
                    el( 'div', { key: 'editor', className: 'wpc-block-surface-wrap' },
                        wpc.renderEditorBlockLabel( el, blockTitle ),
                        el( 'div', { className: 'wpc-multi-note-wrap' },
                            el( 'table', { className: 'wpc-multi-note-table' },
                                el( 'thead', null,
                                    el( 'tr', null,
                                        el( 'th', { className: 'wpc-changelog-col-date' }, __( 'Date', 'wp-changelog' ) ),
                                        el( 'th', { className: 'wpc-changelog-col-change' }, __( 'Change', 'wp-changelog' ) ),
                                        el( 'th', { className: 'wpc-changelog-col-author' }, __( 'Author', 'wp-changelog' ) ),
                                        el( 'th', { className: 'wpc-changelog-col-actions' }, '' )
                                    )
                                ),
                                el( 'tbody', null,
                                    rows.map( function( row ) {
                                        return el( 'tr', { key: row.id },
                                            el( 'td', { className: 'wpc-minimal-input wpc-changelog-col-date' },
                                                el( TextControl, {
                                                    value: row.date,
                                                    onChange: function( value ) { updateRow( row.id, { date: value } ); },
                                                    placeholder: __( 'Date', 'wp-changelog' ),
                                                    style: { height: '28px', fontSize: '12px' }
                                                } )
                                            ),
                                            el( 'td', { className: 'wpc-minimal-input wpc-changelog-col-change' },
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
                                            el( 'td', { className: 'wpc-minimal-input wpc-changelog-col-author', style: { opacity: '0.6' } },
                                                el( TextControl, {
                                                    value: row.author || authorName || __( 'Loading...', 'wp-changelog' ),
                                                    disabled: true,
                                                    style: { height: '28px', fontSize: '12px' }
                                                } )
                                            ),
                                            el( 'td', { className: 'wpc-changelog-col-actions' },
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
                            el( 'div', { className: 'wpc-multi-note-actions' },
                                el( Button, {
                                    variant: 'secondary',
                                    isSmall: true,
                                    onClick: addRow
                                }, __( 'Add row', 'wp-changelog' ) ),
                                el( Button, {
                                    variant: 'primary',
                                    isSmall: true,
                                    onClick: function() {
                                        var synced = wpc.syncMissingDatesFromPage( attributes.rows || [], data );
                                        updateRows( wpc.sortRowsByField( synced, sortField, sortOrder ) );
                                    }
                                }, __( 'Sync missing dates from page', 'wp-changelog' ) )
                            )
                        )
                    )
                ];
            },
            save: function() { return null; }
        } );
    }

    registerMultiChangeNoteBlock( 'wpc/multi-change-note', {
        title: __( 'Log-Liste', 'wp-changelog' )
    } );

    registerMultiChangeNoteBlock( 'wpc/multi-note', {
        title: __( 'Log-Liste', 'wp-changelog' ),
        supports: { inserter: false }
    } );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.data, window.wp.i18n );
