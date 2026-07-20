( function( blocks, element, blockEditor, components, data, i18n ) {
    var el = element.createElement;
    var TextControl = components.TextControl;
    var ToggleControl = components.ToggleControl;
    var PanelBody = components.PanelBody;

    var InspectorControls = blockEditor.InspectorControls;
    var BlockControls = blockEditor.BlockControls;
    var BlockAlignmentControl = blockEditor.BlockAlignmentControl;
    
    // Internationalisierung Shortcut
    var __ = i18n.__;

    /**
     * BLOCK 1: Der Änderungs-Eintrag
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

            return el( 'div', { style: { padding: '15px', border: '1px dashed #007cba', backgroundColor: '#f0f6fa', marginBottom: '10px' } },
                el( 'p', { style: { margin: '0 0 8px 0', fontWeight: 'bold', color: '#007cba' } }, 
                    '📝 ' + __( 'Internal change from:', 'wp-changelog' ) + ' ' + attributes.date + ' (' + __( 'Author:', 'wp-changelog' ) + ' ' + (attributes.author || __( 'Loading...', 'wp-changelog' )) + ')'
                ),
                el( TextControl, {
                    label: __( 'What was changed?', 'wp-changelog' ),
                    value: attributes.comment,
                    onChange: function( value ) {
                        setAttributes( { comment: value } );
                    },
                    placeholder: __( 'e.g. Fixed typo, replaced header image...', 'wp-changelog' )
                } )
            );
        },
        save: function() {
            return null;
        }
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
                    __( 'Please save the post as a draft to enable the live preview of the table.', 'wp-changelog' )
                );
            }

            return [
                el( BlockControls, { key: 'controls' },
                    el( BlockAlignmentControl, {
                        value: attributes.align,
                        onChange: function( nextAlign ) {
                            setAttributes( { align: nextAlign } );
                        }
                    } )
                ),
                
                el( InspectorControls, { key: 'inspector' },
                    el( PanelBody, { title: __( 'Table Settings', 'wp-changelog' ), initialOpen: true },
                        el( ToggleControl, {
                            label: __( 'Show Author', 'wp-changelog' ),
                            help: __( 'Displays the name of the author who logged the change.', 'wp-changelog' ),
                            checked: attributes.showAuthor,
                            onChange: function( value ) {
                                setAttributes( { showAuthor: value } );
                            }
                        } ),
                        el( ToggleControl, {
                            label: __( 'Fixed width table cells', 'wp-changelog' ),
                            help: __( 'Defines fixed column widths instead of variable widths.', 'wp-changelog' ),
                            checked: attributes.hasFixedLayout,
                            onChange: function( value ) {
                                setAttributes( { hasFixedLayout: value } );
                            }
                        } )
                    )
                ),

                el( 'div', { key: 'preview', style: { border: '1px dashed #ccc', padding: '10px', backgroundColor: '#fafafa' } },
                    el( 'span', { style: { display: 'block', fontSize: '11px', color: '#999', marginBottom: '5px', textTransform: 'uppercase' } }, __( 'Table Live Preview:', 'wp-changelog' ) ),
                    el( wp.serverSideRender, {
                        block: 'wpc/change-table',
                        attributes: attributes
                    } )
                )
            ];
        },
        save: function() {
            return null;
        }
    } );

} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.data, window.wp.i18n ); // window.wp.i18n übergeben!
