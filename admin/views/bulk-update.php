<?php
/**
 * Bulk update page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get statistics
$total_products = 0;
$products_with_base_price = 0;
$excluded_count = count(GMC_Exclusion_Manager::get_exclusions());

$args = array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'fields' => 'ids'
);

$products = get_posts($args);
$total_products = count($products);

foreach ($products as $product_id) {
    $product = wc_get_product($product_id);

    if (!$product) {
        continue;
    }

    // Check if variable product
    if ($product->is_type('variable')) {
        // Get variation IDs directly
        $variation_ids = $product->get_children();
        foreach ($variation_ids as $variation_id) {
            $var_base_price = get_post_meta($variation_id, '_gmc_base_price', true);
            $var_currency = get_post_meta($variation_id, '_gmc_currency', true);

            if (!empty($var_base_price) && !empty($var_currency)) {
                $products_with_base_price++;
            }
        }
    } else {
        // Simple product
        $base_price = get_post_meta($product_id, '_gmc_base_price', true);
        $currency = get_post_meta($product_id, '_gmc_currency', true);

        if (!empty($base_price) && !empty($currency)) {
            $products_with_base_price++;
        }
    }
}

$currencies = GMC_Database::get_currencies();
?>

<div class="wrap gmc-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="gmc-container">
        <div class="gmc-stats-section">
            <h2><?php _e('Update Statistics', 'multi-currency-updater-for-woo'); ?></h2>

            <div class="gmc-stats-grid">
                <div class="gmc-stat-card">
                    <div class="gmc-stat-label"><?php _e('Total Products', 'multi-currency-updater-for-woo'); ?></div>
                    <div class="gmc-stat-value"><?php echo esc_html($total_products); ?></div>
                </div>
                <div class="gmc-stat-card">
                    <div class="gmc-stat-label"><?php _e('Products/Variations with Base Price', 'multi-currency-updater-for-woo'); ?></div>
                    <div class="gmc-stat-value"><?php echo esc_html($products_with_base_price); ?></div>
                </div>
                <div class="gmc-stat-card">
                    <div class="gmc-stat-label"><?php _e('Excluded Products', 'multi-currency-updater-for-woo'); ?></div>
                    <div class="gmc-stat-value"><?php echo esc_html($excluded_count); ?></div>
                </div>
                <div class="gmc-stat-card">
                    <div class="gmc-stat-label"><?php _e('Available Currencies', 'multi-currency-updater-for-woo'); ?></div>
                    <div class="gmc-stat-value"><?php echo esc_html(count($currencies)); ?></div>
                </div>
            </div>
        </div>

        <?php if (empty($currencies)): ?>
            <div class="notice notice-">
                <p>
                    <?php _e('No currencies found. Please add currencies before updating prices.', 'multi-currency-updater-for-woo'); ?>
                    <a href="<?php echo admin_url('admin.php?page=gmc-currencies'); ?>" class="button button-small">
                        <?php _e('Add Currency', 'multi-currency-updater-for-woo'); ?>
                    </a>
                </p>
            </div>
        <?php elseif ($products_with_base_price === 0): ?>
            <div class="notice notice-">
                <p><?php _e('No products or variations have base price and currency set. Please configure your products first.', 'multi-currency-updater-for-woo'); ?></p>
            </div>
        <?php else: ?>
            <div class="gmc-update-section">
                <h2><?php _e('Bulk Price Update', 'multi-currency-updater-for-woo'); ?></h2>

                <div class="gmc-update-options">
                    <label class="gmc-checkbox-label">
                        <input type="checkbox" id="gmc-dry-run" checked>
                        <strong><?php _e('Dry Run Mode (Preview Only)', 'multi-currency-updater-for-woo'); ?></strong>
                        <p class="description"><?php _e('When enabled, no prices will be changed. Use this to preview what will be updated.', 'multi-currency-updater-for-woo'); ?></p>
                    </label>
                </div>

                <div class="gmc-update-controls">
                    <button type="button" id="gmc-start-update" class="button button-primary button-large">
                        <?php _e('Start Price Update', 'multi-currency-updater-for-woo'); ?>
                    </button>
                    <button type="button" id="gmc-stop-update" class="button button-secondary button-large" style="display: none;">
                        <?php _e('Stop Update', 'multi-currency-updater-for-woo'); ?>
                    </button>
                </div>

                <div id="gmc-update-progress" class="gmc-update-progress" style="display: none;">
                    <div class="gmc-progress-bar-container">
                        <div class="gmc-progress-bar" style="width: 0%;">
                            <span class="gmc-progress-text">0%</span>
                        </div>
                    </div>
                    <div class="gmc-progress-info">
                        <span id="gmc-progress-current">0</span> / <span id="gmc-progress-total">0</span> <?php _e('processed', 'multi-currency-updater-for-woo'); ?>
                    </div>
                </div>

                <div id="gmc-update-log" class="gmc-update-log" style="display: none;">
                    <h3><?php _e('Update Log', 'multi-currency-updater-for-woo'); ?></h3>
                    <div id="gmc-log-content" class="gmc-log-content"></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="gmc-info-section">
            <h3><?php _e('How it works', 'multi-currency-updater-for-woo'); ?></h3>
            <ol>
                <li><?php _e('The system processes products in batches of 10 to prevent timeouts', 'multi-currency-updater-for-woo'); ?></li>
                <li><?php _e('Only products/variations with base price and currency set will be updated', 'multi-currency-updater-for-woo'); ?></li>
                <li><?php _e('Excluded products are skipped automatically', 'multi-currency-updater-for-woo'); ?></li>
                <li><?php _e('Regular price = Base Price × Exchange Rate', 'multi-currency-updater-for-woo'); ?></li>
                <li><?php _e('Sale prices are not affected by the update process', 'multi-currency-updater-for-woo'); ?></li>
                <li><?php _e('Use Dry Run mode first to preview changes before applying them', 'multi-currency-updater-for-woo'); ?></li>
            </ol>
        </div>
    </div>
</div>
