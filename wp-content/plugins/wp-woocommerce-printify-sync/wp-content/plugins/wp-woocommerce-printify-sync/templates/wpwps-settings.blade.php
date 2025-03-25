<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WP WooCommerce Printify Sync Settings</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="{{ plugins_url('assets/css/wpwps-settings.css', __FILE__) }}">
</head>
<body>
    @include('partials.navbar')
    <div class="container-fluid">
        <div class="row">
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                @include('partials.sidebar')
            </nav>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Settings</h1>
                </div>
                <form id="wpwps-settings-form">
                    <div class="mb-3">
                        <label for="printify-api-key" class="form-label">Printify API Key</label>
                        <input type="password" class="form-control" id="printify-api-key" name="printify_api_key" required>
                    </div>
                    <div class="mb-3">
                        <label for="api-endpoint" class="form-label">API Endpoint</label>
                        <input type="text" class="form-control" id="api-endpoint" name="api_endpoint" value="https://api.printify.com/v1" required>
                    </div>
                    <div class="mb-3">
                        <label for="shop-id" class="form-label">Shop ID</label>
                        <select class="form-select" id="shop-id" name="shop_id" required>
                            <option value="">Select Shop</option>
                            <!-- Options will be populated via AJAX -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="chatgpt-api-key" class="form-label">ChatGPT API Key</label>
                        <input type="password" class="form-control" id="chatgpt-api-key" name="chatgpt_api_key" required>
                    </div>
                    <div class="mb-3">
                        <label for="monthly-spend-cap" class="form-label">Monthly Spend Cap</label>
                        <input type="number" class="form-control" id="monthly-spend-cap" name="monthly_spend_cap" required>
                    </div>
                    <div class="mb-3">
                        <label for="number-of-tokens" class="form-label">Number of Tokens</label>
                        <input type="number" class="form-control" id="number-of-tokens" name="number_of_tokens" required>
                    </div>
                    <div class="mb-3">
                        <label for="temperature" class="form-label">Temperature</label>
                        <input type="number" class="form-control" id="temperature" name="temperature" step="0.1" required>
                    </div>
                    <button type="button" id="test-connection" class="btn btn-primary">Test Connection</button>
                    <button type="submit" class="btn btn-success">Save Settings</button>
                </form>
                <div id="test-connection-result" class="mt-3"></div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/js/bootstrap.min.js"></script>
    <script src="{{ plugins_url('assets/js/wpwps-settings.js', __FILE__) }}"></script>
</body>
</html>
