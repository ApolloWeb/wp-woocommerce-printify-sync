<div class="row g-4">
    <div class="col-md-6">
        <label class="form-label">Sync Frequency</label>
        <select class="form-select" name="sync_frequency">
            <option value="15" {{ $settings['sync_frequency'] == 15 ? 'selected' : '' }}>Every 15 minutes</option>
            <option value="30" {{ $settings['sync_frequency'] == 30 ? 'selected' : '' }}>Every 30 minutes</option>
            <option value="60" {{ $settings['sync_frequency'] == 60 ? 'selected' : '' }}>Every hour</option>
            <option value="daily" {{ $settings['sync_frequency'] == 'daily' ? 'selected' : '' }}>Daily</option>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Batch Size</label>
        <input type="number" class="form-control" name="batch_size" value="{{ $settings['batch_size'] }}" min="1" max="100">
    </div>

    <div class="col-12">
        <div class="form-check form-switch">
            <input type="checkbox" class="form-check-input" id="auto-sync" name="auto_sync" {{ $settings['auto_sync'] ? 'checked' : '' }}>
            <label class="form-check-label" for="auto-sync">Enable automatic synchronization</label>
        </div>
    </div>

    <div class="col-12">
        <div class="form-check form-switch">
            <input type="checkbox" class="form-check-input" id="sync-images" name="sync_images" {{ $settings['sync_images'] ? 'checked' : '' }}>
            <label class="form-check-label" for="sync-images">Sync product images</label>
        </div>
    </div>

    <div class="col-12">
        <div class="form-check form-switch">
            <input type="checkbox" class="form-check-input" id="sync-variants" name="sync_variants" {{ $settings['sync_variants'] ? 'checked' : '' }}>
            <label class="form-check-label" for="sync-variants">Sync product variants</label>
        </div>
    </div>
</div>

<div class="text-end mt-4">
    <button type="submit" class="btn btn-primary">
        Save Sync Settings
    </button>
</div>