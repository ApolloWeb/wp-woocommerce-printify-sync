# Price Handling in Printify Sync

## Price Format

This plugin handles prices from the Printify API, which provides them in different formats:

### Price Format Handling

1. Prices may come from the API in different formats:
   - As whole numbers that need to be divided by 100 (e.g., "1022" for £10.22)
   - As decimal values already in the correct format (e.g., "10.22" for £10.22)

2. Our formatting logic automatically detects which format is used and applies the appropriate transformation.

3. All prices are displayed with the appropriate currency symbol and two decimal places.

## Currency Formatting

The plugin includes a global `formatCurrency()` function that handles proper currency formatting:

```javascript
// Example usage
const formattedPrice = formatCurrency(1022); // Returns "£10.22" (converts from 1022 to 10.22)
const alreadyFormattedPrice = formatCurrency(10.22); // Returns "£10.22" (keeps as 10.22)
```

The function is available globally in all JavaScript files after wpwps-common.js is loaded.

## Configuration

Currency can be configured in the plugin settings page. The supported currencies are:
- GBP (£)
- USD ($)
- EUR (€)

## Technical Implementation

The price formatting logic is implemented in:
- `assets/js/wpwps-common.js` - Main currency formatting function
- `assets/js/wpwps-orders.js` - Order price display
- `assets/js/wpwps-dashboard.js` - Dashboard charts and data

Each implementation includes logic to detect whether values need to be divided by 100 based on whether they already have a decimal point.
