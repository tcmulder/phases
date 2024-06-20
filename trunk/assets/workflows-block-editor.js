/**
 * Workflows plugin block editor scripts
 */
jQuery(document).ready(function($){

    // update workflow status dynamically
    const $wrap = $('#workflows_options');
    if ( $wrap.length ) {
        const $select = $wrap.find('.workflows-stage-select');
        const $swatch = $wrap.find('.workflows-swatch');
        $select.on('change', function() {
            const $selected = $select.find('option:selected');
            if ( $selected.data('color') ) {
                $swatch.text($selected.text()).css('background', $selected.data('color'));
            } else {
                $swatch.text('').css('background', 'transparent');
            }
        });
    }

});
