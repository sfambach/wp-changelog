/**
 * Shared editor helpers for WP Changelog blocks.
 *
 * @namespace wpcChangelog
 */
window.wpcChangelog = window.wpcChangelog || {};

/**
 * Format today's date as d.m.Y.
 *
 * @returns {string}
 */
window.wpcChangelog.formatToday = function() {
    var today = new Date();
    return today.getDate().toString().padStart( 2, '0' ) + '.' +
        ( today.getMonth() + 1 ).toString().padStart( 2, '0' ) + '.' +
        today.getFullYear();
};

/**
 * Convert a d.m.Y string into a sortable numeric value.
 *
 * @param {string} dateStr Date in d.m.Y format.
 * @returns {number}
 */
window.wpcChangelog.parseDateSortValue = function( dateStr ) {
    if ( ! dateStr ) {
        return 0;
    }
    var parts = dateStr.split( '.' );
    if ( parts.length !== 3 ) {
        return 0;
    }
    return ( parseInt( parts[2], 10 ) * 10000 ) + ( parseInt( parts[1], 10 ) * 100 ) + parseInt( parts[0], 10 );
};

/**
 * Sort multi-note rows by date, then by changedAt.
 *
 * @param {Array<Object>} rows Multi Change Note rows.
 * @returns {Array<Object>} New sorted array.
 */
window.wpcChangelog.sortRowsByDate = function( rows ) {
    return rows.slice().sort( function( a, b ) {
        var dateCmp = window.wpcChangelog.parseDateSortValue( a.date ) - window.wpcChangelog.parseDateSortValue( b.date );
        if ( dateCmp !== 0 ) {
            return dateCmp;
        }
        return ( a.changedAt || 0 ) - ( b.changedAt || 0 );
    } );
};

/**
 * Sort rows by date, comment, or author using asc/desc order.
 *
 * Matches the Change Log table sort controls used elsewhere in the plugin.
 *
 * @param {Array<Object>} rows      Row objects.
 * @param {string}        sortField "date", "comment", or "author".
 * @param {string}        sortOrder "asc" or "desc".
 * @returns {Array<Object>} New sorted array.
 */
window.wpcChangelog.sortRowsByField = function( rows, sortField, sortOrder ) {
    var field = sortField || 'date';
    var order = sortOrder === 'asc' ? 1 : -1;

    return rows.slice().sort( function( a, b ) {
        var cmp = 0;

        if ( field === 'comment' ) {
            cmp = ( a.comment || '' ).localeCompare( b.comment || '', undefined, { sensitivity: 'base' } );
        } else if ( field === 'author' ) {
            cmp = ( a.author || '' ).localeCompare( b.author || '', undefined, { sensitivity: 'base' } );
        } else {
            cmp = window.wpcChangelog.parseDateSortValue( a.date ) - window.wpcChangelog.parseDateSortValue( b.date );
        }

        if ( cmp === 0 ) {
            cmp = ( a.changedAt || 0 ) - ( b.changedAt || 0 );
        }

        return cmp * order;
    } );
};

/**
 * Compare two row arrays for shallow equality of sync-relevant fields.
 *
 * @param {Array<Object>} left  First row list.
 * @param {Array<Object>} right Second row list.
 * @returns {boolean}
 */
window.wpcChangelog.rowsEqual = function( left, right ) {
    if ( ! left || ! right || left.length !== right.length ) {
        return false;
    }

    var rightById = {};
    right.forEach( function( row ) {
        if ( row && row.id ) {
            rightById[ row.id ] = row;
        }
    } );

    for ( var i = 0; i < left.length; i++ ) {
        var a = left[ i ];
        var b = rightById[ a.id ];

        if ( ! b || a.date !== b.date || a.comment !== b.comment || a.author !== b.author ) {
            return false;
        }
    }

    return true;
};

/**
 * Merge server-synced revision rows with local editor rows.
 *
 * Keeps user-entered comments when the server response is based on stale data.
 *
 * @param {Array<Object>} localRows  Current editor rows.
 * @param {Array<Object>} serverRows Rows returned by the sync endpoint.
 * @returns {Array<Object>}
 */
window.wpcChangelog.mergeRevisionSyncRows = function( localRows, serverRows ) {
    var byDate = {};
    var byId = {};

    ( localRows || [] ).forEach( function( row ) {
        if ( row.date ) {
            byDate[ row.date ] = row;
        }
        if ( row.id ) {
            byId[ row.id ] = row;
        }
    } );

    return ( serverRows || [] ).map( function( serverRow ) {
        var local = ( serverRow.date && byDate[ serverRow.date ] ) || ( serverRow.id && byId[ serverRow.id ] );

        if ( ! local ) {
            return serverRow;
        }

        return Object.assign( {}, serverRow, {
            id: local.id || serverRow.id,
            comment: typeof local.comment === 'string' ? local.comment : ( serverRow.comment || '' ),
            changedAt: local.changedAt || serverRow.changedAt || 0
        } );
    } );
};

