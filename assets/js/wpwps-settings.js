jQuery(document).ready(function($) {
    const settingsForm = $('#wpwps-settings-form');
    const testButton = $('#test-connection');
    const shopSelect = $('#shop_id');
    const tempRange = $('#temperature');
    const tempValue = $('#temperature-value');

    tempRange.on('input', function() {
        tempValue.text(this.value);
    });

    testButton.on('click', function() {
        const apiKey = $('#printify_api_key').val();
        const endpoint = $('#printify_endpoint').val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpwps_test_connection',
                api_key: apiKey,
                endpoint: endpoint,
                nonce: wpwpsSettings.nonce
            },
            beforeSend: function() {
                testButton.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    shopSelect.empty().prop('disabled', false);
                    response.data.shops.forEach(function(shop) {
                        shopSelect.append(new Option(shop.title, shop.id));
                    });
                    alert('Connection successful!');
                } else {
                    alert('Connection failed: ' + response.data.message);
                }
            },
            error: function() {
                alert('Connection test failed');
            },
            complete: function() {
                testButton.prop('disabled', false);
            }
        });
    });

    settingsForm.on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpwps_save_settings',
                settings: settingsForm.serialize(),
                nonce: wpwpsSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Settings saved successfully!');
                } else {
                    alert('Failed to save settings: ' + response.data.message);
                }
            }
        });
    });
});
