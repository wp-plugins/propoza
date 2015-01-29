function request_quote() {
    request_quote_form();
}
jQuery(function () {
    jQuery("#dialog").dialog({autoOpen: false, modal: true, resizable: false});
});

function request_quote_form() {
    jQuery.ajax(
        {
            url: propoza_ajax_object.form_quote_request_url,
            type: "POST",
            data: JSON.stringify(propoza_ajax_object.logged_in_user),
            contentType: 'application/json',
            headers: {
                "Authorization": "Basic " + propoza_ajax_object.basic_auth
            },
            success: function (response) {
                jQuery("#dialog").html(response);
                jQuery('#cancel_request').click(function () {
                    jQuery("#dialog").dialog("close");
                });
                jQuery('#submit_request').click(function () {
                    execute_request_quote();
                });
                jQuery("#dialog").dialog("open");
            },
            error: function () {
                jQuery("#dialog").dialog("open");
                jQuery("#dialog").html(jQuery('#error-message'));
            }
        }
    );
}
function execute_request_quote() {
    jQuery('#quote_request_form input').parent('p').removeClass('woocommerce-invalid');
    var form = jQuery("#quote_request_form");
    propoza_ajax_object.prepared_quote.Quote.Requester = form.serializeObject();
    delete propoza_ajax_object.prepared_quote.Quote.Requester._method;

    jQuery.ajax(
        {
            url: form.attr('action'),
            type: "POST",
            data: JSON.stringify(propoza_ajax_object.prepared_quote),
            contentType: 'application/json',
            headers: {
                "Authorization": "Basic " + propoza_ajax_object.basic_auth
            },
            success: function (data) {
                if (data.response.validationErrors && data.response.validationErrors.Requester) {
                    for (var key in data.response.validationErrors.Requester) {
                        jQuery('#quote_request_form input#' + key).parent('p').addClass('woocommerce-invalid');
                    }
                } else {
                    jQuery("#dialog").html(jQuery('#success-message'));
                }
            },
            error: function (data) {
                jQuery("#dialog").html(jQuery('#error-message'));
            }
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