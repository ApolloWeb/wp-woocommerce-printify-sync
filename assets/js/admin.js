document.addEventListener('DOMContentLoaded', function () {
    const shopSelect = document.querySelector('select[name="printify_selected_shop"]');

    if (shopSelect) {
        shopSelect.addEventListener('change', function () {
            document.forms[0].submit();
        });
    }
});