@extends('layout')

@section('title', $title)

@section('content')
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Printify Settings</h5>
                <form id="printifyForm">
                    <div class="mb-3">
                        <label for="printifyKey" class="form-label">API Key</label>
                        <input type="password" class="form-control" id="printifyKey" name="printify_key" value="{{ $printify_key }}">
                    </div>
                    <div class="mb-3">
                        <label for="printifyEndpoint" class="form-label">API Endpoint</label>
                        <input type="url" class="form-control" id="printifyEndpoint" name="printify_endpoint" value="{{ $printify_endpoint }}">
                    </div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-info" id="testPrintify">
                            <i class="fas fa-vial"></i> Test Connection
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">OpenAI Settings</h5>
                <form id="openaiForm">
                    <div class="mb-3">
                        <label for="openaiKey" class="form-label">API Key</label>
                        <input type="password" class="form-control" id="openaiKey" name="openai_key" value="{{ $openai_key }}">
                    </div>
                    <div class="mb-3">
                        <label for="tokenLimit" class="form-label">Token Limit</label>
                        <input type="number" class="form-control" id="tokenLimit" name="token_limit" value="{{ $openai_token_limit }}">
                    </div>
                    <div class="mb-3">
                        <label for="temperature" class="form-label">Temperature</label>
                        <input type="range" class="form-range" id="temperature" name="temperature" min="0" max="1" step="0.1" value="{{ $openai_temperature }}">
                        <small class="text-muted">Current: <span id="temperatureValue">{{ $openai_temperature }}</span></small>
                    </div>
                    <div class="mb-3">
                        <label for="spendCap" class="form-label">Monthly Spend Cap ($)</label>
                        <input type="number" class="form-control" id="spendCap" name="spend_cap" value="{{ $openai_spend_cap }}">
                    </div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-info" id="testOpenAI">
                            <i class="fas fa-vial"></i> Test Connection
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="wpwps-toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055;"></div>
@endsection