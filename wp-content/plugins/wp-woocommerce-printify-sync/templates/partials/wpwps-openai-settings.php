<form id="openaiSettingsForm">
    <div class="mb-3">
        <label for="openai_api_key" class="form-label">API Key</label>
        <div class="input-group">
            <input type="password" class="form-control" id="openai_api_key" 
                   value="<?php echo esc_attr($settings['openai_api_key']); ?>"
                   aria-describedby="openaiApiHelp">
            <button class="btn btn-outline-secondary toggle-password" type="button" aria-label="Toggle API key visibility">
                <i class="fas fa-eye" aria-hidden="true"></i>
            </button>
        </div>
        <div id="openaiApiHelp" class="form-text">Enter your OpenAI API key for AI-powered features</div>
    </div>
    <div class="mb-3">
        <label for="token_limit" class="form-label">Max Tokens</label>
        <input type="number" class="form-control" id="token_limit" min="1" max="4000"
               value="<?php echo esc_attr($settings['token_limit']); ?>"
               aria-describedby="tokenHelp">
        <div id="tokenHelp" class="form-text">Maximum number of tokens per request (1-4000)</div>
    </div>
    <div class="mb-3">
        <label for="temperature" class="form-label">Temperature <span id="temperatureValue" aria-label="Current temperature value"><?php echo esc_attr($settings['temperature']); ?></span></label>
        <input type="range" class="form-range" id="temperature" min="0" max="1" step="0.1" 
               value="<?php echo esc_attr($settings['temperature']); ?>"
               aria-describedby="temperatureHelp">
        <div id="temperatureHelp" class="form-text">Lower values = more focused, Higher values = more creative</div>
    </div>
    <div class="mb-3">
        <label for="monthly_spend_cap" class="form-label">Monthly Spend Cap ($)</label>
        <input type="number" class="form-control" id="monthly_spend_cap" min="1"
               value="<?php echo esc_attr($settings['monthly_spend_cap']); ?>"
               aria-describedby="spendCapHelp">
        <div id="spendCapHelp" class="form-text">Set your maximum monthly spend limit</div>
    </div>
    <div class="mb-3">
        <label class="form-label">Credit Balance</label>
        <div id="creditBalance" class="alert <?php echo $credit_balance < 2 ? 'alert-danger' : 'alert-info'; ?>" role="alert">
            $<?php echo number_format($credit_balance, 2); ?>
        </div>
    </div>
    <div class="mb-3">
        <button type="button" id="testOpenAI" class="btn btn-secondary" aria-describedby="openaiStatus">
            Test & Estimate Cost
        </button>
        <div id="openaiStatus" class="visually-hidden" role="status"></div>
    </div>
</form>
