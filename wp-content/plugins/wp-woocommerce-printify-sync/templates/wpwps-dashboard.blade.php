@extends('layout')

@section('title', $title)

@section('content')
<div class="row">
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Products</h5>
                <div class="d-flex align-items-center">
                    <div class="display-4 me-3">
                        <i class="fas fa-box text-primary"></i>
                    </div>
                    <div>
                        <h2 class="mb-0">0</h2>
                        <small class="text-muted">Synced Products</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Orders</h5>
                <div class="d-flex align-items-center">
                    <div class="display-4 me-3">
                        <i class="fas fa-shopping-cart text-success"></i>
                    </div>
                    <div>
                        <h2 class="mb-0">0</h2>
                        <small class="text-muted">Total Orders</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Revenue</h5>
                <div class="d-flex align-items-center">
                    <div class="display-4 me-3">
                        <i class="fas fa-dollar-sign text-warning"></i>
                    </div>
                    <div>
                        <h2 class="mb-0">$0</h2>
                        <small class="text-muted">Total Revenue</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Support</h5>
                <div class="d-flex align-items-center">
                    <div class="display-4 me-3">
                        <i class="fas fa-ticket-alt text-info"></i>
                    </div>
                    <div>
                        <h2 class="mb-0">0</h2>
                        <small class="text-muted">Open Tickets</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Sales Overview</h5>
                <canvas id="salesChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Recent Orders</h5>
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0">
                        <div class="d-flex w-100 justify-content-between">
                            <p class="mb-1">No orders found</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection