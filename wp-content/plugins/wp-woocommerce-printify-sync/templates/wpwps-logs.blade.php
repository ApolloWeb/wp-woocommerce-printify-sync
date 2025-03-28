@extends('layout')

@section('actions')
    <div class="d-flex">
        <button class="btn btn-secondary me-2" id="refresh-logs">
            <i class="fa fa-sync me-1"></i> Refresh
        </button>
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="logTypeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                {{ empty($current_type) ? 'All Logs' : ucfirst($current_type) . ' Logs' }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="logTypeDropdown">
                <li><a class="dropdown-item {{ empty($current_type) ? 'active' : '' }}" href="?page=wpwps-logs">All Logs</a></li>
                <li><a class="dropdown-item {{ $current_type == 'api' ? 'active' : '' }}" href="?page=wpwps-logs&type=api">API Logs</a></li>
                <li><a class="dropdown-item {{ $current_type == 'sync' ? 'active' : '' }}" href="?page=wpwps-logs&type=sync">Sync Logs</a></li>
                <li><a class="dropdown-item {{ $current_type == 'webhook' ? 'active' : '' }}" href="?page=wpwps-logs&type=webhook">Webhook Logs</a></li>
                <li><a class="dropdown-item {{ $current_type == 'error' ? 'active' : '' }}" href="?page=wpwps-logs&type=error">Error Logs</a></li>
            </ul>
        </div>
    </div>
@endsection

@section('content')
    <div class="wpwps-logs">
        <!-- Filters Row -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="filters-row">
                    <form method="get" class="row g-3 align-items-end">
                        <input type="hidden" name="page" value="wpwps-logs">
                        @if(!empty($current_type))
                            <input type="hidden" name="type" value="{{ $current_type }}">
                        @endif
                        
                        <div class="col-md-4">
                            <label for="search" class="filter-label">Search</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa fa-search"></i></span>
                                <input type="text" class="form-control" id="search" name="search" placeholder="Search logs..." value="{{ isset($_GET['search']) ? $_GET['search'] : '' }}">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="date_from" class="filter-label">From Date</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="{{ isset($_GET['date_from']) ? $_GET['date_from'] : '' }}">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="date_to" class="filter-label">To Date</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="{{ isset($_GET['date_to']) ? $_GET['date_to'] : '' }}">
                        </div>
                        
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fa fa-filter me-2"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card bg-light mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Log Statistics</h5>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <h2 class="stats-number">{{ $stats['total'] ?? 0 }}</h2>
                                    <div class="stats-label">Total Logs</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <h2 class="stats-number">{{ $stats['api'] ?? 0 }}</h2>
                                    <div class="stats-label">API Requests</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <h2 class="stats-number">{{ $stats['error'] ?? 0 }}</h2>
                                    <div class="stats-label">Errors</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <h2 class="stats-number">{{ $stats['webhook'] ?? 0 }}</h2>
                                    <div class="stats-label">Webhook Events</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs Table Card -->
        <div class="card">
            <div class="card-body p-0">
                @if(count($logs) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover logs-table">
                            <thead>
                                <tr>
                                    <th class="time-col">Time</th>
                                    <th class="type-col">Type</th>
                                    <th class="message-col">Message</th>
                                    <th class="actions-col">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $log)
                                    <tr>
                                        <td>
                                            <div>{{ date('Y-m-d H:i:s', $log['timestamp']) }}</div>
                                            <small class="text-muted">{{ human_time_diff($log['timestamp'], time()) }} ago</small>
                                        </td>
                                        <td>
                                            @if($log['type'] == 'error')
                                                <span class="badge badge-error">Error</span>
                                            @elseif($log['type'] == 'api')
                                                <span class="badge badge-api">API</span>
                                            @elseif($log['type'] == 'sync')
                                                <span class="badge badge-sync">Sync</span>
                                            @elseif($log['type'] == 'webhook')
                                                <span class="badge badge-webhook">Webhook</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($log['type']) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $log['message'] }}</td>
                                        <td>
                                            @if(!empty($log['details']))
                                                <button class="btn btn-sm btn-outline-secondary view-details" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#logDetailsModal"
                                                    data-log-id="{{ $log['id'] }}"
                                                    data-log-details="{{ json_encode($log['details']) }}">
                                                    <i class="fa fa-eye me-1"></i> View
                                                </button>
                                            @else
                                                <span class="text-muted">No details</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="logs-empty-state">
                        <div class="logs-empty-illustration">
                            <i class="fa fa-clipboard-list"></i>
                        </div>
                        <h3>No Logs Found</h3>
                        <p class="text-muted">There are no logs matching your current filters.</p>
                        <a href="?page=wpwps-logs" class="btn btn-primary mt-3">
                            <i class="fa fa-redo me-2"></i> Reset Filters
                        </a>
                    </div>
                @endif
            </div>
            
            @if(isset($pagination) && $pagination['total_pages'] > 1)
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            Showing {{ $pagination['start'] }} to {{ $pagination['end'] }} of {{ $pagination['total'] }} logs
                        </div>
                        <nav aria-label="Pagination">
                            <ul class="pagination mb-0">
                                <li class="page-item {{ $pagination['current_page'] == 1 ? 'disabled' : '' }}">
                                    <a class="page-link" href="?page=wpwps-logs&p={{ $pagination['current_page'] - 1 }}{{ !empty($current_type) ? '&type=' . $current_type : '' }}{{ isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' }}{{ isset($_GET['date_from']) ? '&date_from=' . $_GET['date_from'] : '' }}{{ isset($_GET['date_to']) ? '&date_to=' . $_GET['date_to'] : '' }}" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                @for($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++)
                                    <li class="page-item {{ $i == $pagination['current_page'] ? 'active' : '' }}">
                                        <a class="page-link" href="?page=wpwps-logs&p={{ $i }}{{ !empty($current_type) ? '&type=' . $current_type : '' }}{{ isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' }}{{ isset($_GET['date_from']) ? '&date_from=' . $_GET['date_from'] : '' }}{{ isset($_GET['date_to']) ? '&date_to=' . $_GET['date_to'] : '' }}">{{ $i }}</a>
                                    </li>
                                @endfor
                                
                                <li class="page-item {{ $pagination['current_page'] == $pagination['total_pages'] ? 'disabled' : '' }}">
                                    <a class="page-link" href="?page=wpwps-logs&p={{ $pagination['current_page'] + 1 }}{{ !empty($current_type) ? '&type=' . $current_type : '' }}{{ isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' }}{{ isset($_GET['date_from']) ? '&date_from=' . $_GET['date_from'] : '' }}{{ isset($_GET['date_to']) ? '&date_to=' . $_GET['date_to'] : '' }}" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Log Details Modal -->
    <div class="modal fade log-details-modal" id="logDetailsModal" tabindex="-1" aria-labelledby="logDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logDetailsModalLabel">Log Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <pre id="logDetailsContent"></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary copy-details">
                        <i class="fa fa-copy me-1"></i> Copy to Clipboard
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            // Handle log details modal
            $('.view-details').on('click', function() {
                var details = $(this).data('log-details');
                var formattedDetails = JSON.stringify(details, null, 2);
                $('#logDetailsContent').text(formattedDetails);
            });
            
            // Copy to clipboard
            $('.copy-details').on('click', function() {
                var content = $('#logDetailsContent').text();
                navigator.clipboard.writeText(content).then(function() {
                    $(this).html('<i class="fa fa-check me-1"></i> Copied!');
                    setTimeout(() => {
                        $(this).html('<i class="fa fa-copy me-1"></i> Copy to Clipboard');
                    }, 2000);
                }.bind(this));
            });
            
            // Refresh logs
            $('#refresh-logs').on('click', function() {
                location.reload();
            });
            
            // Show loading indicator when filtering
            $('form').on('submit', function() {
                $(this).find('button[type="submit"]').html('<i class="fa fa-spinner fa-spin me-2"></i> Filtering...').prop('disabled', true);
            });
        });
    </script>
@endsection