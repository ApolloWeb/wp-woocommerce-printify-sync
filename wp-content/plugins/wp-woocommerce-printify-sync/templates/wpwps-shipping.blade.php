@extends('layouts.wpwps-main')

@section('title', 'Shipping')
@section('page-title', 'Shipping Configuration')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="wpwps-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Shipping Profiles</h4>
                <button type="button" class="btn btn-primary" id="syncShipping">
                    <i class="fas fa-sync-alt me-2"></i>Sync Profiles
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Provider</th>
                            <th>Profile</th>
                            <th>Base Rate</th>
                            <th>Additional Item</th>
                            <th>Locations</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shipping_profiles as $providerId => $profiles)
                            @foreach($profiles as $profile)
                                <tr>
                                    <td>{{ $providerId }}</td>
                                    <td>{{ $profile['name'] }}</td>
                                    <td>${{ number_format($profile['rates']['first_item'], 2) }}</td>
                                    <td>${{ number_format($profile['rates']['additional_item'], 2) }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-primary me-2">
                                                {{ count($profile['locations']) }}
                                            </span>
                                            <button type="button" 
                                                    class="btn btn-sm btn-link view-locations"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#locationsModal"
                                                    data-locations="{{ json_encode($profile['locations']) }}">
                                                View
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" 
                                                    class="btn btn-outline-primary edit-profile"
                                                    data-profile-id="{{ $profile['id'] }}"
                                                    data-provider-id="{{ $providerId }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-outline-primary map-zones"
                                                    data-profile-id="{{ $profile['id'] }}"
                                                    data-provider-id="{{ $providerId }}">
                                                <i class="fas fa-map-marker-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-truck fa-2x mb-2 text-muted"></i>
                                    <p class="mb-0">No shipping profiles found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="wpwps-card p-4">
            <h4 class="mb-4">Zone Mapping</h4>
            <form id="zoneMappingForm">
                @foreach($shipping_zones as $zone)
                    <div class="mb-4">
                        <h6>{{ $zone['name'] }}</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Region</th>
                                        <th>Provider</th>
                                        <th>Profile</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($zone['regions'] as $region)
                                        <tr>
                                            <td>
                                                {{ $region['type'] === 'country' ? $region['code'] : 'All Countries' }}
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm provider-select" 
                                                        data-zone-id="{{ $zone['id'] }}"
                                                        data-region="{{ $region['code'] }}">
                                                    <option value="">Select Provider</option>
                                                    @foreach($providers as $provider)
                                                        <option value="{{ $provider['id'] }}"
                                                                {{ isset($shipping_mappings[$zone['id']][$region['code']]['provider']) && 
                                                                   $shipping_mappings[$zone['id']][$region['code']]['provider'] === $provider['id'] 
                                                                   ? 'selected' : '' }}>
                                                            {{ $provider['name'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm profile-select" 
                                                        data-zone-id="{{ $zone['id'] }}"
                                                        data-region="{{ $region['code'] }}">
                                                    <option value="">Select Profile</option>
                                                    @if(isset($shipping_mappings[$zone['id']][$region['code']]['provider']))
                                                        @foreach($shipping_profiles[$shipping_mappings[$zone['id']][$region['code']]['provider']] ?? [] as $profile)
                                                            <option value="{{ $profile['id'] }}"
                                                                    {{ isset($shipping_mappings[$zone['id']][$region['code']]['profile']) && 
                                                                       $shipping_mappings[$zone['id']][$region['code']]['profile'] === $profile['id'] 
                                                                       ? 'selected' : '' }}>
                                                                {{ $profile['name'] }}
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Mappings
                </button>
            </form>
        </div>
    </div>

    <div class="col-md-4">
        <div class="wpwps-card p-4 mb-4">
            <h5 class="mb-3">Shipping Overview</h5>
            <div class="list-group list-group-flush">
                <div class="list-group-item border-0 px-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Total Providers</span>
                        <span class="badge bg-primary">{{ count($providers) }}</span>
                    </div>
                </div>
                <div class="list-group-item border-0 px-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Total Profiles</span>
                        <span class="badge bg-primary">
                            {{ array_sum(array_map('count', $shipping_profiles)) }}
                        </span>
                    </div>
                </div>
                <div class="list-group-item border-0 px-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Mapped Zones</span>
                        <span class="badge bg-primary">
                            {{ count($shipping_mappings) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="wpwps-card p-4">
            <h5 class="mb-3">Quick Actions</h5>
            <div class="list-group">
                <a href="#" class="list-group-item list-group-item-action" id="createZone">
                    <i class="fas fa-plus-circle me-2"></i>Create New Zone
                </a>
                <a href="#" class="list-group-item list-group-item-action" id="bulkUpdate">
                    <i class="fas fa-upload me-2"></i>Bulk Update Rates
                </a>
                <a href="#" class="list-group-item list-group-item-action" id="exportProfiles">
                    <i class="fas fa-download me-2"></i>Export Profiles
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Locations Modal -->
<div class="modal fade" id="locationsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Shipping Locations</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="list-group list-group-flush" id="locationsList"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('additional-css')
<style>
.provider-select, .profile-select {
    min-width: 150px;
}
</style>
@endsection

@section('additional-js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const syncBtn = document.getElementById('syncShipping');
    const providerSelects = document.querySelectorAll('.provider-select');
    const profileSelects = document.querySelectorAll('.profile-select');
    const shippingProfiles = @json($shipping_profiles);
    
    syncBtn?.addEventListener('click', async function() {
        const btn = this;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Syncing...';

        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'wpwps_sync_shipping',
                    nonce: '{{ wp_create_nonce("wpwps_ajax_nonce") }}'
                })
            });

            const data = await response.json();
            
            if (data.success) {
                showToast('Shipping profiles synchronized successfully!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('Sync failed: ' + data.data.message, 'error');
            }
        } catch (error) {
            showToast('Sync failed: ' + error.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });

    // Handle provider selection changes
    providerSelects.forEach(select => {
        select.addEventListener('change', function() {
            const zoneId = this.dataset.zoneId;
            const region = this.dataset.region;
            const providerId = this.value;
            const profileSelect = document.querySelector(`.profile-select[data-zone-id="${zoneId}"][data-region="${region}"]`);

            // Clear and update profile options
            profileSelect.innerHTML = '<option value="">Select Profile</option>';
            if (providerId && shippingProfiles[providerId]) {
                shippingProfiles[providerId].forEach(profile => {
                    const option = document.createElement('option');
                    option.value = profile.id;
                    option.textContent = profile.name;
                    profileSelect.appendChild(option);
                });
            }
        });
    });

    // Handle zone mapping form submission
    document.getElementById('zoneMappingForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const mappings = {};

        // Collect all mappings
        providerSelects.forEach(select => {
            const zoneId = select.dataset.zoneId;
            const region = select.dataset.region;
            const providerId = select.value;
            const profileId = document.querySelector(
                `.profile-select[data-zone-id="${zoneId}"][data-region="${region}"]`
            ).value;

            if (providerId && profileId) {
                if (!mappings[zoneId]) {
                    mappings[zoneId] = {};
                }
                mappings[zoneId][region] = {
                    provider: providerId,
                    profile: profileId
                };
            }
        });

        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'wpwps_update_shipping_mapping',
                    nonce: '{{ wp_create_nonce("wpwps_ajax_nonce") }}',
                    mappings: JSON.stringify(mappings)
                })
            });

            const data = await response.json();
            
            if (data.success) {
                showToast('Shipping mappings updated successfully!', 'success');
            } else {
                showToast('Update failed: ' + data.data.message, 'error');
            }
        } catch (error) {
            showToast('Update failed: ' + error.message, 'error');
        }
    });

    // Handle location view
    document.querySelectorAll('.view-locations').forEach(btn => {
        btn.addEventListener('click', function() {
            const locations = JSON.parse(this.dataset.locations);
            const list = document.getElementById('locationsList');
            list.innerHTML = locations.map(location => `
                <div class="list-group-item">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    ${location.country}
                </div>
            `).join('');
        });
    });
});
</script>
@endsection