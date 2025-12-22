# Multi-Currency Price Updater for WooCommerce

A comprehensive WordPress plugin that enables multi-currency price management for WooCommerce products with batch processing, real-time logging, and dry-run capabilities.

## Features

- **Currency Management**: Add, edit, and delete currencies with custom exchange rates
- **Product-Level Currency Selection**: Set base prices and currencies for both simple products and product variations
- **Batch Processing**: Update prices in batches of 10 to prevent timeouts
- **Dry-Run Mode**: Preview price changes before applying them
- **Real-Time Logging**: Monitor the update process with detailed logs
- **Product Exclusions**: Search and exclude specific products from bulk updates
- **Manual Updates**: Update individual product prices on-demand
- **Timeout Handling**: Automatic retry mechanism for failed batches
- **Security**: Nonce verification, capability checks, and data sanitization

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Installation

1. **Upload the Plugin**
   - Upload the plugin folder to `/wp-content/plugins/`
   - Or zip the folder and upload via WordPress admin (Plugins → Add New → Upload Plugin)

2. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Multi-Currency Price Updater for Woo"
   - Click "Activate"

3. **Verify WooCommerce**
   - Make sure WooCommerce is installed and active
   - The plugin will not work without WooCommerce

## Usage

### 1. Add Currencies

1. Navigate to **WooCommerce → Multi-Currency**
2. Fill in the currency details:
   - **Currency Code**: 3-letter code (e.g., USD, EUR, GBP)
   - **Currency Name**: Full name (e.g., United States Dollar)
   - **Exchange Rate**: Rate relative to your base currency
   - **Set as Default**: Optional checkbox for default currency
3. Click **Add Currency**

**Example:**
- Code: `EUR`
- Name: `Euro`
- Exchange Rate: `0.85` (if 1 USD = 0.85 EUR)

### 2. Set Product Base Prices

#### For Simple Products:
1. Edit any simple product
2. Scroll to **Product data → General**
3. Find the **Multi-Currency Price Settings** section
4. Enter the **Base Price**
5. Select the **Currency** from the dropdown
6. Save the product

#### For Variable Products:
1. Edit any variable product
2. Go to **Product data → Variations**
3. Expand each variation
4. In the **Multi-Currency Settings** section:
   - Enter the **Base Price**
   - Select the **Currency**
5. Save changes

### 3. Exclude Products (Optional)

1. Navigate to **WooCommerce → Price Exclusions**
2. Search for products by name or SKU
3. Click **Add to Exclusions** for products you want to exclude
4. Excluded products will be skipped during bulk updates

### 4. Bulk Update Prices

1. Navigate to **WooCommerce → Bulk Price Update**
2. Review the statistics showing products ready for update
3. **Choose Update Mode:**
   - **Dry Run Mode (Recommended First)**: Check the box to preview changes without modifying prices
   - **Live Update**: Uncheck the box to actually update prices
4. Click **Start Price Update**
5. Monitor the progress bar and real-time logs
6. Wait for completion

**Important Notes:**
- Always run a **Dry Run** first to verify the changes
- The system processes products in batches of 10
- Only products/variations with base price and currency set will be updated
- Excluded products are skipped automatically
- Sale prices are NOT affected by updates

### 5. Manual Product Update

You can update individual products from the product edit page:

1. Edit any product with base price and currency set
2. Find the **Update Price Now** section (for simple products in General tab)
3. Click **Update Regular Price from Base Price**
4. The price will be updated immediately

For variations, the button appears in each variation's settings.

## Price Calculation

The plugin calculates the regular price using this formula:

```
Regular Price = Base Price × Exchange Rate
```

**Example:**
- Base Price: 100 USD
- Exchange Rate: 1.2 (USD to EUR)
- Regular Price: 100 × 1.2 = 120

## How It Works

### Batch Processing
- Products/variations are processed in batches of 10
- Each batch has a 30-second timeout
- Failed batches are automatically retried
- Progress is tracked and displayed in real-time

### Update Process
1. System fetches only products/variations with base price and currency set
2. For each item:
   - Check if excluded → skip
   - Fetch currency exchange rate
   - Calculate new price
   - Update regular price (in live mode)
   - Log the result
3. Move to next batch
4. Repeat until all items processed

### Security
- All AJAX requests use nonce verification
- User capabilities are checked (`manage_woocommerce`, `edit_products`)
- All input is sanitized and validated
- SQL queries use prepared statements

## Database Tables

The plugin creates one custom table:

**`wp_gmc_currencies`**
- `id`: Primary key
- `currency_code`: 3-letter currency code (unique)
- `currency_name`: Full currency name
- `exchange_rate`: Exchange rate value
- `is_default`: Default currency flag
- `created_at`: Creation timestamp
- `updated_at`: Update timestamp

Product data is stored in WordPress postmeta:
- `_gmc_base_price`: Base price value
- `_gmc_currency`: Currency code

Exclusions are stored in `wp_options`:
- `gmc_excluded_products`: Array of excluded product IDs

## Troubleshooting

### Plugin won't activate
- Ensure WooCommerce is installed and active
- Check PHP version (7.4+ required)
- Check WordPress version (5.8+ required)

### Batch update stops or fails
- Check server timeout settings
- The plugin has automatic retry for timeouts
- Click "Stop Update" and try again
- Check error logs in the update log panel

### Prices not updating
- Verify base price and currency are set on products
- Check that currency exists in currency list
- Ensure product is not in exclusion list
- Verify exchange rate is greater than 0
- Check dry-run mode is disabled for actual updates

### Manual update button not showing
- Product must have both base price AND currency set
- Refresh the product edit page
- Check that the plugin is active

## Support

For issues or questions, please visit: https://alisahafi.ir

## License

This plugin is developed by Ali Sahafi.

## Changelog

### Version 1.0.0
- Initial release
- Multi-currency management
- Batch price updates with optimization
- Dry-run mode
- Product exclusions
- Real-time logging
- Manual product updates
- Timeout handling
- Variation support
