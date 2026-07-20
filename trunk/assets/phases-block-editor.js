/**
 * Phases plugin block editor scripts
 */
// On DOM load
document.addEventListener( 'DOMContentLoaded', function () {
    // Get our wrapper (or bail)
	const wrap = document.getElementById( 'phases_options' );
	if ( ! wrap ) {
		return;
	}

    // Get our select box and swatch
	const select = wrap.querySelector( '.phases-phase-select' );
	const swatch = wrap.querySelector( '.phases-swatch' );

	// Get the label to reuse 
    const label = swatch.textContent.split( ':' )[0];

	// When the user selects a phase
    select.addEventListener( 'change', function () {
        // Update the phase visually in the UI
		const selected = select.options[ select.selectedIndex ];
		const color = selected.dataset.color;
		if ( color ) {
			swatch.textContent = label + ': ' + selected.text;
			swatch.style.background = color;
		} else {
			swatch.textContent = label;
			swatch.style.background = 'transparent';
		}

        // Change something so the post status is dirty and the user is warned before leaving
		const phases = selected.value === '-1' ? [] : [ parseInt( selected.value, 10 ) ];
		wp.data.dispatch( 'core/editor' ).editPost( { phases: phases } );
	} );
} );
