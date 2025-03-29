@extends('layout')

@section('title', $title)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ $title }}</h5>
                    <a href="?page=wpwps-tickets&action=new" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Ticket
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
                <div class="table-responsive">
                    <table class="table table-hover" id="ticketsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Subject</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Last Response</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tickets as $ticket)
                            <tr>
                                <td>{{ $ticket->getId() }}</td>
                                <td>{{ $ticket->getSubject() }}</td>
                                <td>
                                    @if($ticket->getOrderId())
                                        <a href="{{ admin_url('post.php?post=' . $ticket->getOrderId() . '&action=edit') }}" target="_blank">
                                            #{{ $ticket->getOrderId() }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $ticket->getStatus() === 'closed' ? 'success' : ($ticket->getStatus() === 'pending' ? 'warning' : 'info') }}">
                                        {{ ucfirst($ticket->getStatus()) }}
                                    </span>
                                </td>
                                <td>{{ human_time_diff(strtotime($ticket->getCreatedAt())) }} ago</td>
                                <td>
                                    @php
                                        $responses = $ticket->getResponses();
                                        $lastResponse = end($responses);
                                    @endphp
                                    @if($lastResponse)
                                        {{ human_time_diff(strtotime($lastResponse['created_at'])) }} ago
                                    @else
                                        No responses
                                    @endif
                                </td>
                                <td>
                                    <a href="?page=wpwps-tickets&action=view&ticket_id={{ $ticket->getId() }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">No tickets found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection