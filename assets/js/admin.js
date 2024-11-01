jQuery(document).ready(function ($) {
    "use strict";

    function toggleSettings( item )
    {

        let toggleClass = item.attr('class');
        toggleClass = toggleClass.split(' ');

        let paymentGateway = toggleClass[1];

        if ( item.is(':checked') ) {
            $('.wcdpg-show-if-' + paymentGateway).parents('tr').show();
        } else {
            $('.wcdpg-show-if-' + paymentGateway).parents('tr').hide();
        }

    }

    $('.wcdpg-toggle').each(function() {
        toggleSettings( $(this) );
    });

    $(document).on("click", ".wcdpg-toggle", function(evt) {
        toggleSettings( $(this) );
    });
});