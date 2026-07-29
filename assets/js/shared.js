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

    blocks.registerBlockType( blockName, {
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
            var currentUser = data.select( 'core' ).getCurrentUser();

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

            return el( 'div', { className: 'wpc-block-surface-wrap' },
                wpc.renderEditorBlockLabel( el, blockTitle ),
                el( 'div', {
                    className: 'wpc-block-surface',
                    style: {
                        display: 'flex',
                        alignItems: 'center',
                        gap: '8px',
                        padding: '2px 6px',
                        marginBottom: '4px',
                        borderRadius: '2px',
                        borderLeft: '3px solid #007cba'
                    }
                },
                    el( 'div', { style: { width: '7rem', flex: '0 0 7rem' }, className: 'wpc-minimal-input wpc-changelog-col-date' },
                        el( TextControl, {
                            value: attributes.date,
                            onChange: function( value ) { setAttributes( { date: value } ); },
                            placeholder: __( 'Date', 'wp-changelog' ),
                            style: { height: '28px', fontSize: '12px' }
                        } )
                    ),
                    el( 'div', { style: { flex: '1 1 auto', minWidth: 0 }, className: 'wpc-minimal-input wpc-changelog-col-change' },
                        el( TextControl, {
                            value: attributes.comment,
                            onChange: function( value ) {
                                setAttributes( {
                                    comment: value,
                                    changedAt: Math.floor( Date.now() / 1000 )
                                } );
                            },
                            placeholder: __( 'What was changed? (e.g. Fixed typo...)', 'wp-changelog' ),
                            style: { height: '28px', fontSize: '12px' }
                        } )
                    ),
                    el( 'div', { style: { width: '9rem', flex: '0 0 9rem', opacity: '0.6' }, className: 'wpc-minimal-input wpc-changelog-col-author' },
                        el( TextControl, {
                            value: attributes.author || __( 'Loading...', 'wp-changelog' ),
                            disabled: true,
                            style: { height: '28px', fontSize: '12px' }
                        } )
                    )
                )
            );
        },
        save: function() { return null; }
    } );
};