/**
 * Create a default row object for the Multi Change Note block.
 *
 * @param {string} authorName Current editor user display name.
 * @returns {{id: string, date: string, comment: string, author: string, changedAt: number}}
 */
window.wpcChangelog.createRow = function( authorName ) {
    return {
        id: 'row-' + Date.now() + '-' + Math.random().toString( 36 ).slice( 2 ),
        date: window.wpcChangelog.formatToday(),
        comment: '',
        author: authorName || '',
        changedAt: Math.floor( Date.now() / 1000 )
    };
};

/**
 * Format an ISO datetime as d.m.Y.
 *
 * @param {string} isoDate ISO date string.
 * @returns {string}
 */
window.wpcChangelog.formatDateFromIso = function( isoDate ) {
    if ( ! isoDate ) {
        return '';
    }

    var parsed = new Date( isoDate );
    if ( Number.isNaN( parsed.getTime() ) ) {
        return '';
    }

    return parsed.getDate().toString().padStart( 2, '0' ) + '.' +
        ( parsed.getMonth() + 1 ).toString().padStart( 2, '0' ) + '.' +
        parsed.getFullYear();
};

/**
 * Collect changelog dates already present on the page (notes + post date).
 *
 * @param {Object} data wp.data module.
 * @returns {Map<string, {date: string, author: string, changedAt: number}>}
 */
window.wpcChangelog.collectPageChangelogDates = function( data ) {
    var dates = new Map();
    var editorBlocks = data.select( 'core/block-editor' ).getBlocks();
    var postDate = data.select( 'core/editor' ).getEditedPostAttribute( 'date' );
    var postDateFormatted = window.wpcChangelog.formatDateFromIso( postDate );
    var currentUser = data.select( 'core' ).getCurrentUser();
    var authorName = currentUser ? currentUser.name : '';

    function walk( blockList ) {
        ( blockList || [] ).forEach( function( block ) {
            if (
                ( block.name === 'wpc/change-item' || block.name === 'wpc/single-change-note' ) &&
                block.attributes &&
                block.attributes.date
            ) {
                dates.set( block.attributes.date, {
                    date: block.attributes.date,
                    author: block.attributes.author || authorName,
                    changedAt: block.attributes.changedAt || 0
                } );
            }

            if ( block.innerBlocks && block.innerBlocks.length ) {
                walk( block.innerBlocks );
            }
        } );
    }

    if ( postDateFormatted ) {
        dates.set( postDateFormatted, {
            date: postDateFormatted,
            author: authorName,
            changedAt: postDate ? Math.floor( new Date( postDate ).getTime() / 1000 ) : 0
        } );
    }

    walk( editorBlocks );
    return dates;
};

/**
 * Add missing page changelog dates to Multi Change Note rows.
 *
 * @param {Array<Object>} currentRows Existing rows.
 * @param {Object}        data        wp.data module.
 * @returns {Array<Object>}
 */
window.wpcChangelog.syncMissingDatesFromPage = function( currentRows, data ) {
    var pageDates = window.wpcChangelog.collectPageChangelogDates( data );
    var existingDates = new Set(
        ( currentRows || [] ).map( function( row ) {
            return row.date;
        } )
    );
    var nextRows = ( currentRows || [] ).slice();
    var currentUser = data.select( 'core' ).getCurrentUser();
    var defaultAuthor = currentUser ? currentUser.name : '';
    var now = Math.floor( Date.now() / 1000 );

    pageDates.forEach( function( info, date ) {
        if ( existingDates.has( date ) ) {
            return;
        }

        nextRows.push(
            Object.assign( window.wpcChangelog.createRow( info.author || defaultAuthor ), {
                date: date,
                comment: '',
                author: info.author || defaultAuthor,
                changedAt: info.changedAt || now
            } )
        );
        existingDates.add( date );
    } );

    return window.wpcChangelog.sortRowsByDate( nextRows );
};

/**
 * Default table caption by document language.
 *
 * @returns {string}
 */
window.wpcChangelog.getDefaultTableCaption = function() {
    var lang = ( document.documentElement.lang || '' ).toLowerCase();
    if ( 0 === lang.indexOf( 'de' ) ) {
        return 'Logbuch';
    }
    return 'Changelog';
};

