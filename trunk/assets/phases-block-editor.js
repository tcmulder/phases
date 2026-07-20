/**
 * Phases plugin block editor scripts
 */
document.addEventListener( 'DOMContentLoaded', function () {
	const wrap = document.getElementById( 'phases_options' );
	if ( ! wrap ) {
		return;
	}

	const select = wrap.querySelector( '.phases-phase-select' );
	const swatch = wrap.querySelector( '.phases-swatch' );
	if ( ! select || ! swatch ) {
		return;
	}

	const label = swatch.textContent.split( ':' )[0];

	select.addEventListener( 'change', function () {
		const selected = select.options[ select.selectedIndex ];
		const color = selected.dataset.color;
		if ( color ) {
			swatch.textContent = label + ': ' + selected.text;
			swatch.style.background = color;
		} else {
			swatch.textContent = label;
			swatch.style.background = 'transparent';
		}
	} );
} );
