/**
 * Phases plugin settings page scripts
 */
jQuery(document).ready(function($){

    // get the phase form
    const $phases = $('.phases');

    // establish default colors
    const palettes = [
        '#d9ead3', 
        '#fff3cc', 
        '#f5cbcc', 
        '#efefef'
    ];
   
    // initiate color picker
    $phases.find('.phases-color').wpColorPicker({ palettes });

    // remove phases on click of removal button
    $phases.on('click', '.phases__remove', function(){
        $(this).parent().remove();
    });

    // add phases on click of addition button
    $phases.on('click', '.phases__add', function(){
        // use temporary unique ID
        const id = `new-phase-${Date.now()}`;
        $(this).parent().prepend(`
            <div class="phases__phase">
                <input name="phases_settings[phases][${id}][id]" value="${id}" type="hidden" />
                <input name="phases_settings[phases][${id}][name]" value="" type="text" />
                <span class="phases__color">
                    <input name="phases_settings[phases][${id}][color]" value="" data-default-color="#cccccc" class="phases-color" type="text" />
                </span>
                <button class="phases__remove button-secondary" aria-label="Remove" title="Remove" type="button">✕</button>
            </div>
        `)
        // initiate color picker
        .find('.phases-color').wpColorPicker({ palettes });
    });

});
