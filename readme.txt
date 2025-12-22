=== Multi-Currency Price Updater for Woo ===
Contributors: alisahafi
Tags: woocommerce, multi-currency, price-updater, currency-conversion, bulk-update
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive multi-currency product price updater for WooCommerce with batch processing and real-time logging.

== Description ==

Manage product prices across multiple currencies with ease. This plugin enables you to set base prices in any currency and automatically update WooCommerce regular prices based on exchange rates.

**Key Features:**

* **Multiple Currency Management** - Add unlimited currencies with custom exchange rates
* **Product & Variation Support** - Set base prices for both simple products and individual variations
* **Batch Processing** - Update prices in batches of 10 to prevent timeouts
* **Dry-Run Mode** - Preview all price changes before applying them
* **Real-Time Logging** - Monitor every update with detailed, color-coded logs
* **Product Exclusions** - Search and exclude specific products from bulk updates
* **Manual Updates** - Update individual product prices on-demand from the product edit page
* **Timeout Handling** - Automatic retry mechanism for failed batches
* **Complete Security** - Nonce verification, capability checks, and data sanitization throughout

**How It Works:**

1. Add your currencies with exchange rates
2. Set base prices and currencies on your products/variations
3. Run bulk updates (use dry-run first to preview)
4. Prices are calculated: Regular Price = Base Price × Exchange Rate

**Perfect For:**

* Stores selling internationally with different base currencies
* Managing prices across multiple currencies efficiently
* Testing price changes before applying them (dry-run mode)
* Products sourced from different countries with varying currencies

**Note:** Sale prices are never affected by updates. The plugin only manages regular prices.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install via WordPress admin
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure WooCommerce is installed and active
4. Navigate to WooCommerce → Multi-Currency to get started

== Frequently Asked Questions ==

= Does this plugin change sale prices? =

No. The plugin only updates regular prices. Sale prices remain untouched and must be managed manually through WooCommerce.

= What happens if I don't set a base price on a product? =

Products without a base price and currency will be skipped during bulk updates. They won't be affected in any way.

= Can I undo a price update? =

The plugin doesn't have an undo feature, so we strongly recommend:
1. Always running a dry-run first to preview changes
2. Creating a database backup before live updates

= How many products can be updated at once? =

The plugin processes products in batches of 10 to prevent server timeouts. It can handle any number of products, processing them automatically in sequence.

= Does this replace WooCommerce Multi-Currency plugins? =

No. This plugin manages base prices and calculates regular prices. It doesn't handle frontend currency switching or customer-facing currency selection. It's designed to work alongside currency switcher plugins.

= What if my update times out? =

The plugin has automatic retry mechanisms. If a batch times out, it will automatically retry and resume from where it stopped.

== Screenshots ==

1. Currency Management - Add and manage currencies with exchange rates
2. Bulk Update Page - Statistics and dry-run mode before updating
3. Product Fields - Base price and currency selector on product page
4. Real-Time Logging - Monitor updates with detailed logs
5. Variation Support - Individual base prices for each variation

== Changelog ==

= 1.0.0 =
* Initial release
* Multi-currency management system
* Batch price updates with optimization
* Dry-run mode for safe testing
* Product exclusion management
* Real-time update logging
* Manual product update buttons
* Automatic timeout handling
* Full variation support
* Complete security implementation

== Upgrade Notice ==

= 1.0.0 =
Initial release of Multi-Currency Price Updater for Woo.

== Additional Information ==

**Support:** For issues or questions, visit [https://alisahafi.ir](https://alisahafi.ir)

**Requirements:**
* WordPress 5.8 or higher
* WooCommerce 5.0 or higher
* PHP 7.4 or higher

**Database:** The plugin creates one custom table for currencies. Product data is stored in standard WordPress postmeta tables.
