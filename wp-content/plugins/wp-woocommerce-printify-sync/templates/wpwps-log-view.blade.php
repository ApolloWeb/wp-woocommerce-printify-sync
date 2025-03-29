@extends('layout')

@section('title', $title)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ $filename }}</h5>
                    <a href="?page=wpwps-logs" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="log-viewer">
                    <div class="log-controls mb-3">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleWrap">
                                <i class="fas fa-text-width"></i> Toggle Wrap
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleTimestamps">
                                <i class="fas fa-clock"></i> Toggle Timestamps
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearHighlights">
                                <i class="fas fa-eraser"></i> Clear Highlights
                            </button>
                            <div class="flex-grow-1"></div>
                            <div class="input-group input-group-sm" style="width: 200px;">
                                <input type="text" class="form-control" id="logSearch" placeholder="Search logs...">
                                <button class="btn btn-outline-secondary" type="button" id="prevMatch">
                                    <i class="fas fa-chevron-up"></i>
                                </button>
                                <button class="btn btn-outline-secondary" type="button" id="nextMatch">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <pre class="log-content" id="logContent">{{ $content }}</pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection