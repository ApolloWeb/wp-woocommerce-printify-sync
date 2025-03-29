@extends('layout')

@section('title', $title)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ $title }}</h5>
                    <a href="?page=wpwps-tickets" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form id="newTicketForm">
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="order_id" class="form-label">Related Order (Optional)</label>
                        <select class="form-select" id="order_id" name="order_id">
                            <option value="">No order</option>
                            @php
                                $orders = wc_get_orders(['limit' => 50]);
                            @endphp
                            @foreach($orders as $order)
                                <option value="{{ $order->get_id() }}">#{{ $order->get_id() }} - {{ $order->get_billing_first_name() }} {{ $order->get_billing_last_name() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Create Ticket
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Help</h6>
            </div>
            <div class="card-body">
                <p class="mb-0">Create a new support ticket to track customer issues or inquiries. If the ticket is related to a specific order, you can select it from the dropdown menu.</p>
            </div>
        </div>
    </div>
</div>
@endsection