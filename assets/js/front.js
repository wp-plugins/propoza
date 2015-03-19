function request_quote() {
    request_quote_form();
}
jQuery(function () {
    jQuery("#dialog").dialog({autoOpen: false, modal: true, resizable: false});
});

function request_quote_form() {
    jQuery.post(propoza_request.ajax_url, {
        'action': 'get_form_quote_request'
    }, function (data) {
        jQuery("#dialog").html(data);
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
    jQuery('#quote_request_form input').parent('p').removeClass('woocommerce-invalid');
    var form = jQuery("#quote_request_form");

    jQuery.post(propoza_request.ajax_url, {
        'action': 'execute_request_quote',
        'form-action': form.attr('action'),
        'form-data': form.serializeObject()
    }, function (data) {
        if (data.response && data.response.Quote && data.response.Quote.id) {
            jQuery("#dialog").html(jQuery('#success-message'));
        } else if (data.response && data.response.validationErrors && data.response.validationErrors.Requester) {
            for (var key in data.response.validationErrors.Requester) {
                jQuery('#quote_request_form input#' + key).parent('p').addClass('woocommerce-invalid');
            }
        } else {
            jQuery("#dialog").html(jQuery('#error-message'));
        }

    }, 'json').fail(function () {
        jQuery("#dialog").html(jQuery('#error-message'));
    });
}

jQuery.fn.serializeObject = function () {
    var o = {};
    var a = this.serializeArray();
    jQuery.each(a, function () {
        if (o[this.name]) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};