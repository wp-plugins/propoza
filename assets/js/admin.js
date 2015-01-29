var basic_auth;
var connection_test_url;

function test_connection() {
    jQuery(document).queue('test_connection', get_basic_auth);
    jQuery(document).queue('test_connection', get_test_connection_url);
    jQuery(document).queue('test_connection', execute_test_connection);
    jQuery(document).dequeue('test_connection');
}

function get_test_connection_url() {
    jQuery.post(propoza_ajax_object.ajax_url, {
        'action': 'get_test_connection_url',
        'web_address': jQuery('#wc_settings_tab_propoza_web_address').val()
    }, function (response) {
        connection_test_url = response;
        jQuery(document).dequeue('test_connection');
    });
}

function get_basic_auth() {
    jQuery.post(propoza_ajax_object.ajax_url, {
        'action': 'get_basic_auth',
        'api_key': jQuery('#wc_settings_tab_propoza_api_key').val(),
        'web_address': jQuery('#wc_settings_tab_propoza_web_address').val()
    }, function (response) {
        basic_auth = response;
        jQuery(document).dequeue('test_connection');
    });
}


function execute_test_connection() {
    jQuery.ajax(
        {
            url: connection_test_url,
            type: "POST",
            dataType: 'json',
            headers: {
                "Authorization": "Basic " + basic_auth
            },
            success: function (response) {
                if (response.response === true) {
                    alert('Test connection success!');
                } else {
                    alert('Test connection failed!');
                }
                jQuery(document).dequeue('test_connection');
            },
            error: function () {
                alert('Test connection failed!');
            }
        }
    );
}