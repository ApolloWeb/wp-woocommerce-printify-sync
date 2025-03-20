method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'printify_sync',
                    action_type: 'raw_request',
                    nonce: '<?php echo wp_create_nonce('wpwps_nonce'); ?>',
                    endpoint: endpoint,
                    api_key: apiKey
                })