/**
 * Small editor-only label showing the block display name.
 *
 * @param {Function} el    wp.element.createElement.
 * @param {string}   title Block title shown in the inserter.
 * @returns {*} React element.
 */
window.wpcChangelog.renderEditorBlockLabel = function( el, title ) {
    return el( 'span', {
        className: 'wpc-editor-block-label',
        style: {
            display: 'block',
            fontSize: '11px',
            fontWeight: '600',
            color: '#1e1e1e',
            marginBottom: '6px',
            letterSpacing: '0.02em'
        }
    }, title || '' );
};

/**
 * Whether a paragraph/rich-text content value is empty.
 *
 * WordPress 6.5+ may store empty rich-text as `{}` instead of `''`.
 *
 * @param {*} content Paragraph content attribute.
 * @returns {boolean}
 */
window.wpcChangelog.isRichTextEmpty = function( content ) {
    if ( content == null || content === '' ) {
        return true;
    }

    if ( typeof content === 'string' ) {
        return content.replace( /<[^>]*>/g, '' ).trim() === '';
    }

    if ( typeof content === 'object' ) {
        if ( typeof content.text === 'string' ) {
            return content.text.trim() === '';
        }
        if ( typeof content.toHTMLString === 'function' ) {
            return content.toHTMLString().replace( /<[^>]*>/g, '' ).trim() === '';
        }
        return Object.keys( content ).length === 0;
    }

    return false;
};

/**
 * Focus the RichText caret of the currently selected paragraph block.
 *
 * @returns {void}
 */
window.wpcChangelog.focusSelectedParagraph = function() {
    var selected = document.querySelector(
        '.block-editor-block-list__block.is-selected[data-type="core/paragraph"] [contenteditable="true"]'
    );
    if ( selected && typeof selected.focus === 'function' ) {
        selected.focus();
    }
};

/**
 * Register a Single Change Note block variant (current or legacy slug).
 *
 * @param {Object} blocks     wp.blocks module.
 * @param {Object} element    wp.element module.
 * @param {Object} components wp.components module.
 * @param {Object} data       wp.data module.
 * @param {Object} i18n       wp.i18n module.
 * @param {string} blockName  Block slug to register.
 * @param {Object} options    Block options: title, icon, supports.
 * @returns {void}
 */
