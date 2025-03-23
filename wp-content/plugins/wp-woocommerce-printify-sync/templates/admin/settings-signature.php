<div class="wpwps-card mb-4">
    <div class="card-body">
        <h5 class="card-title">Email Signature</h5>
        <form id="signature-settings-form">
            <div class="row g-3">
                <div class="col-md-6">
                    <!-- Logo Upload -->
                    <div class="mb-3">
                        <label class="form-label">Signature Logo</label>
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?php echo esc_url($signature_logo); ?>" 
                                 class="wpwps-logo-preview" 
                                 style="max-width: 200px; display: <?php echo empty($signature_logo) ? 'none' : 'block'; ?>">
                            <button type="button" class="btn btn-outline-primary" id="upload-signature-logo">
                                <i class="fas fa-upload me-2"></i> Upload Logo
                            </button>
                            <input type="hidden" id="signature-logo-url" 
                                   value="<?php echo esc_attr($signature_logo); ?>">
                        </div>
                    </div>

                    <!-- Social Links -->
                    <div class="mb-3">
                        <label class="form-label">Social Links</label>
                        <div id="social-links-container">
                            <?php foreach ($social_links as $network => $url): ?>
                            <div class="input-group mb-2">
                                <span class="input-group-text">
                                    <i class="fab fa-<?php echo esc_attr($network); ?>"></i>
                                </span>
                                <input type="url" class="form-control" 
                                       name="social_links[<?php echo esc_attr($network); ?>]" 
                                       value="<?php echo esc_url($url); ?>"
                                       placeholder="<?php echo esc_attr(ucfirst($network)); ?> URL">
                            </div>
                            <?php endforeach; ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="add-social-link">
                                <i class="fas fa-plus me-2"></i> Add Social Link
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <!-- Signature Preview -->
                    <div class="wpwps-signature-preview-container">
                        <h6>Live Preview</h6>
                        <div class="border rounded p-3 bg-light">
                            <?php echo $signature_preview; ?>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Save Signature Settings</button>
        </form>
    </div>
</div>
