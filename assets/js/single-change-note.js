/**
 * Registers the Single Change Note block and its legacy slug.
 */
( function( blocks, element, components, data, i18n ) {
    var wpc = window.wpcChangelog;
    var __ = i18n.__;

    wpc.registerNoteBlock(
        blocks,
        element,
        components,
        data,
        i18n,
        'wpc/single-change-note',
        {
            title: __( 'Single Change Note', 'wp-changelog' ),
            icon: 'edit'
        }
    );

    wpc.registerNoteBlock(
        blocks,
        element,
        components,
        data,
        i18n,
        'wpc/change-item',
        {
            title: __( 'Single Change Note', 'wp-changelog' ),
            icon: 'edit',
            supports: { inserter: false }
        }
    );
} )( window.wp.blocks, window.wp.element, window.wp.components, window.wp.data, window.wp.i18n );
