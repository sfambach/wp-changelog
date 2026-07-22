/**
 * Shows the globally configured Change Log table at the bottom of the block editor.
 */
( function( element, data, apiFetch, i18n, hooks ) {
    var el = element.createElement;
    var useEffect = element.useEffect;
    var useState = element.useState;
    var useRef = element.useRef;
    var Fragment = element.Fragment;
    var __ = i18n.__;
    var useSelect = data.useSelect;
    var addFilter = hooks.addFilter;

    /**
     * Live preview of the globally appended Change Log table.
     */
    function GlobalChangeLogPreview() {
        var editorState = useSelect( function( select ) {
            var editor = select( 'core/editor' );
            var blockEditorStore = select( 'core/block-editor' );

            if ( ! editor ) {
                return null;
            }

            var blockOrder = blockEditorStore ? blockEditorStore.getBlockOrder() : [];

            return {
                postId: editor.getCurrentPostId(),
                postType: editor.getCurrentPostType(),
                postContent: editor.getEditedPostContent(),
                isSaving: editor.isSavingPost() && ! editor.isAutosavingPost(),
                lastBlockClientId: blockOrder.length ? blockOrder[ blockOrder.length - 1 ] : null
            };
        }, [] );

        var previewState = useState( {
            eligible: false,
            html: '',
            hiddenOnFrontend: false,
            loading: true
        } );
        var preview = previewState[ 0 ];
        var setPreview = previewState[ 1 ];
        var wasSavingRef = useRef( false );
        var refreshState = useState( 0 );
        var refreshToken = refreshState[ 0 ];
        var setRefreshToken = refreshState[ 1 ];

        useEffect( function() {
            if ( ! editorState || ! editorState.postId ) {
                setPreview( {
                    eligible: false,
                    html: '',
                    hiddenOnFrontend: false,
                    loading: false
                } );
                return;
            }

            var cancelled = false;

            setPreview( function( current ) {
                return Object.assign( {}, current, { loading: true } );
            } );

            apiFetch( {
                path: '/wp-changelog/v1/global-change-log-preview',
                method: 'POST',
                data: {
                    post_id: editorState.postId,
                    post_content: editorState.postContent || '',
                    trigger: refreshToken
                }
            } ).then( function( response ) {
                if ( cancelled ) {
                    return;
                }

                setPreview( {
                    eligible: !! response.eligible,
                    html: response.html || '',
                    hiddenOnFrontend: !! response.hiddenOnFrontend,
                    loading: false
                } );
            } ).catch( function() {
                if ( cancelled ) {
                    return;
                }

                setPreview( {
                    eligible: false,
                    html: '',
                    hiddenOnFrontend: false,
                    loading: false
                } );
            } );

            return function() {
                cancelled = true;
            };
        }, [
            editorState ? editorState.postId : 0,
            editorState ? editorState.postType : '',
            editorState ? editorState.postContent : '',
            refreshToken
        ] );

        useEffect( function() {
            if ( ! editorState ) {
                return;
            }

            if ( wasSavingRef.current && ! editorState.isSaving ) {
                setRefreshToken( function( token ) { return token + 1; } );
            }

            wasSavingRef.current = editorState.isSaving;
        }, [ editorState ? editorState.isSaving : false ] );

        if ( ! editorState || ! preview.eligible ) {
            return null;
        }

        return el(
            'div',
            {
                className: 'wpc-change-log-preview wpc-global-change-log-preview',
                'data-wpc-global-change-log': 'true'
            },
            el(
                'span',
                {
                    style: {
                        display: 'block',
                        fontSize: '11px',
                        color: '#999',
                        marginBottom: '5px',
                        textTransform: 'uppercase'
                    }
                },
                __( 'Global Change Log (automatic)', 'wp-changelog' )
            ),
            el(
                'span',
                {
                    style: {
                        display: 'block',
                        fontSize: '11px',
                        color: '#666',
                        marginBottom: '8px'
                    }
                },
                __( 'Configured under Settings → Change Log. A manual Change Log block on this page takes precedence.', 'wp-changelog' )
            ),
            preview.hiddenOnFrontend && el(
                'span',
                {
                    style: {
                        display: 'block',
                        fontSize: '11px',
                        color: '#996800',
                        marginBottom: '8px'
                    }
                },
                __( 'Hidden on the public site — editor preview only.', 'wp-changelog' )
            ),
            preview.loading
                ? el( 'p', { style: { margin: 0, fontSize: '12px', color: '#666' } }, __( 'Loading...', 'wp-changelog' ) )
                : el( 'div', { dangerouslySetInnerHTML: { __html: preview.html } } )
        );
    }

    /**
     * Append the global preview after the last block in the canvas.
     *
     * @param {Function} OriginalBlockEdit Original BlockEdit component.
     * @return {Function}
     */
    function withGlobalChangeLogPreview( OriginalBlockEdit ) {
        return function( props ) {
            var isLastBlock = useSelect( function( select ) {
                var blockEditorStore = select( 'core/block-editor' );

                if ( ! blockEditorStore ) {
                    return false;
                }

                var blockOrder = blockEditorStore.getBlockOrder();

                return blockOrder.length > 0 && blockOrder[ blockOrder.length - 1 ] === props.clientId;
            }, [ props.clientId ] );

            return el(
                Fragment,
                null,
                el( OriginalBlockEdit, props ),
                isLastBlock && el( GlobalChangeLogPreview, { key: 'wpc-global-change-log-preview' } )
            );
        };
    }

    addFilter( 'editor.BlockEdit', 'wpc/global-change-log-preview', withGlobalChangeLogPreview );
} )(
    window.wp.element,
    window.wp.data,
    window.wp.apiFetch,
    window.wp.i18n,
    window.wp.hooks
);
