// ...existing code...
    setupAPICheck: function() {
        const $form = $('#wpwps-settings-form');
        const $testBtn = $('#wpwps-test-api');
        const $shopSelect = $('#shop-selector');
        
        $testBtn.on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const apiKey = $('#wpwps_settings_api_key').val();
            
            if (!apiKey) {
                alert(wpwps.strings.enterApiKey);
                return;
            }
            
            $button.prop('disabled', true).text(wpwps.strings.testing);
            
            $.ajax({
                url: wpwps.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'wpwps_test_api',
                    nonce: wpwps.nonce,
                    api_key: apiKey
                },
                success: function(response) {
                    $button.prop('disabled', false).text(wpwps.strings.testConnection);
                    
                    if (response.success) {
                        WPWPS.loadShops();
                    } else {
                        alert(response.data.message || wpwps.strings.error);
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text(wpwps.strings.testConnection);
                    alert(wpwps.strings.error);
                }
            });
        });

        $form.on('submit', function(e) {
            e.preventDefault();
            WPWPS.saveSettings($(this));
        });
    },

    loadShops: function() {
        const $select = $('#wpwps_settings_shop_id');
        const $container = $('#shop-selector');
        
        $.ajax({
            url: wpwps.ajaxUrl,
            method: 'POST',
            data: {
                action: 'wpwps_get_shops',
                nonce: wpwps.nonce
            },
            success: function(response) {
                if (response.success) {
                    $select.empty().append('<option value="">' + wpwps.strings.selectShop + '</option>');
                    
                    response.data.shops.forEach(function(shop) {
                        $select.append(`<option value="${shop.id}">${shop.title}</option>`);
                    });
                    
                    $container.slideDown();
                } else {
                    alert(response.data.message || wpwps.strings.error);
                }
            },
            error: function() {
                alert(wpwps.strings.error);
            }
        });
    },

    saveSettings: function($form) {
        const data = $form.serializeArray();
        const $submit = $form.find('button[type="submit"]');
        
        if (!$('#wpwps_settings_shop_id').val()) {
            alert(wpwps.strings.selectShop);
            return;
        }
        
        $submit.prop('disabled', true);
        
        $.ajax({
            url: wpwps.ajaxUrl,
            method: 'POST',
            data: {
                action: 'wpwps_save_settings',
                nonce: wpwps.nonce,
                settings: Object.fromEntries(data.map(x => [x.name.replace('settings[', '').replace(']', ''), x.value]))
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    $submit.prop('disabled', false);
                    alert(response.data.message || wpwps.strings.error);
                }
            },
            error: function() {
                $submit.prop('disabled', false);
                alert(wpwps.strings.error);
            }
        });
    }
// ...existing code...
