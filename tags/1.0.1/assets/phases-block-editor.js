/**
 * Phases plugin block editor scripts
 */
jQuery( document ).ready( function ( $ ) {

    // update phase status whenever the user chooses a new one
    const $wrap = $( '#phases_options' );
    if ( $wrap.length ) {
        const $select = $wrap.find( '.phases-phase-select' );
        const $swatch = $wrap.find( '.phases-swatch' );
        const label = $swatch.text().split( ':' )[0];
        $select.on( 'change', function() {
            const $selected = $select.find( 'option:selected' );
            if ( $selected.data( 'color' ) ) {
                $swatch.text( label  + ': ' + $selected.text() ).css( 'background', $selected.data( 'color' ) );
            } else {
                $swatch.text( label ).css( 'background', 'transparent' );
            }
        });
    }

});
