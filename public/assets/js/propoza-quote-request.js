jQuery(function () {
    jQuery("#dialog").dialog({autoOpen: false, modal: true, resizable: false});
});

function request_quote() {
    request_quote_form();
}

function request_quote_form() {
    jQuery.post(propoza_request.ajax_url, {
        'action': 'get_form_quote_request'
    }, function (data) {
        if (data != '') {
            jQuery("#dialog").html(data);
        } else {
            jQuery("#dialog").html(jQuery('#error-message'));
        }
        jQuery('#cancel_request').click(function () {
            jQuery("#dialog").dialog("close");
        });
        jQuery('#submit_request').click(function () {
            execute_request_quote();
        });
        jQuery("#dialog").dialog("open");
    }).fail(function () {
        jQuery("#dialog").dialog("open");
        jQuery("#dialog").html(jQuery('#error-message'));
    });
}
function execute_request_quote() {
    toggleLoading();
    jQuery('#quote_request_form input').parent('p').removeClass('woocommerce-invalid');
    var form = jQuery("#quote_request_form");
    var postData = form.serializeArray();
    postData.push({name: 'action', value: 'execute_request_quote'});
    postData.push({name: 'form-action', value: form.attr('action')});
    jQuery.post(propoza_request.ajax_url, postData, function (data) {
        toggleLoading();
        if (data.response && data.response.Quote && data.response.Quote.id) {
            jQuery("#dialog").html(jQuery('#success-message'));
            jQuery("#dialog").bind('dialogclose', function (event) {
                location.reload();
            });
        } else if (data.response && data.response.validationErrors) {
            for (var key in data.response.validationErrors) {
                var parent = jQuery('#' + key).parent('p');
                parent.find('.validation-error-message').html(data.response.validationErrors[key]);
                parent.addClass('woocommerce-invalid');
            }
        } else {
            jQuery("#dialog").html(jQuery('#error-message'));
        }

    }, 'json').fail(function () {
        toggleLoading();
        jQuery("#dialog").html(jQuery('#error-message'));
    });
}

function toggleLoading() {
    jQuery('#quote_request_form').toggle();
    jQuery('#loader').toggle();
}