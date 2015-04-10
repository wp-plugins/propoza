(function ($) {
    "use strict";

    $(function () {
        $('#woocommerce_propoza_setup_button').click(function (e) {
            window.open($('#woocommerce_propoza_setup_button').attr('href'));
        });
        $('#woocommerce_propoza_launch_propoza').click(function (e) {
            if ($('#woocommerce_propoza_web_address').val() != "") {
                window.open($('#woocommerce_propoza_launch_propoza').attr('href').replace('%s', $('#woocommerce_propoza_web_address').val()));
            }
            e.preventDefault();
        });
        $('#propoza_dashoard_link').click(function (e) {
            if ($('#woocommerce_propoza_web_address').val() != "") {
                window.open($('#propoza_dashoard_link').attr('href').replace('%s', $('#woocommerce_propoza_web_address').val()));
            }
            e.preventDefault();
        });

    });

}(jQuery));

function execute_test_connection() {
    jQuery.post(propoza_object.ajax_url, {
        'action': 'test_connection',
        'api_key': jQuery('#woocommerce_propoza_api_key').val(),
        'web_address': jQuery('#woocommerce_propoza_web_address').val()
    }, function (data) {
        showMessage(data.response);
    }, 'json');
}

function showMessage(success) {
    if (success === true) {
        alert('Test connection success!');
    } else {
        alert('Test connection failed!');
    }
}

