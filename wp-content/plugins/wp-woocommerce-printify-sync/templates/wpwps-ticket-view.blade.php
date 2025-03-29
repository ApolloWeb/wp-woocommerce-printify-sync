@extends('layout')

@section('title', $title)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Ticket #{{ $ticket->getId() }}: {{ $ticket->getSubject() }}</h5>
                    <div>
                        <button type="button" class="btn btn-info me-2" id="generateResponse">
                            <i class="fas fa-robot"></i> Generate AI Response
                        </button>
                        <a href="?page=wpwps-tickets" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="ticket-thread">
                    <div class="ticket-message">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong>{{ get_userdata($ticket->getUserId())->display_name }}</strong>
                                <small class="text-muted ms-2">
                                    {{ human_time_diff(strtotime($ticket->getCreatedAt())) }} ago
                                </small>
                            </div>
                        </div>
                        <div class="ticket-content">
                            {{ $ticket->getMessage() }}
                        </div>
                    </div>

                    @foreach($ticket->getResponses() as $response)
                    <div class="ticket-response">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong>{{ get_userdata($response['user_id'])->display_name }}</strong>
                                <small class="text-muted ms-2">
                                    {{ human_time_diff(strtotime($response['created_at'])) }} ago
                                </small>
                            </div>
                        </div>
                        <div class="ticket-content">
                            {{ $response['message'] }}
                        </div>
                    </div>
                    @endforeach

                    <div class="ticket-reply mt-4">
                        <form id="replyForm">
                            <input type="hidden" name="ticket_id" value="{{ $ticket->getId() }}">
                            <div class="mb-3">
                                <label for="response" class="form-label">Your Response</label>
                                <textarea class="form-control" id="response" name="response" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Update Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">No change</option>
                                    <option value="open" {{ $ticket->getStatus() === 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="pending" {{ $ticket->getStatus() === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="closed" {{ $ticket->getStatus() === 'closed' ? 'selected' : '' }}>Closed</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-reply"></i> Send Response
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Ticket Details</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-{{ $ticket->getStatus() === 'closed' ? 'success' : ($ticket->getStatus() === 'pending' ? 'warning' : 'info') }}">
                            {{ ucfirst($ticket->getStatus()) }}
                        </span>
                    </dd>

                    <dt class="col-sm-4">Created</dt>
                    <dd class="col-sm-8">{{ date('M j, Y g:i a', strtotime($ticket->getCreatedAt())) }}</dd>

                    @if($ticket->getOrderId())
                    <dt class="col-sm-4">Order</dt>
                    <dd class="col-sm-8">
                        <a href="{{ admin_url('post.php?post=' . $ticket->getOrderId() . '&action=edit') }}" target="_blank">
                            #{{ $ticket->getOrderId() }}
                        </a>
                    </dd>
                    @endif
                </dl>
            </div>
        </div>

        @if($ticket->getOrderId())
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Order Details</h6>
            </div>
            <div class="card-body">
                @php
                    $order = wc_get_order($ticket->getOrderId());
                @endphp
                @if($order)
                <dl class="row mb-0">
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">{{ ucfirst($order->get_status()) }}</dd>

                    <dt class="col-sm-4">Total</dt>
                    <dd class="col-sm-8">${{ $order->get_total() }}</dd>

                    <dt class="col-sm-4">Items</dt>
                    <dd class="col-sm-8">
                        <ul class="list-unstyled mb-0">
                            @foreach($order->get_items() as $item)
                            <li>{{ $item->get_name() }} × {{ $item->get_quantity() }}</li>
                            @endforeach
                        </ul>
                    </dd>
                </dl>
                @else
                <p class="mb-0 text-muted">Order not found</p>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection