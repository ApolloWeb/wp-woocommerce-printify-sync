@extends('layout')

@section('actions')
    <div class="d-flex">
        <button class="btn btn-secondary me-2" id="refresh-logs">
            <i class="fa fa-sync me-1"></i> Refresh
        </button>
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="logTypeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                {{ $current_type ?? 'All Logs' }}
            </button>
            <ul class="dropdown-menu" aria-labelledby="logTypeDropdown">
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
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-light">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Log Statistics</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="border rounded p-3 text-center">
                                <h2 class="mb-1">{{ $stats['total'] ?? 0 }}</h2>
                                <div class="small text-muted">Total Logs</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3 text-center">
                                <h2 class="mb-1">{{ $stats['api'] ?? 0 }}</h2>
                                <div class="small text-muted">API Requests</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3 text-center">
                                <h2 class="mb-1">{{ $stats['error'] ?? 0 }}</h2>
                                <div class="small text-muted">Errors</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3 text-center">
                                <h2 class="mb-1">{{ $stats['webhook'] ?? 0 }}</h2>
                                <div class="small text-muted">Webhook Events</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Type</th>
                    <th>Message</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                @if(count($logs) > 0)
                    @foreach($logs as $log)
                        <tr>
                            <td>{{ date('Y-m-d H:i:s', $log['timestamp']) }}</td>
                            <td>
                                @if($log['type'] == 'error')
                                    <span class="badge bg-danger">Error</span>
                                @elseif($log['type'] == 'api')
                                    <span class="badge bg-info">API</span>
                                @elseif($log['type'] == 'sync')
                                    <span class="badge bg-primary">Sync</span>
                                @elseif($log['type'] == 'webhook')
                                    <span class="badge bg-warning">Webhook</span>
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
                                        View
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="4" class="text-center py-4">No log entries found</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    @if(isset($pagination) && $pagination['total_pages'] > 1)
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div>
                Showing {{ $pagination['start'] }} to {{ $pagination['end'] }} of {{ $pagination['total'] }} logs
            </div>
            <nav aria-label="Pagination">
                <ul class="pagination mb-0">
                    <li class="page-item {{ $pagination['current_page'] == 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="?page=wpwps-logs&p={{ $pagination['current_page'] - 1 }}{{ !empty($current_type) ? '&type=' . $current_type : '' }}" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    @for($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++)
                        <li class="page-item {{ $i == $pagination['current_page'] ? 'active' : '' }}">
                            <a class="page-link" href="?page=wpwps-logs&p={{ $i }}{{ !empty($current_type) ? '&type=' . $current_type : '' }}">{{ $i }}</a>
                        </li>
                    @endfor
                    
                    <li class="page-item {{ $pagination['current_page'] == $pagination['total_pages'] ? 'disabled' : '' }}">
                        <a class="page-link" href="?page=wpwps-logs&p={{ $pagination['current_page'] + 1 }}{{ !empty($current_type) ? '&type=' . $current_type : '' }}" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    @endif

    <!-- Log Details Modal -->
    <div class="modal fade" id="logDetailsModal" tabindex="-1" aria-labelledby="logDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logDetailsModalLabel">Log Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <pre id="logDetailsContent" class="bg-light p-3 rounded" style="max-height: 400px; overflow: auto;"></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
            
            // Refresh logs
            $('#refresh-logs').on('click', function() {
                location.reload();
            });
        });
    </script>
@endsection