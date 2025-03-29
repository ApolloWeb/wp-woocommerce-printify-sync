@extends('layout')

@section('title', __('Settings', WPWPS_TEXT_DOMAIN))
@section('page_title', __('Settings', WPWPS_TEXT_DOMAIN))

@section('content')
    <div class="wpwps-settings-container">
        <ul class="nav nav-tabs mb-4" id="wpwpsSettingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="printify-tab" data-bs-toggle="tab" data-bs-target="#printify" type="button" role="tab" aria-controls="printify" aria-selected="true">
                    <i class="fas fa-store me-2"></i> @e__('Printify')
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="openai-tab" data-bs-toggle="tab" data-bs-target="#openai" type="button" role="tab" aria-controls="openai" aria-selected="false">
                    <i class="fas fa-robot me-2"></i> @e__('OpenAI')
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab" aria-controls="email" aria-selected="false">
                    <i class="fas fa-envelope me-2"></i> @e__('Email Settings')
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="wpwpsSettingsTabsContent">
            <!-- Printify API Settings -->
            <div class="tab-pane fade show active" id="printify" role="tabpanel" aria-labelledby="printify-tab">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">@e__('Printify API Settings')</h5>
                    </div>
                    <div class="card-body">
                        <form id="printify-settings-form">
                            <div class="form-group row mb-3">
                                <label for="printify_api_key" class="col-sm-3 col-form-label">@e__('API Key')</label>
                                <div class="col-sm-9">
                                    <input type="password" class="form-control" id="printify_api_key" name="api_key" value="{{ $printify['api_key'] }}">
                                    <small class="form-text text-muted">@e__('You can find your API key in the Printify dashboard under Settings > API.')</small>
                                </div>
                            </div>
                            
                            <div class="form-group row mb-3">
                                <label for="printify_api_endpoint" class="col-sm-3 col-form-label">@e__('API Endpoint')</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" id="printify_api_endpoint" name="api_endpoint" value="{{ $printify['api_endpoint'] }}">
                                    <small class="form-text text-muted">@e__('Default: https://api.printify.com/v1/')</small>
                                </div>
                            </div>
                            
                            <div id="shop-selection-container" class="{{ $printify['is_shop_selected'] ? 'd-none' : '' }}">
                                <div class="form-group row mb-3">
                                    <label for="printify_shop_id" class="col-sm-3 col-form-label">@e__('Shop')</label>
                                    <div class="col-sm-9">
                                        <select class="form-control" id="printify_shop_id" name="shop_id" {{ $printify['is_shop_selected'] ? 'disabled' : '' }}>
                                            <option value="">@e__('Select a shop...')</option>
                                            @if($printify['is_shop_selected'])
                                                <option value="{{ $printify['shop_id'] }}" selected>{{ $printify['shop_id'] }}</option>
                                            @endif
                                        </select>
                                        <small class="form-text text-muted">@e__('Once saved, the shop cannot be changed.')</small>
                                    </div>
                                </div>
                            </div>
                            
                            @if($printify['is_shop_selected'])
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i> @e__('Shop ID is locked:') {{ $printify['shop_id'] }}
                                </div>
                            @endif
                            
                            <div class="form-group row mb-3">
                                <div class="col-sm-9 offset-sm-3">
                                    <button type="button" id="test-printify-connection" class="btn btn-info me-2">
                                        <i class="fas fa-plug me-2"></i> @e__('Test Connection')
                                    </button>
                                    <button type="submit" id="save-printify-settings" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> @e__('Save Settings')
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <div id="printify-connection-result"></div>
                    </div>
                </div>
            </div>
            
            <!-- OpenAI Settings -->
            <div class="tab-pane fade" id="openai" role="tabpanel" aria-labelledby="openai-tab">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">@e__('OpenAI Integration')</h5>
                    </div>
                    <div class="card-body">
                        <form id="openai-settings-form">
                            <div class="form-group row mb-3">
                                <label for="openai_api_key" class="col-sm-3 col-form-label">@e__('API Key')</label>
                                <div class="col-sm-9">
                                    <input type="password" class="form-control" id="openai_api_key" name="api_key" value="{{ $openai['api_key'] }}">
                                    <small class="form-text text-muted">@e__('Find your API key at https://platform.openai.com/account/api-keys')</small>
                                </div>
                            </div>
                            
                            <div class="form-group row mb-3">
                                <label for="openai_max_tokens" class="col-sm-3 col-form-label">@e__('Max Tokens')</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" id="openai_max_tokens" name="max_tokens" min="100" max="4000" value="{{ $openai['max_tokens'] }}">
                                    <small class="form-text text-muted">@e__('Maximum number of tokens to generate. Higher values may impact cost.')</small>
                                </div>
                            </div>
                            
                            <div class="form-group row mb-3">
                                <label for="openai_temperature" class="col-sm-3 col-form-label">@e__('Temperature')</label>
                                <div class="col-sm-9">
                                    <input type="range" class="form-range" id="openai_temperature" name="temperature" min="0" max="1" step="0.1" value="{{ $openai['temperature'] }}">
                                    <div class="d-flex justify-content-between">
                                        <small>@e__('More Precise')</small>
                                        <span id="temperature-value">{{ $openai['temperature'] }}</span>
                                        <small>@e__('More Creative')</small>
                                    </div>
                                    <small class="form-text text-muted">@e__('Controls randomness in responses. Lower values are more deterministic.')</small>
                                </div>
                            </div>
                            
                            <div class="form-group row mb-3">
                                <label for="openai_spend_cap" class="col-sm-3 col-form-label">@e__('Monthly Spend Cap (USD)')</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" id="openai_spend_cap" name="spend_cap" min="0" step="1" value="{{ $openai['spend_cap'] }}">
                                    <small class="form-text text-muted">@e__('Set a limit on monthly API spending to prevent unexpected costs.')</small>
                                </div>
                            </div>
                            
                            <div class="form-group row mb-3">
                                <div class="col-sm-9 offset-sm-3">
                                    <button type="button" id="test-openai-connection" class="btn btn-info me-2">
                                        <i class="fas fa-plug me-2"></i> @e__('Test Connection')
                                    </button>
                                    <button type="submit" id="save-openai-settings" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> @e__('Save Settings')
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <div id="openai-connection-result"></div>
                    </div>
                </div>
            </div>
            
            <!-- Email Settings -->
            <div class="tab-pane fade" id="email" role="tabpanel" aria-labelledby="email-tab">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">@e__('Email Settings')</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> @e__('Email settings will be available in a future update.')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        jQuery(document).ready(function($) {
            // Update temperature value display
            $('#openai_temperature').on('input', function() {
                $('#temperature-value').text($(this).val());
            });
            
            // Test Printify connection
            $('#test-printify-connection').on('click', function() {
                const button = $(this);
                const resultContainer = $('#printify-connection-result');
                const apiKey = $('#printify_api_key').val();
                const apiEndpoint = $('#printify_api_endpoint').val();
                
                if (!apiKey) {
                    resultContainer.html('<div class="alert alert-danger mt-3"><i class="fas fa-exclamation-circle me-2"></i> @e__('API key is required.')</div>');
                    return;
                }
                
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> @e__('Testing...')');
                resultContainer.html('');
                
                $.ajax({
                    url: wpwps.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpwps_test_printify_connection',
                        nonce: wpwps.nonce,
                        api_key: apiKey,
                        api_endpoint: apiEndpoint
                    },
                    success: function(response) {
                        if (response.success) {
                            resultContainer.html('<div class="alert alert-success mt-3"><i class="fas fa-check-circle me-2"></i> ' + response.data.message + '</div>');
                            
                            // Populate shop dropdown
                            const shopSelect = $('#printify_shop_id');
                            shopSelect.empty().append('<option value="">@e__('Select a shop...')</option>');
                            
                            if (response.data.shops && response.data.shops.length) {
                                $.each(response.data.shops, function(index, shop) {
                                    shopSelect.append('<option value="' + shop.id + '">' + shop.title + '</option>');
                                });
                                $('#shop-selection-container').removeClass('d-none');
                            }
                        } else {
                            resultContainer.html('<div class="alert alert-danger mt-3"><i class="fas fa-exclamation-circle me-2"></i> ' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        resultContainer.html('<div class="alert alert-danger mt-3"><i class="fas fa-exclamation-circle me-2"></i> @e__('An unexpected error occurred.')</div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).html('<i class="fas fa-plug me-2"></i> @e__('Test Connection')');
                    }
                });
            });
            
            // Save Printify settings
            $('#printify-settings-form').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const submitButton = $('#save-printify-settings');
                const resultContainer = $('#printify-connection-result');
                const apiKey = $('#printify_api_key').val();
                const apiEndpoint = $('#printify_api_endpoint').val();
                const shopId = $('#printify_shop_id').val();
                
                if (!apiKey) {
                    resultContainer.html('<div class="alert alert-danger mt-3"><i class="fas fa-exclamation-circle me-2"></i> @e__('API key is required.')</div>');
                    return;
                }
                
                if (!$('#printify_shop_id').prop('disabled') && !shopId) {
                    resultContainer.html('<div class="alert alert-danger mt-3"><i class="fas fa-exclamation-circle me-2"></i> @e__('Please select a shop.')</div>');
                    return;
                }
                
                submitButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> @e__('Saving...')');
                resultContainer.html('');
                
                $.ajax({
                    url: wpwps.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpwps_save_printify_settings',
                        nonce: wpwps.nonce,
                        api_key: apiKey,
                        api_endpoint: apiEndpoint,
                        shop_id: shopId
                    },
                    success: function(response) {
                        if (response.success) {
                            resultContainer.html('<div class="alert alert-success mt-3"><i class="fas fa-check-circle me-2"></i> ' + response.data.message + '</div>');
                            
                            // If shop was just selected, refresh to show locked state
                            if (!$('#printify_shop_id').prop('disabled') && shopId) {
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            }
                        } else {
                            resultContainer.html('<div class="alert alert-danger mt-3"><i class="fas fa-exclamation-circle me-2"></i> ' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        resultContainer.html('<div class="alert alert-danger mt-3"><i class="fas fa-exclamation-circle me-2"></i> @e__('An unexpected error occurred.')</div>');
                    },
                    complete: function() {
                        submitButton.prop('disabled', false).html('<i class="fas fa-save me-2"></i> @e__('Save Settings')');
                    }
                });
            });
            
            // Test OpenAI connection
            $('#test-openai-connection').on('click', function() {
                const button = $(this);
                const resultContainer = $('#openai-connection-result');
                const apiKey = $('#openai_api_key').val();
                const maxTokens = $('#openai_max_tokens').val();
                const temperature = $('#openai_temperature').val();
                
                if (!apiKey) {
                    resultContainer.html('<div class="alert alert-danger mt-3"><i class="fas fa-exclamation-circle me-2"></i> @e__('API key is required.')</div>');
                    return;
                }
                
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> @e__('Testing...')');
                resultContainer.html('');
                
                $.ajax({
                    url: wpwps.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpwps_test_openai_connection',
                        nonce: wpwps.nonce,
                        api_key: apiKey,
                        max_tokens: maxTokens,
                        temperature: temperature
                    },
                    success: function(response) {
                        if (response.success) {
                            let html = '<div class="alert alert-success mt-3">' +
                                '<i class="fas fa-check-circle me-2"></i> ' + response.data.message + '<br>' +
                                '<strong>@e__('Response')</strong>: ' + response.data.response + '<br>' +
                                '<strong>@e__('Estimated Monthly Cost')</strong>: $' + response.data.estimated_cost + ' USD' +
                                '</div>';
                            resultContainer.html(html);
                        } else {
                            resultContainer.html('<div class="alert alert-danger mt-3"><i class="fas fa-exclamation-circle me-2"></i> ' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        resultContainer.html('<div class="alert alert-danger mt-3"><i class="fas fa-exclamation-circle me-2"></i> @e__('An unexpected error occurred.')</div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).html('<i class="fas fa-plug me-2"></i> @e__('Test Connection')');
                    }
                });
            });
            
            // Save OpenAI settings
            $('#openai-settings-form').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const submitButton = $('#save-openai-settings');
                const resultContainer = $('#openai-connection-result');
                const apiKey = $('#openai_api_key').val();
                const maxTokens = $('#openai_max_tokens').val();
                const temperature = $('#openai_temperature').val();
                const spendCap = $('#openai_spend_cap').val();
                
                if (!apiKey) {
                    resultContainer.html('<div class="alert alert-danger mt-3"><i class="fas fa-exclamation-circle me-2"></i> @e__('API key is required.')</div>');
                    return;
                }
                
                submitButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> @e__('Saving...')');
                resultContainer.html('');
                
                $.ajax({
                    url: wpwps.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpwps_save_openai_settings',
                        nonce: wpwps.nonce,
                        api_key: apiKey,
                        max_tokens: maxTokens,
                        temperature: temperature,
                        spend_cap: spendCap
                    },
                    success: function(response) {
                        if (response.success) {
                            resultContainer.html('<div class="alert alert-success mt-3"><i class="fas fa-check-circle me-2"></i> ' + response.data.message + '</div>');
                        } else {
                            resultContainer.html('<div class="alert alert-danger mt-3"><i class="fas fa-exclamation-circle me-2"></i> ' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        resultContainer.html('<div class="alert alert-danger mt-3"><i class="fas fa-exclamation-circle me-2"></i> @e__('An unexpected error occurred.')</div>');
                    },
                    complete: function() {
                        submitButton.prop('disabled', false).html('<i class="fas fa-save me-2"></i> @e__('Save Settings')');
                    }
                });
            });
            
            // Make sure we're on the right tab if there was an error or success message
            if ($('#printify-connection-result').find('.alert').length > 0) {
                $('#printify-tab').tab('show');
            } else if ($('#openai-connection-result').find('.alert').length > 0) {
                $('#openai-tab').tab('show');
            }
        });
    </script>
@endsection