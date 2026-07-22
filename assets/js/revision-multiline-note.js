/**

 * Revision Multiline Note block: revision-synced editable rows with table sorting.

 */

( function( blocks, element, blockEditor, components, data, i18n, apiFetch ) {

    var el = element.createElement;

    var TextControl = components.TextControl;

    var ToggleControl = components.ToggleControl;

    var SelectControl = components.SelectControl;

    var PanelBody = components.PanelBody;

    var InspectorControls = blockEditor.InspectorControls;

    var __ = i18n.__;

    var wpc = window.wpcChangelog;



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

     * Register one Revision Multiline Note block variant.

     *

     * @param {string} blockName Block slug.

     * @param {Object} options   title and optional supports.

     */

    function registerRevisionMultilineNoteBlock( blockName, options ) {

        blocks.registerBlockType( blockName, {

            title: options.title,

            icon: 'backup',

            category: 'common',

            supports: Object.assign( { html: false }, options.supports || {} ),

            attributes: {

                rows: { type: 'array', default: [] },

                sortField: { type: 'string', default: 'date' },

                sortOrder: { type: 'string', default: 'desc' },

                showAuthor: { type: 'boolean', default: true },

                includeCurrentVersion: { type: 'boolean', default: true }

            },

            edit: function( props ) {

                var attributes = props.attributes;

                var setAttributes = props.setAttributes;

                var useEffect = element.useEffect;

                var useState = element.useState;

                var useRef = element.useRef;

                var currentPostId = data.select( 'core/editor' ).getCurrentPostId();

                var currentUser = data.select( 'core' ).getCurrentUser();

                var authorName = currentUser ? currentUser.name : '';

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

                var wasSavingRef = useRef( false );

                var syncRequestRef = useRef( 0 );

                var rowsRef = useRef( attributes.rows || [] );

                var isInitialMountRef = useRef( true );

                var syncingState = useState( false );

                var isSyncing = syncingState[ 0 ];

                var setIsSyncing = syncingState[ 1 ];



                rowsRef.current = attributes.rows || [];



                useEffect( function() {

                    if ( wasSavingRef.current && ! isSaving ) {

                        setRefreshToken( function( t ) { return t + 1; } );

                    }

                    wasSavingRef.current = isSaving;

                }, [ isSaving ] );



                useEffect( function() {

                    if ( ! currentPostId || isSaving ) {

                        return;

                    }



                    if ( isInitialMountRef.current ) {

                        isInitialMountRef.current = false;

                        if ( rowsRef.current.length ) {

                            return;

                        }

                    }



                    var requestId = ++syncRequestRef.current;

                    setIsSyncing( true );



                    apiFetch( {

                        path: '/wp-changelog/v1/sync-revision-rows',

                        method: 'POST',

                        data: {

                            post_id: currentPostId,

                            rows: rowsRef.current,

                            includeCurrentVersion: attributes.includeCurrentVersion

                        }

                    } ).then( function( syncedRows ) {

                        if ( requestId !== syncRequestRef.current ) {

                            return;

                        }



                        var mergedRows = wpc.mergeRevisionSyncRows( rowsRef.current, syncedRows || [] );



                        if ( ! wpc.rowsEqual( rowsRef.current, mergedRows ) ) {

                            setAttributes( { rows: mergedRows } );

                        }

                    } ).catch( function() {

                        // Keep existing rows when sync is unavailable.

                    } ).finally( function() {

                        if ( requestId === syncRequestRef.current ) {

                            setIsSyncing( false );

                        }

                    } );

                }, [ currentPostId, refreshToken, attributes.includeCurrentVersion, isSaving ] );



                var rows = wpc.sortRowsByField(

                    attributes.rows || [],

                    attributes.sortField,

                    attributes.sortOrder

                );



                function updateRow( rowId, patch ) {

                    setAttributes( {

                        rows: ( attributes.rows || [] ).map( function( row ) {

                            if ( row.id !== rowId ) {

                                return row;

                            }



                            return Object.assign( {}, row, patch );

                        } )

                    } );

                }



                if ( ! currentPostId ) {

                    return el( 'p', { className: 'wpc-revision-multiline-note-notice' },

                        __( 'Please save the post as a draft to load the revisions.', 'wp-changelog' )

                    );

                }



                return [

                    el( InspectorControls, { key: 'inspector' },

                        el( PanelBody, { title: __( 'Table Settings', 'wp-changelog' ), initialOpen: true },

                            el( SelectControl, {

                                label: __( 'Order by', 'wp-changelog' ),

                                value: attributes.sortField,

                                options: sortFieldOptions,

                                onChange: function( value ) { setAttributes( { sortField: value } ); }

                            } ),

                            el( SelectControl, {

                                label: __( 'Sort direction', 'wp-changelog' ),

                                value: attributes.sortOrder,

                                options: sortOrderOptions,

                                onChange: function( value ) { setAttributes( { sortOrder: value } ); }

                            } ),

                            el( ToggleControl, {

                                label: __( 'Show Author', 'wp-changelog' ),

                                checked: attributes.showAuthor,

                                onChange: function( value ) { setAttributes( { showAuthor: value } ); }

                            } ),

                            el( ToggleControl, {

                                label: __( 'Include current revision', 'wp-changelog' ),

                                checked: attributes.includeCurrentVersion,

                                onChange: function( value ) { setAttributes( { includeCurrentVersion: value } ); }

                            } )

                        )

                    ),

                    el( 'span', {
                        key: 'editor-only-notice',
                        style: { display: 'block', fontSize: '11px', color: '#996800', marginBottom: '8px' }
                    }, __( 'Hidden on the public site — editor preview only.', 'wp-changelog' ) ),

                    el( 'div', { key: 'editor', className: 'wpc-multi-note-wrap wpc-revision-multiline-note' },

                        isSyncing && el( 'p', { className: 'wpc-revision-multiline-note-notice' },

                            __( 'Syncing revision dates…', 'wp-changelog' )

                        ),

                        ! rows.length && ! isSyncing && el( 'p', { className: 'wpc-revision-multiline-note-notice' },

                            __( 'No post revisions found yet. Save the post to create revisions.', 'wp-changelog' )

                        ),

                        !! rows.length && el( 'table', { className: 'wpc-multi-note-table' },

                            el( 'thead', null,

                                el( 'tr', null,

                                    el( 'th', { style: { width: '90px' } }, __( 'Date', 'wp-changelog' ) ),

                                    el( 'th', null, __( 'Change', 'wp-changelog' ) ),

                                    attributes.showAuthor && el( 'th', { style: { width: '110px' } }, __( 'Author', 'wp-changelog' ) )

                                )

                            ),

                            el( 'tbody', null,

                                rows.map( function( row ) {

                                    return el( 'tr', { key: row.id },

                                        el( 'td', { className: 'wpc-minimal-input', style: { opacity: '0.6' } },

                                            el( TextControl, {

                                                value: row.date || '',

                                                disabled: true,

                                                style: { height: '28px', fontSize: '12px' }

                                            } )

                                        ),

                                        el( 'td', { className: 'wpc-minimal-input' },

                                            el( TextControl, {

                                                value: row.comment || '',

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

                                        attributes.showAuthor && el( 'td', { className: 'wpc-minimal-input', style: { opacity: '0.6' } },

                                            el( TextControl, {

                                                value: row.author || authorName || __( 'Loading...', 'wp-changelog' ),

                                                disabled: true,

                                                style: { height: '28px', fontSize: '12px' }

                                            } )

                                        )

                                    );

                                } )

                            )

                        )

                    )

                ];

            },

            save: function() { return null; }

        } );

    }



    registerRevisionMultilineNoteBlock( 'wpc/revision-multiline-note', {

        title: __( 'Revision Multiline Note', 'wp-changelog' )

    } );



    registerRevisionMultilineNoteBlock( 'wpc/version-multiline-note', {

        title: __( 'Revision Multiline Note', 'wp-changelog' ),

        supports: { inserter: false }

    } );



    registerRevisionMultilineNoteBlock( 'wpc/generated-multiline-note', {

        title: __( 'Revision Multiline Note', 'wp-changelog' ),

        supports: { inserter: false }

    } );

} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.data, window.wp.i18n, window.wp.apiFetch );


