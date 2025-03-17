@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{{ plugin_asset('css/pages/settings.css') }}">
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <!-- API Configuration -->
            <div class="wpwps-card mb-4">
                <div class="card-body">
                    <h5 class="card-title">API Configuration</h5>
                    <form id="api-settings-form">
                        @include('partials.settings.api-form')
                    </form>
                </div>
            </div>

            <!-- Sync Settings -->
            <div class="wpwps-card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Sync Settings</h5>
                    <form id="sync-settings-form">
                        @include('partials.settings.sync-form')
                    </form>
                </div>
            </div>

            <!-- Advanced Settings -->
            <div class="wpwps-card">
                <div class="card-body">
                    <h5 class="card-title">Advanced Settings</h5>
                    <form id="advanced-settings-form">
                        @include('partials.settings.advanced-form')
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <!-- Connection Status -->
            <div class="wpwps-card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Connection Status</h5>
                    @include('partials.settings.connection-status')
                </div>
            </div>

            <!-- System Information -->
            <div class="wpwps-card">
                <div class="card-body">
                    <h5 class="card-title">System Information</h5>
                    @include('partials.settings.system-info')
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ plugin_asset('js/pages/settings.js') }}"></script>
@endsection