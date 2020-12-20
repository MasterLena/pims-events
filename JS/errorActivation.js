(function($) {

    jQuery('#wpbody-content').on('load', function (){
        let content = jQuery('.wrap')
        jQuery('<div class="error"><p>PIMS EVENTS: You must run php 7.4').insertBefore(content);
    })

}(jQuery, document, window));