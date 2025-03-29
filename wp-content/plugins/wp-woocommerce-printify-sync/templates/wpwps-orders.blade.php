@extends('layout')

@section('title', $title)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ $title }}</h5>
                    <button type="button" class="btn btn-primary" id="refreshOrders">
                        <i class="fas fa-sync"></i> Refresh Orders
                    </button>
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
                    <table class="table table-hover" id="ordersTable">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Printify ID</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Tracking</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                            <tr>
                                <td>{{ $order['id'] }}</td>
                                <td>{{ $order['printify_id'] }}</td>
                                <td>{{ $order['date_created'] }}</td>
                                <td>
                                    <span class="badge bg-{{ $order['status'] === 'completed' ? 'success' : ($order['status'] === 'processing' ? 'info' : 'warning') }}">
                                        {{ ucfirst($order['status']) }}
                                    </span>
                                </td>
                                <td>${{ number_format($order['total'], 2) }}</td>
                                <td>
                                    @if($order['tracking_number'])
                                        <a href="{{ $order['tracking_url'] }}" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fas fa-truck"></i> {{ $order['tracking_number'] }}
                                        </a>
                                    @else
                                        <span class="text-muted">No tracking</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ admin_url('post.php?post=' . $order['id'] . '&action=edit') }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">No orders found</td>
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