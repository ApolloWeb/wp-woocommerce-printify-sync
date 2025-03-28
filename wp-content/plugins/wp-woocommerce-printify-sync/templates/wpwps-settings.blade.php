@extends('layout')

@section('actions')
    <button class="btn btn-primary" id="save-settings">
        <i class="fa fa-save me-1"></i> Save Changes
    </button>
@endsection

@section('content')
    <form id="wpwps-settings-form">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs mb-3" id="settings-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="printify-tab" data-bs-toggle="tab" data-bs-target="#printify" 
                    type="button" role="tab" aria-controls="printify" aria-selected="true">Printify API</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="openai-tab" data-bs-toggle="tab" data-bs-target="#openai" 
                    type="button" role="tab" aria-controls="openai" aria-selected="false">OpenAI</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" 
                    type="button" role="tab" aria-controls="email" aria-selected="false">Email Settings</button>
            </li>
        </ul>
        
        <!-- Tab panes -->
        <div class="tab-content">
            <!-- Printify API Settings -->
            <div class="tab-pane fade show active" id="printify" role="tabpanel" aria-labelledby="printify-tab">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="printify_api_key" class="form-label">API Key</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="printify_api_key" name="printify_api_key" 
                                value="{{ isset($settings['printify_api_key']) ? str_repeat('•', 10) : '' }}" 
                                placeholder="Enter your Printify API key">
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Get your API key from the <a href="https://printify.com/apps" target="_blank">Printify Apps</a> page.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="printify_api_endpoint" class="form-label">API Endpoint</label>
                        <input type="text" class="form-control" id="printify_api_endpoint" name="printify_api_endpoint" 
                            value="{{ $settings['printify_api_endpoint'] }}" placeholder="https://api.printify.com/v1/">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <button type="button" id="test-printify-api" class="btn btn-secondary">
                            <i class="fa fa-check-circle me-1"></i> Test Connection
                        </button>
                    </div>
                    <div class="col-md-8">
                        <div id="printify-api-status" class="d-none alert"></div>
                    </div>
                </div>
                
                <div class="row mb-3 shops-container {{ !empty($settings['printify_shop_id']) ? 'd-none' : '' }}">
                    <div class="col-md-6">
                        <label for="printify_shop_id" class="form-label">Shop</label>
                        <select class="form-select" id="printify_shop_id" name="printify_shop_id" {{ !empty($settings['printify_shop_id']) ? 'disabled' : '' }}>
                            <option value="">Select a shop</option>
                            @foreach($shops as $shop)
                                <option value="{{ $shop['id'] }}" 
                                    {{ $settings['printify_shop_id'] == $shop['id'] ? 'selected' : '' }}>
                                    {{ $shop['title'] }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Once a shop is selected and saved, it cannot be changed.</div>
                    </div>
                </div>
                
                <div class="row mb-3 selected-shop-container {{ empty($settings['printify_shop_id']) ? 'd-none' : '' }}">
                    <div class="col-md-6">
                        <label class="form-label">Selected Shop</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="{{ $settings['printify_shop_name'] }}" disabled>
                            <input type="hidden" name="printify_shop_id" value="{{ $settings['printify_shop_id'] }}">
                            <input type="hidden" name="printify_shop_name" value="{{ $settings['printify_shop_name'] }}">
                        </div>
                        <div class="form-text text-warning">Shop selection cannot be changed. To use a different shop, you must reinstall the plugin.</div>
                    </div>
                </div>
            </div>
            
            <!-- OpenAI Settings -->
            <div class="tab-pane fade" id="openai" role="tabpanel" aria-labelledby="openai-tab">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="openai_api_key" class="form-label">API Key</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="openai_api_key" name="openai_api_key" 
                                value="{{ isset($settings['openai_api_key']) ? str_repeat('•', 10) : '' }}" 
                                placeholder="Enter your OpenAI API key">
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Get your API key from the <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI dashboard</a>.</div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="openai_token_limit" class="form-label">Token Limit</label>
                        <input type="number" class="form-control" id="openai_token_limit" name="openai_token_limit" 
                            value="{{ $settings['openai_token_limit'] }}" min="100" max="4000" step="100">
                        <div class="form-text">Maximum number of tokens per request. Higher values may improve quality but cost more.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="openai_temperature" class="form-label">Temperature</label>
                        <input type="range" class="form-range" id="openai_temperature" name="openai_temperature" 
                            value="{{ $settings['openai_temperature'] }}" min="0" max="1" step="0.1">
                        <div class="d-flex justify-content-between">
                            <small>More precise (0)</small>
                            <small><span id="temperature-value">{{ $settings['openai_temperature'] }}</span></small>
                            <small>More creative (1)</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="openai_spend_cap" class="form-label">Monthly Spend Cap ($)</label>
                        <input type="number" class="form-control" id="openai_spend_cap" name="openai_spend_cap" 
                            value="{{ $settings['openai_spend_cap'] }}" min="1" step="0.01">
                        <div class="form-text">Maximum monthly spend in USD. Requests will be blocked if this limit is reached.</div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <button type="button" id="test-openai" class="btn btn-secondary">
                            <i class="fa fa-check-circle me-1"></i> Test Connection
                        </button>
                    </div>
                    <div class="col-md-8">
                        <div id="openai-status" class="d-none alert"></div>
                    </div>
                </div>
                
                <div class="row mb-3" id="cost-estimate-container" style="display: none;">
                    <div class="col-md-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title">Estimated Monthly Cost</h5>
                                <p class="card-text">
                                    Based on your current settings, the estimated monthly cost is <strong id="cost-estimate">$0.00</strong>.
                                </p>
                                <div class="progress">
                                    <div id="cost-progress" class="progress-bar" role="progressbar" style="width: 0%;" 
                                        aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                                </div>
                                <small class="text-muted">This is based on an estimated 300 requests per month.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Email Settings -->
            <div class="tab-pane fade" id="email" role="tabpanel" aria-labelledby="email-tab">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="email_queue_enabled" name="email_queue_enabled" {{ $settings['email_queue_enabled'] ? 'checked' : '' }}>
                            <label class="form-check-label" for="email_queue_enabled">Enable Email Queue</label>
                        </div>
                        <div class="form-text">When enabled, emails are queued and sent in batches every 5 minutes.</div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email_from_name" class="form-label">From Name</label>
                        <input type="text" class="form-control" id="email_from_name" name="email_from_name" 
                            value="{{ $settings['email_from_name'] }}" placeholder="Your Store Name">
                    </div>
                    <div class="col-md-6">
                        <label for="email_from_email" class="form-label">From Email</label>
                        <input type="email" class="form-control" id="email_from_email" name="email_from_email" 
                            value="{{ $settings['email_from_email'] }}" placeholder="noreply@yourdomain.com">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="email_signature" class="form-label">Email Signature</label>
                        <textarea class="form-control" id="email_signature" name="email_signature" rows="3">{{ $settings['email_signature'] }}</textarea>
                        <div class="form-text">HTML is allowed. This signature will be added to all outgoing emails.</div>
                    </div>
                </div>
                
                <h5 class="mt-4">SMTP Settings</h5>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email_smtp_host" class="form-label">SMTP Host</label>
                        <input type="text" class="form-control" id="email_smtp_host" name="email_smtp_host" 
                            value="{{ $settings['email_smtp_host'] }}" placeholder="smtp.example.com">
                    </div>
                    <div class="col-md-6">
                        <label for="email_smtp_port" class="form-label">SMTP Port</label>
                        <input type="number" class="form-control" id="email_smtp_port" name="email_smtp_port" 
                            value="{{ $settings['email_smtp_port'] }}" placeholder="587">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="email_smtp_username" class="form-label">SMTP Username</label>
                        <input type="text" class="form-control" id="email_smtp_username" name="email_smtp_username" 
                            value="{{ $settings['email_smtp_username'] }}" placeholder="username">
                    </div>
                    <div class="col-md-4">
                        <label for="email_smtp_password" class="form-label">SMTP Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="email_smtp_password" name="email_smtp_password" 
                                value="{{ isset($settings['email_smtp_password']) && !empty($settings['email_smtp_password']) ? str_repeat('•', 10) : '' }}" 
                                placeholder="Enter your SMTP password">
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="email_smtp_secure" class="form-label">Encryption</label>
                        <select class="form-select" id="email_smtp_secure" name="email_smtp_secure">
                            <option value="tls" {{ $settings['email_smtp_secure'] == 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ $settings['email_smtp_secure'] == 'ssl' ? 'selected' : '' }}>SSL</option>
                        </select>
                    </div>
                </div>
                
                <h5 class="mt-4">Social Media Links</h5>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email_social_facebook" class="form-label">Facebook</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fab fa-facebook"></i></span>
                            <input type="url" class="form-control" id="email_social_facebook" name="email_social_facebook" 
                                value="{{ $settings['email_social_facebook'] }}" placeholder="https://facebook.com/yourpage">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="email_social_instagram" class="form-label">Instagram</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                            <input type="url" class="form-control" id="email_social_instagram" name="email_social_instagram" 
                                value="{{ $settings['email_social_instagram'] }}" placeholder="https://instagram.com/yourprofile">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email_social_tiktok" class="form-label">TikTok</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fab fa-tiktok"></i></span>
                            <input type="url" class="form-control" id="email_social_tiktok" name="email_social_tiktok" 
                                value="{{ $settings['email_social_tiktok'] }}" placeholder="https://tiktok.com/@youraccount">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="email_social_youtube" class="form-label">YouTube</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fab fa-youtube"></i></span>
                            <input type="url" class="form-control" id="email_social_youtube" name="email_social_youtube" 
                                value="{{ $settings['email_social_youtube'] }}" placeholder="https://youtube.com/c/yourchannel">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    
    <script>
        jQuery(document).ready(function($) {
            // Toggle password visibility
            $('.toggle-password').on('click', function() {
                var input = $(this).parent().find('input');
                var icon = $(this).find('i');
                
                // Only toggle if not masked with dots
                if (input.val().indexOf('•') === -1) {
                    if (input.attr('type') === 'password') {
                        input.attr('type', 'text');
                        icon.removeClass('fa-eye').addClass('fa-eye-slash');
                    } else {
                        input.attr('type', 'password');
                        icon.removeClass('fa-eye-slash').addClass('fa-eye');
                    }
                }
            });
            
            // Handle API key fields (remove dots on focus)
            $('input[type="password"]').on('focus', function() {
                if ($(this).val().indexOf('•') !== -1) {
                    $(this).val('');
                }
            });
            
            // Update temperature display value
            $('#openai_temperature').on('input', function() {
                $('#temperature-value').text($(this).val());
            });
            
            // Test Printify API connection
            $('#test-printify-api').on('click', function() {
                var button = $(this);
                var apiKey = $('#printify_api_key').val();
                var endpoint = $('#printify_api_endpoint').val();
                var statusContainer = $('#printify-api-status');
                var shopsContainer = $('.shops-container');
                var shopSelect = $('#printify_shop_id');
                
                if (!apiKey) {
                    statusContainer.removeClass('d-none alert-success').addClass('alert-danger').html('Please enter an API key.');
                    return;
                }
                
                button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Testing...');
                statusContainer.removeClass('d-none alert-success alert-danger').html('');
                
                $.ajax({
                    url: wpwps.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpwps_test_printify_api',
                        nonce: wpwps.nonce,
                        api_key: apiKey,
                        endpoint: endpoint
                    },
                    success: function(response) {
                        if (response.success) {
                            statusContainer.removeClass('alert-danger').addClass('alert-success').html(response.data.message);
                            
                            // Populate shops dropdown
                            shopSelect.empty().append('<option value="">Select a shop</option>');
                            $.each(response.data.shops, function(i, shop) {
                                shopSelect.append($('<option>', {
                                    value: shop.id,
                                    text: shop.title
                                }));
                            });
                            
                            shopsContainer.removeClass('d-none');
                        } else {
                            statusContainer.removeClass('alert-success').addClass('alert-danger').html(response.data.message);
                        }
                    },
                    error: function() {
                        statusContainer.removeClass('alert-success').addClass('alert-danger').html('An error occurred. Please try again.');
                    },
                    complete: function() {
                        button.prop('disabled', false).html('<i class="fa fa-check-circle me-1"></i> Test Connection');
                    }
                });
            });
            
            // Test OpenAI API connection
            $('#test-openai').on('click', function() {
                var button = $(this);
                var apiKey = $('#openai_api_key').val();
                var tokenLimit = $('#openai_token_limit').val();
                var temperature = $('#openai_temperature').val();
                var spendCap = $('#openai_spend_cap').val();
                var statusContainer = $('#openai-status');
                var costContainer = $('#cost-estimate-container');
                
                if (!apiKey) {
                    statusContainer.removeClass('d-none alert-success').addClass('alert-danger').html('Please enter an API key.');
                    return;
                }
                
                button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Testing...');
                statusContainer.removeClass('d-none alert-success alert-danger').html('');
                
                $.ajax({
                    url: wpwps.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpwps_test_openai',
                        nonce: wpwps.nonce,
                        api_key: apiKey,
                        token_limit: tokenLimit,
                        temperature: temperature,
                        spend_cap: spendCap
                    },
                    success: function(response) {
                        if (response.success) {
                            statusContainer.removeClass('alert-danger').addClass('alert-success').html(response.data.message);
                            
                            // Update cost estimate
                            $('#cost-estimate').text('$' + response.data.estimated_cost.toFixed(2));
                            var percentage = (response.data.estimated_cost / response.data.spend_cap) * 100;
                            $('#cost-progress').css('width', percentage + '%').attr('aria-valuenow', percentage).text(percentage.toFixed(0) + '%');
                            
                            // Show cost estimate container
                            costContainer.show();
                            
                            // Add warning if estimated cost exceeds spend cap
                            if (!response.data.within_cap) {
                                statusContainer.append('<div class="mt-2 alert alert-warning mb-0">Warning: Estimated cost exceeds your monthly spend cap.</div>');
                            }
                        } else {
                            statusContainer.removeClass('alert-success').addClass('alert-danger').html(response.data.message);
                            costContainer.hide();
                        }
                    },
                    error: function() {
                        statusContainer.removeClass('alert-success').addClass('alert-danger').html('An error occurred. Please try again.');
                        costContainer.hide();
                    },
                    complete: function() {
                        button.prop('disabled', false).html('<i class="fa fa-check-circle me-1"></i> Test Connection');
                    }
                });
            });
            
            // Save settings
            $('#save-settings').on('click', function() {
                var button = $(this);
                var form = $('#wpwps-settings-form');
                var formData = form.serializeArray();
                
                button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> ' + wpwps.i18n.saving);
                
                $.ajax({
                    url: wpwps.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpwps_save_settings',
                        nonce: wpwps.nonce,
                        settings: Object.fromEntries(formData.map(x => [x.name, x.value]))
                    },
                    success: function(response) {
                        if (response.success) {
                            button.html('<i class="fa fa-check-circle me-1"></i> ' + wpwps.i18n.saved);
                            
                            // Show success message
                            $('<div class="alert alert-success mt-3"><i class="fa fa-check-circle me-2"></i> Settings saved successfully.</div>')
                                .insertBefore(form).delay(3000).fadeOut(500);
                            
                            // Reload page if shop_id was selected for the first time
                            if ($('.selected-shop-container').hasClass('d-none') && $('#printify_shop_id').val()) {
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            }
                        } else {
                            button.html('<i class="fa fa-times-circle me-1"></i> Error');
                            
                            // Show error message
                            $('<div class="alert alert-danger mt-3"><i class="fa fa-exclamation-circle me-2"></i> ' + response.data.message + '</div>')
                                .insertBefore(form).delay(3000).fadeOut(500);
                        }
                    },
                    error: function() {
                        button.html('<i class="fa fa-times-circle me-1"></i> Error');
                        
                        // Show error message
                        $('<div class="alert alert-danger mt-3"><i class="fa fa-exclamation-circle me-2"></i> ' + wpwps.i18n.error + '</div>')
                            .insertBefore(form).delay(3000).fadeOut(500);
                    },
                    complete: function() {
                        setTimeout(function() {
                            button.prop('disabled', false).html('<i class="fa fa-save me-1"></i> Save Changes');
                        }, 2000);
                    }
                });
            });
        });
    </script>
@endsection