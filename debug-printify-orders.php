// ...existing code...

<form id="ordersForm">
    <div style="margin-bottom: 10px;">
        <label for="page">Page:</label>
        <input type="number" id="page" value="1" min="1" style="width: 60px;">
        <label for="limit">Per Page (max 50):</label>
        <input type="number" id="limit" value="50" min="1" max="50" style="width: 60px;">
    </div>
    <button type="submit">Fetch Orders</button>
</form>

// ...existing code...
