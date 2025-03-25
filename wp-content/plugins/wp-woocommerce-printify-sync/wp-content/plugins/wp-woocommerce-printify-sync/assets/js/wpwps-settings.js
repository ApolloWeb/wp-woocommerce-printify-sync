document.addEventListener('DOMContentLoaded', function () {
    const settingsForm = document.getElementById('wpwps-settings-form');
    const testConnectionButton = document.getElementById('test-connection');
    const testConnectionResult = document.getElementById('test-connection-result');

    // Test connection to Printify API
    testConnectionButton.addEventListener('click', function () {
        const apiKey = document.getElementById('printify-api-key').value;
        const apiEndpoint = document.getElementById('api-endpoint').value;

        fetch(`${apiEndpoint}/shops.json`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${apiKey}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data && data.data) {
                testConnectionResult.innerHTML = `<div class="alert alert-success">Connection successful. Shops: ${data.data.map(shop => shop.title).join(', ')}</div>`;
                const shopSelect = document.getElementById('shop-id');
                shopSelect.innerHTML = data.data.map(shop => `<option value="${shop.id}">${shop.title}</option>`).join('');
            } else {
                testConnectionResult.innerHTML = `<div class="alert alert-danger">Connection failed. Please check your API key and endpoint.</div>`;
            }
        })
        .catch(error => {
            testConnectionResult.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        });
    });

    // Save settings
    settingsForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(settingsForm);
        const settings = {};
        formData.forEach((value, key) => {
            settings[key] = value;
        });

        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'save_settings',
                settings: settings
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Settings saved successfully.');
            } else {
                alert('Failed to save settings.');
            }
        })
        .catch(error => {
            alert(`Error: ${error.message}`);
        });
    });
});
