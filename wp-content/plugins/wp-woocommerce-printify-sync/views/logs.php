@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{{ plugin_asset('css/pages/logs.css') }}">
@endsection

@section('header-actions')
    <div class="d-flex gap-3">
        <div class="input-group w-auto">
            <select class="form-select" id="log-level">
                <option value="">All Levels</option>
                <option value="info">Info</option>
                <option value="warning">Warning</option>
                <option value="error">Error</option>
            </select>
            <input type="text" class="form-control" id="log-search" placeholder="Search logs...">
            <button class="btn btn-outline-secondary" type="button">
                <i class="fas fa-search"></i>
            </button>
        </div>
        <button class="btn btn-outline-danger" id="clear-logs">
            <i class="fas fa-trash"></i> Clear Logs
        </button>
    </div>
@endsection

@section('content')
    <div class="wpwps-card">
        <div class="card-body">
            <!-- Log Filters -->
            @include('partials.logs.filters')

            <!-- Live Log Feed -->
            <div class="log-feed" id="log-feed">
                @foreach($logs as $log)
                    @include('partials.logs.entry', ['log' => $log])
                @endforeach
            </div>

            <!-- Load More -->
            <div class="text-center mt-4">
                <button class="btn btn-outline-primary" id="load-more-logs">
                    Load More <i class="fas fa-chevron-down"></i>
                </button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ plugin_asset('js/pages/logs.js') }}"></script>
    <script>
        WPWPS.Logs.init({
            autoRefresh: true,
            refreshInterval: 5000,
            maxEntries: 100
        });
    </script>
@endsection