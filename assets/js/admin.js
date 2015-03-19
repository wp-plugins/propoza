function execute_test_connection() {
    jQuery.post(propoza_settings.ajax_url, {
        'action': 'test_connection',
        'api_key': jQuery('#wc_settings_tab_propoza_api_key').val(),
        'web_address': jQuery('#wc_settings_tab_propoza_web_address').val()
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