window.wpcChangelog.registerNoteBlock = function( blocks, element, components, data, i18n, blockName, options ) {
    var el = element.createElement;
    var TextControl = components.TextControl;
    var __ = i18n.__;
    var wpc = window.wpcChangelog;
    var supports = options.supports || {};
    var blockTitle = options.title || '';
    var editorSettings = window.wpcChangelogSettings || {};
    var useBlockProps = ( window.wp.blockEditor && window.wp.blockEditor.useBlockProps ) || function( extra ) {
        return extra || {};
    };
    var blockSettings = {
        apiVersion: 3,
        title: options.title,
        icon: options.icon || 'edit',
        category: 'common',
        supports: supports,
        attributes: {
            date: { type: 'string', default: '' },
            comment: { type: 'string', default: '' },
            author: { type: 'string', default: '' },
            changedAt: { type: 'number', default: 0 }
        },
        edit: function( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var isSelected = props.isSelected;
            var clientId = props.clientId;
            var currentUser = data.select( 'core' ).getCurrentUser();
            var changeFieldRef = element.useRef( null );
            var didFocusChangeRef = element.useRef( false );
            var blockProps = useBlockProps( {
                className: isSelected
                    ? 'wpc-block-surface-wrap wpc-single-note-active'
                    : 'wpc-block-surface-wrap wpc-single-note-idle'
            } );

            /**
             * Enter in the Change field inserts/selects a paragraph after this block.
             *
             * @param {KeyboardEvent} event Key event from the Change input.
             * @returns {void}
             */
            function leaveBlockOnEnter( event ) {
                if ( event.key !== 'Enter' || event.shiftKey || event.altKey || event.ctrlKey || event.metaKey ) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                // Blur the note input so the new paragraph can take writing focus.
                if ( event.target && typeof event.target.blur === 'function' ) {
                    event.target.blur();
                }

                var blockEditorSelect = data.select( 'core/block-editor' );
                var blockEditorDispatch = data.dispatch( 'core/block-editor' );
                var nextClientId = blockEditorSelect.getNextBlockClientId( clientId );

                if ( nextClientId ) {
                    var nextBlock = blockEditorSelect.getBlock( nextClientId );
                    if (
                        nextBlock &&
                        nextBlock.name === 'core/paragraph' &&
                        wpc.isRichTextEmpty( nextBlock.attributes.content )
                    ) {
                        blockEditorDispatch.selectBlock( nextClientId, 0 );
                        window.setTimeout( function() {
                            wpc.focusSelectedParagraph();
                        }, 0 );
                        return;
                    }
                }

                blockEditorDispatch.insertAfterBlock( clientId );
                window.setTimeout( function() {
                    wpc.focusSelectedParagraph();
                }, 0 );
            }

            element.useEffect( function() {
                var updates = {};

                if ( ! attributes.date ) {
                    updates.date = wpc.formatToday();
                }
                if ( currentUser && ! attributes.author ) {
                    updates.author = currentUser.name;
                }
                if ( ! attributes.changedAt ) {
                    updates.changedAt = Math.floor( Date.now() / 1000 );
                }

                if ( Object.keys( updates ).length ) {
                    setAttributes( updates );
                }
            }, [ currentUser ] );

            // After insert/select (e.g. via #log shortcut), focus the Change field.
            element.useEffect( function() {
                if ( ! isSelected ) {
                    didFocusChangeRef.current = false;
                    return;
                }

                if ( didFocusChangeRef.current ) {
                    return;
                }

                var timer = window.setTimeout( function() {
                    if ( ! changeFieldRef.current ) {
                        return;
                    }

                    var input = changeFieldRef.current.querySelector( 'input' );
                    if ( ! input ) {
                        return;
                    }

                    input.focus();
                    var len = input.value ? input.value.length : 0;
                    if ( typeof input.setSelectionRange === 'function' ) {
                        input.setSelectionRange( len, len );
                    }
                    didFocusChangeRef.current = true;
                }, 0 );

                return function() {
                    window.clearTimeout( timer );
                };
            }, [ isSelected ] );

            if ( ! isSelected ) {
                var summaryParts = [];
                if ( attributes.date ) {
                    summaryParts.push( attributes.date );
                }
                if ( attributes.comment ) {
                    summaryParts.push( attributes.comment );
                }
                if ( attributes.author ) {
                    summaryParts.push( attributes.author );
                }

                return el( 'div', blockProps,
                    el( 'div', { className: 'wpc-single-note-summary' },
                        '#log: ' + ( summaryParts.length
                            ? summaryParts.join( ' — ' )
                            : __( 'Empty change note', 'wp-changelog' ) )
                    )
                );
            }

            return el( 'div', blockProps,
                wpc.renderEditorBlockLabel( el, blockTitle ),
                el( 'div', { className: 'wpc-note-table-wrap' },
                    el( 'table', { className: 'wpc-editor-note-table' },
                        el( 'tbody', null,
                            el( 'tr', null,
                                el( 'td', { className: 'wpc-minimal-input wpc-changelog-col-date' },
                                    el( TextControl, {
                                        value: attributes.date,
                                        onChange: function( value ) { setAttributes( { date: value } ); },
                                        placeholder: __( 'Date', 'wp-changelog' )
                                    } )
                                ),
                                el( 'td', {
                                    className: 'wpc-minimal-input wpc-changelog-col-change',
                                    ref: changeFieldRef,
                                    onKeyDown: leaveBlockOnEnter
                                },
                                    el( TextControl, {
                                        value: attributes.comment,
                                        onChange: function( value ) {
                                            setAttributes( {
                                                comment: value,
                                                changedAt: Math.floor( Date.now() / 1000 )
                                            } );
                                        },
                                        placeholder: __( 'What was changed? (e.g. Fixed typo...)', 'wp-changelog' )
                                    } )
                                ),
                                el( 'td', { className: 'wpc-minimal-input wpc-changelog-col-author' },
                                    el( TextControl, {
                                        value: attributes.author || __( 'Loading...', 'wp-changelog' ),
                                        disabled: true
                                    } )
                                )
                            )
                        )
                    )
                )
            );
        },
        save: function() { return null; }
    };

    // Only the current block slug gets the typing shortcut (not the legacy slug).
    if (
        blockName === 'wpc/single-change-note' &&
        editorSettings.shortcutEnabled !== false &&
        typeof blocks.createBlock === 'function'
    ) {
        var prefix = ( editorSettings.shortcutPrefix || '#log' ).toString();
        if ( prefix ) {
            blockSettings.transforms = {
                from: [
                    {
                        type: 'prefix',
                        prefix: prefix,
                        transform: function( content ) {
                            return blocks.createBlock( blockName, {
                                comment: content || '',
                                date: wpc.formatToday(),
                                changedAt: Math.floor( Date.now() / 1000 )
                            } );
                        }
                    }
                ]
            };
        }
    }

    blocks.registerBlockType( blockName, blockSettings );
};
