/**
 * Registers the Change Log block, sidebar controls, and live server preview.
 */
( function( blocks, element, blockEditor, components, data, i18n ) {
    var el = element.createElement;
    var ToggleControl = components.ToggleControl;
    var SelectControl = components.SelectControl;
    var PanelBody = components.PanelBody;
    var InspectorControls = blockEditor.InspectorControls;
    var BlockControls = blockEditor.BlockControls;
    var BlockAlignmentControl = blockEditor.BlockAlignmentControl;
    var __ = i18n.__;

    /**
     * Register one Change Log block variant.
     *
     * @param {string} blockName Block slug.
     * @param {Object} options   title and optional supports.
     */
    function registerChangeLogBlock( blockName, options ) {
        blocks.registerBlockType( blockName, {
            title: options.title,
            icon: 'editor-table',
            category: 'common',
            supports: Object.assign(
                {
                    align: [ 'left', 'center', 'right', 'wide', 'full' ],
                    className: true,
                    styles: true
                },
                options.supports || {}
            ),
            attributes: {
                showAuthor: { type: 'boolean', default: true },
                hasFixedLayout: { type: 'boolean', default: false },
                consolidateDates: { type: 'boolean', default: false },
                listChanges: { type: 'boolean', default: false },
                changeFieldSort: { type: 'string', default: 'time' },
                changeFieldOrder: { type: 'string', default: 'newest_first' },
                sortOrder: { type: 'string', default: 'desc' },
                visibleOnPage: { type: 'boolean', default: true },
                tableStyle: { type: 'string', default: 'default' },
                align: { type: 'string', default: '' },
                className: { type: 'string', default: '' }
            },
            edit: function( props ) {
                var attributes = props.attributes;
                var setAttributes = props.setAttributes;
                var useEffect = element.useEffect;
                var useState = element.useState;
                var currentPostId = data.select( 'core/editor' ).getCurrentPostId();
                var useSelect = typeof data.useSelect === 'function' ? data.useSelect : null;
                var isSaving = useSelect
                    ? useSelect( function( select ) {
                        var editor = select( 'core/editor' );
                        return editor ? ( editor.isSavingPost() && ! editor.isAutosavingPost() ) : false;
                    }, [] )
                    : false;
                var refreshState = useState( 0 );
                var refreshToken = refreshState[ 0 ];
                var setRefreshToken = refreshState[ 1 ];
                var wasSavingRef = element.useRef( false );

                // Refresh the server preview only after a manual save, not on autosave.
                useEffect( function() {
                    if ( wasSavingRef.current && ! isSaving ) {
                        setRefreshToken( function( t ) { return t + 1; } );
                    }
                    wasSavingRef.current = isSaving;
                }, [ isSaving ] );

                // Migrate legacy asc/desc values to explicit oldest_first / newest_first.
                useEffect( function() {
                    if ( attributes.changeFieldOrder === 'asc' ) {
                        setAttributes( { changeFieldOrder: 'oldest_first' } );
                    } else if ( attributes.changeFieldOrder === 'desc' ) {
                        setAttributes( { changeFieldOrder: 'newest_first' } );
                    }
                }, [ attributes.changeFieldOrder ] );

                return [
                    el( BlockControls, { key: 'controls' },
                        el( BlockAlignmentControl, {
                            value: attributes.align,
                            onChange: function( nextAlign ) { setAttributes( { align: nextAlign } ); }
                        } )
                    ),
                    el( InspectorControls, { key: 'inspector' },
                        el( PanelBody, { title: __( 'General Settings', 'wp-changelog' ), initialOpen: true },
                            el( SelectControl, {
                                label: __( 'Sort Order', 'wp-changelog' ),
                                help: __( 'Which date row appears at the top of the table.', 'wp-changelog' ),
                                value: attributes.sortOrder,
                                options: [
                                    { label: __( 'Newest on top', 'wp-changelog' ), value: 'desc' },
                                    { label: __( 'Oldest on top', 'wp-changelog' ), value: 'asc' }
                                ],
                                onChange: function( value ) { setAttributes( { sortOrder: value } ); }
                            } ),
                            el( ToggleControl, {
                                label: __( 'Visible on page', 'wp-changelog' ),
                                help: __( 'When off, the table is shown in the editor only and hidden on the public site.', 'wp-changelog' ),
                                checked: attributes.visibleOnPage !== false,
                                onChange: function( value ) { setAttributes( { visibleOnPage: value } ); }
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
                            } ),
                            el( SelectControl, {
                                label: __( 'Table style', 'wp-changelog' ),
                                value: attributes.tableStyle || 'default',
                                options: [
                                    { label: __( 'Default', 'wp-changelog' ), value: 'default' },
                                    { label: __( 'Stripes', 'wp-changelog' ), value: 'stripes' }
                                ],
                                onChange: function( value ) { setAttributes( { tableStyle: value } ); }
                            } )
                        ),
                        el( PanelBody, { title: __( 'Changefield Options', 'wp-changelog' ), initialOpen: true },
                            el( ToggleControl, {
                                label: __( 'Consolidate identical dates', 'wp-changelog' ),
                                help: __( 'Merges entries with the same date and author into one row.', 'wp-changelog' ),
                                checked: attributes.consolidateDates,
                                onChange: function( value ) { setAttributes( { consolidateDates: value } ); }
                            } ),
                            el( ToggleControl, {
                                label: __( 'List for changes', 'wp-changelog' ),
                                help: __( 'Always display changes as a bullet list in the Change column.', 'wp-changelog' ),
                                checked: attributes.listChanges,
                                onChange: function( value ) { setAttributes( { listChanges: value } ); }
                            } ),
                            attributes.consolidateDates && el( SelectControl, {
                                label: __( 'Order merged changes by', 'wp-changelog' ),
                                value: attributes.changeFieldSort,
                                options: [
                                    { label: __( 'Time of change', 'wp-changelog' ), value: 'time' },
                                    { label: __( 'Alphabetically', 'wp-changelog' ), value: 'alpha' }
                                ],
                                onChange: function( value ) { setAttributes( { changeFieldSort: value } ); }
                            } ),
                            attributes.consolidateDates && el( SelectControl, {
                                label: __( 'Merged changes sort order', 'wp-changelog' ),
                                help: __( 'Controls the order of multiple changes within one table cell.', 'wp-changelog' ),
                                value: attributes.changeFieldOrder,
                                options: [
                                    { label: __( 'Oldest on top', 'wp-changelog' ), value: 'oldest_first' },
                                    { label: __( 'Newest on top', 'wp-changelog' ), value: 'newest_first' }
                                ],
                                onChange: function( value ) { setAttributes( { changeFieldOrder: value } ); }
                            } )
                        )
                    ),
                    el( 'div', { key: 'preview', className: 'wpc-change-log-preview' },
                        el( 'span', { style: { display: 'block', fontSize: '11px', color: '#999', marginBottom: '5px', textTransform: 'uppercase' } }, __( 'Table Live Preview:', 'wp-changelog' ) ),
                        attributes.visibleOnPage === false && el( 'span', {
                            style: { display: 'block', fontSize: '11px', color: '#996800', marginBottom: '8px' }
                        }, __( 'Hidden on the public site — editor preview only.', 'wp-changelog' ) ),
                        el( wp.serverSideRender, {
                            block: blockName,
                            attributes: attributes,
                            urlQueryArgs: {
                                post_id: currentPostId || 0,
                                trigger: refreshToken
                            }
                        } )
                    )
                ];
            },
            save: function() { return null; }
        } );
    }

    registerChangeLogBlock( 'wpc/change-log', {
        title: __( 'Change Log', 'wp-changelog' )
    } );

    registerChangeLogBlock( 'wpc/change-table', {
        title: __( 'Change Log', 'wp-changelog' ),
        supports: { inserter: false }
    } );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.data, window.wp.i18n );
