/**
 * Workflows plugin settings page scripts
 */
jQuery(document).ready(function($){

    // get the workflow form
    const $workflows = $('.workflows');

    // establish default colors
    const palettes = [
        '#d9ead3', 
        '#fff3cc', 
        '#f5cbcc', 
        '#efefef'
    ];
   
    // initiate color picker
    $workflows.find('.workflows-color').wpColorPicker({ palettes });

    // remove stages on click of removal button
    $workflows.on('click', '.workflows__remove', function(){
        $(this).parent().remove();
    });

    // add stages on click of addition button
    $workflows.on('click', '.workflows__add', function(){
        const id = `new-stage-${Date.now()}`;
        $(this).parent().prepend(`
            <div class="workflows__stage">
                <input name="workflows_settings[stages][${id}][id]" value="${id}" type="text" />
                <input name="workflows_settings[stages][${id}][name]" value="" type="text" />
                <span class="workflows__color">
                    <input name="workflows_settings[stages][${id}][color]" value="%s" data-default-color="#cccccc" class="workflows-color" type="text" />
                </span>
                <button class="workflows__remove button-secondary" aria-label="Remove" title="Remove" type="button">✕</button>
            </div>
        `)
        .find('.workflows-color').wpColorPicker({ palettes });
    });

});
