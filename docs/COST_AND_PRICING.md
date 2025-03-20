# Cost and Pricing in Printify Sync

## Price Structure

In the Printify Sync plugin, we handle different price elements:

### Retail Price
The price charged to the customer, coming from:
- `total_price` - The product's price to the end customer
- `total_shipping` - The shipping cost charged to the customer
- **Total Amount** = `total_price` + `total_shipping`

### Cost Price (Merchant Cost)
What you pay to Printify:
- `cost` - The base cost of the product to you
- `shipping_cost` - The shipping cost you pay to Printify
- **Total Merchant Cost** = (`cost` × `quantity`) + `shipping_cost`

### Profit Calculation
Your profit is calculated as:
- **Profit** = **Total Amount** - **Total Merchant Cost**

## Example

For an order with the following values:
```json
{
  "total_price": 2200,
  "total_shipping": 400,
  "line_items": [
    {
      "quantity": 1,
      "cost": 1050,
      "shipping_cost": 400,
      "metadata": {
        "price": 2200
      }
    }
  ]
}
```

The calculations would be:
- **Total Amount**: 2200 + 400 = 2600
- **Total Merchant Cost**: (1050 × 1) + 400 = 1450
- **Profit**: 2600 - 1450 = 1150

## Where This Information Appears

1. **Order Table**: The orders list displays both the total price (including shipping) and the cost price, along with the calculated profit.

2. **Order Details**: When viewing a WooCommerce order imported from Printify, the cost information is stored in order meta fields.

3. **Reports**: The Dashboard includes cost and profit analysis for all imported orders.

## Currency Formatting

All monetary values are formatted according to your store's currency settings. The plugin automatically handles the conversion from cents to dollars if needed.
