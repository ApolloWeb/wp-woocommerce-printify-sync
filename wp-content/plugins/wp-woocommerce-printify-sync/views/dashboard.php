@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{{ plugin_asset('css/pages/dashboard.css') }}">
@endsection

@section('header-actions')
    <div class="d-flex gap-3">
        <button class="btn btn-primary" id="start-sync">
            <i class="fas fa-sync-alt"></i> Start Sync
        </button>
        <button class="btn btn-outline-secondary" id="refresh-stats">
            <i class="fas fa-redo"></i> Refresh
        </button>
    </div>
@endsection

@section('content')
    <div class="row g-4">
        @include('partials.dashboard.stats')
        
        <div class="col-12">
            <div class="wpwps-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title">Sync Activity</h5>
                        <div class="btn-group">
                            <button class="btn btn-outline-primary btn-sm active">Day</button>
                            <button class="btn btn-outline-primary btn-sm">Week</button>
                            <button class="btn btn-outline-primary btn-sm">Month</button>
                        </div>
                    </div>
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="sync-activity-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        @include('partials.dashboard.recent-syncs')
        @include('partials.dashboard.failed-items')
    </div>
@endsection

@section('scripts')
    <script src="{{ plugin_asset('js/pages/dashboard.js') }}"></script>
    <script>
        WPWPS.Dashboard.init({
            syncStats: {!! json_encode($syncStats) !!},
            chartData: {!! json_encode($chartData) !!}
        });
    </script>
@endsection