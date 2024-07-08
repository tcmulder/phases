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

    // remove stages on click of removal button
    $phases.on('click', '.phases__remove', function(){
        $(this).parent().remove();
    });

    // add stages on click of addition button
    $phases.on('click', '.phases__add', function(){
        const id = `new-stage-${Date.now()}`;
        $(this).parent().prepend(`
            <div class="phases__stage">
                <input name="phases_settings[stages][${id}][id]" value="${id}" type="text" />
                <input name="phases_settings[stages][${id}][name]" value="" type="text" />
                <span class="phases__color">
                    <input name="phases_settings[stages][${id}][color]" value="%s" data-default-color="#cccccc" class="phases-color" type="text" />
                </span>
                <button class="phases__remove button-secondary" aria-label="Remove" title="Remove" type="button">✕</button>
            </div>
        `)
        .find('.phases-color').wpColorPicker({ palettes });
    });

});
