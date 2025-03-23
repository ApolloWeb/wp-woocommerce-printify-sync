<?php $this->layout('admin/layout', ['title' => __('Settings', 'wp-woocommerce-printify-sync')]) ?>

<div class="wpps-card p-4">
    <form id="wpps-settings-form" class="needs-validation" novalidate>
        <?php wp_nonce_field('wpps_admin'); ?>
        
        <!-- API Settings -->
        <div class="mb-4">
            <h3 class="h5 mb-3"><?= __('API Configuration', 'wp-woocommerce-printify-sync') ?></h3>
            
            <div class="mb-3">
                <label for="printify_key" class="form-label required">
                    <?= __('Printify API Key', 'wp-woocommerce-printify-sync') ?>
                </label>
                <div class="input-group">
                    <input type="password" class="form-control" name="printify_key" 
                           id="printify_key" value="<?= esc_attr($api_key) ?>" required>
                    <button class="btn btn-outline-secondary toggle-password" type="button">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="mt-2">
                    <button type="button" class="btn btn-primary" id="test_printify">
                        <?= __('Test Connection', 'wp-woocommerce-printify-sync') ?>
                    </button>
                </div>
            </div>

            <div class="mb-3 shop-selector d-none">
                <label for="shop_id" class="form-label required">
                    <?= __('Select Shop', 'wp-woocommerce-printify-sync') ?>
                </label>
                <select class="form-select" name="shop_id" id="shop_id" required>
                    <option value=""><?= __('Select a shop...', 'wp-woocommerce-printify-sync') ?></option>
                </select>
            </div>
        </div>

        <!-- ChatGPT Settings -->
        <div class="mb-4">
            <h3 class="h5 mb-3"><?= __('ChatGPT Configuration', 'wp-woocommerce-printify-sync') ?></h3>
            
            <div class="mb-3">
                <label for="chatgpt_key" class="form-label required">
                    <?= __('ChatGPT API Key', 'wp-woocommerce-printify-sync') ?>
                </label>
                <div class="input-group">
                    <input type="password" class="form-control" name="chatgpt_key"
                           id="chatgpt_key" value="<?= esc_attr($chatgpt_key) ?>" required>
                    <button class="btn btn-outline-secondary toggle-password" type="button">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="mb-3">
                <label for="chatgpt_model" class="form-label">
                    <?= __('Model', 'wp-woocommerce-printify-sync') ?>
                </label>
                <select class="form-select" name="chatgpt_model" id="chatgpt_model">
                    <option value="gpt-3.5-turbo" <?= selected($chatgpt_model, 'gpt-3.5-turbo', false) ?>>
                        GPT-3.5 Turbo
                    </option>
                    <option value="gpt-4" <?= selected($chatgpt_model, 'gpt-4', false) ?>>
                        GPT-4
                    </option>
                </select>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="monthly_cap" class="form-label">
                            <?= __('Monthly Request Cap', 'wp-woocommerce-printify-sync') ?>
                        </label>
                        <input type="number" class="form-control" name="monthly_cap"
                               id="monthly_cap" value="<?= esc_attr($monthly_cap) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="token_limit" class="form-label">
                            <?= __('Token Limit', 'wp-woocommerce-printify-sync') ?>
                        </label>
                        <input type="number" class="form-control" name="token_limit"
                               id="token_limit" value="<?= esc_attr($token_limit) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="temperature" class="form-label">
                            <?= __('Temperature', 'wp-woocommerce-printify-sync') ?>
                        </label>
                        <input type="number" class="form-control" name="temperature" step="0.1"
                               id="temperature" value="<?= esc_attr($temperature) ?>">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <button type="button" class="btn btn-primary" id="test_chatgpt">
                    <?= __('Test Connection', 'wp-woocommerce-printify-sync') ?>
                </button>
                <button type="button" class="btn btn-secondary" id="estimate_cost">
                    <?= __('Estimate Monthly Cost', 'wp-woocommerce-printify-sync') ?>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <?= __('Save Settings', 'wp-woocommerce-printify-sync') ?>
        </button>
    </form>
</div>
