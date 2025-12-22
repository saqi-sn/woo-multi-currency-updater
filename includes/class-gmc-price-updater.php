<?php
/**
 * Price Updater class - Handles price calculations and updates
 */

if (!defined('ABSPATH')) {
    exit;
}

class GMC_Price_Updater {

    /**
     * Update single product price
     */
    public static function update_product_price($product_id, $dry_run = false) {
        $product = wc_get_product($product_id);

        if (!$product) {
            return array(
                'success' => false,
                'message' => __('Product not found', 'multi-currency-updater-for-woo'),
                'product_id' => $product_id
            );
        }

        // Check if excluded
        if (GMC_Exclusion_Manager::is_excluded($product_id)) {
            return array(
                'success' => false,
                'message' => __('Product is excluded from updates', 'multi-currency-updater-for-woo'),
                'excluded' => true,
                'product_name' => $product->get_name(),
                'product_id' => $product_id
            );
        }

        if ($product->is_type('variable')) {
            return self::update_variable_product($product, $dry_run);
        } else {
            return self::update_simple_product($product, $dry_run);
        }
    }

    /**
     * Update simple product
     */
    private static function update_simple_product($product, $dry_run = false) {
        $product_id = $product->get_id();
        $base_price = get_post_meta($product_id, '_gmc_base_price', true);
        $currency_code = get_post_meta($product_id, '_gmc_currency', true);

        if (empty($base_price) || empty($currency_code)) {
            return array(
                'success' => false,
                'message' => __('No base price or currency set', 'multi-currency-updater-for-woo'),
                'skipped' => true,
                'product_name' => $product->get_name(),
                'product_id' => $product_id
            );
        }

        $currency = GMC_Database::get_currency_by_code($currency_code);

        if (!$currency) {
            return array(
                'success' => false,
                /* translators: %s: Currency code */
                'message' => sprintf(__('Currency %s not found', 'multi-currency-updater-for-woo'), $currency_code),
                'product_name' => $product->get_name(),
                'product_id' => $product_id
            );
        }

        $old_price = $product->get_regular_price();
        $new_price = floatval($base_price) * floatval($currency->exchange_rate);
        $new_price = round($new_price, 2);

        if (!$dry_run) {
            $product->set_regular_price($new_price);
            $product->save();
        }

        return array(
            'success' => true,
            'product_id' => $product_id,
            'product_name' => $product->get_name(),
            'old_price' => $old_price,
            'new_price' => $new_price,
            'base_price' => $base_price,
            'currency' => $currency_code,
            'exchange_rate' => $currency->exchange_rate,
            'dry_run' => $dry_run
        );
    }

    /**
     * Update variable product (all variations)
     */
    private static function update_variable_product($product, $dry_run = false) {
        $results = array();
        $variation_ids = $product->get_children();

        if (empty($variation_ids)) {
            return array(
                'success' => false,
                'message' => __('No variations found', 'multi-currency-updater-for-woo'),
                'skipped' => true,
                'product_name' => $product->get_name()
            );
        }

        $has_updates = false;

        foreach ($variation_ids as $variation_id) {
            $variation = wc_get_product($variation_id);

            if (!$variation) {
                continue;
            }

            $base_price = get_post_meta($variation_id, '_gmc_base_price', true);
            $currency_code = get_post_meta($variation_id, '_gmc_currency', true);

            if (empty($base_price) || empty($currency_code)) {
                continue;
            }

            $currency = GMC_Database::get_currency_by_code($currency_code);

            if (!$currency) {
                continue;
            }

            $old_price = $variation->get_regular_price();
            $new_price = floatval($base_price) * floatval($currency->exchange_rate);
            $new_price = round($new_price, 2);

            if (!$dry_run) {
                $variation->set_regular_price($new_price);
                $variation->save();
            }

            $results[] = array(
                'variation_id' => $variation_id,
                'variation_name' => $variation->get_name(),
                'old_price' => $old_price,
                'new_price' => $new_price,
                'base_price' => $base_price,
                'currency' => $currency_code,
                'exchange_rate' => $currency->exchange_rate
            );

            $has_updates = true;
        }

        if (!$has_updates) {
            return array(
                'success' => false,
                'message' => __('No variations with base price set', 'multi-currency-updater-for-woo'),
                'skipped' => true,
                'product_name' => $product->get_name()
            );
        }

        return array(
            'success' => true,
            'product_id' => $product->get_id(),
            'product_name' => $product->get_name(),
            'is_variable' => true,
            'variations' => $results,
            'dry_run' => $dry_run
        );
    }

    /**
     * Get all products/variations that need updating
     */
    private static function get_all_items_to_update() {
        global $wpdb;

        $items = array();

        // Get all simple products with base price and currency
        $simple_products = $wpdb->get_col("
            SELECT DISTINCT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_gmc_base_price'
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_gmc_currency'
            WHERE p.post_type = 'product'
            AND p.post_status = 'publish'
            AND pm1.meta_value != ''
            AND pm2.meta_value != ''
            ORDER BY p.ID ASC
        ");

        foreach ($simple_products as $product_id) {
            $product = wc_get_product($product_id);
            // Only add if it's NOT a variable product (simple, external, grouped, etc.)
            if ($product && !$product->is_type('variable')) {
                $items[] = array(
                    'type' => 'product',
                    'id' => $product_id
                );
            }
        }

        // Get all variations with base price and currency
        $variations = $wpdb->get_col("
            SELECT DISTINCT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_gmc_base_price'
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_gmc_currency'
            WHERE p.post_type = 'product_variation'
            AND p.post_status = 'publish'
            AND pm1.meta_value != ''
            AND pm2.meta_value != ''
            ORDER BY p.post_parent ASC, p.ID ASC
        ");

        foreach ($variations as $variation_id) {
            $items[] = array(
                'type' => 'variation',
                'id' => $variation_id
            );
        }

        return $items;
    }

    /**
     * Get products to update (with pagination)
     */
    public static function get_products_to_update($offset = 0, $limit = 10) {
        static $all_items = null;

        // Cache all items on first call
        if ($all_items === null) {
            $all_items = self::get_all_items_to_update();
        }

        // Return paginated slice
        return array_slice($all_items, $offset, $limit);
    }

    /**
     * Get total count of products/variations to update
     */
    public static function get_total_products_count() {
        $all_items = self::get_all_items_to_update();
        return count($all_items);
    }

    /**
     * Update single variation
     */
    private static function update_single_variation($variation_id, $dry_run = false) {
        $variation = wc_get_product($variation_id);

        if (!$variation) {
            return array(
                'success' => false,
                'message' => __('Variation not found', 'multi-currency-updater-for-woo'),
                'product_id' => $variation_id
            );
        }

        $base_price = get_post_meta($variation_id, '_gmc_base_price', true);
        $currency_code = get_post_meta($variation_id, '_gmc_currency', true);

        if (empty($base_price) || empty($currency_code)) {
            return array(
                'success' => false,
                'message' => __('No base price or currency set', 'multi-currency-updater-for-woo'),
                'skipped' => true,
                'product_name' => $variation->get_name(),
                'product_id' => $variation_id
            );
        }

        $currency = GMC_Database::get_currency_by_code($currency_code);

        if (!$currency) {
            return array(
                'success' => false,
                /* translators: %s: Currency code */
                'message' => sprintf(__('Currency %s not found', 'multi-currency-updater-for-woo'), $currency_code),
                'product_name' => $variation->get_name(),
                'product_id' => $variation_id
            );
        }

        $old_price = $variation->get_regular_price();
        $new_price = floatval($base_price) * floatval($currency->exchange_rate);
        $new_price = round($new_price, 2);

        if (!$dry_run) {
            $variation->set_regular_price($new_price);
            $variation->save();
        }

        return array(
            'success' => true,
            'product_id' => $variation_id,
            'product_name' => $variation->get_name(),
            'old_price' => $old_price,
            'new_price' => $new_price,
            'base_price' => $base_price,
            'currency' => $currency_code,
            'exchange_rate' => $currency->exchange_rate,
            'dry_run' => $dry_run,
            'is_variation' => true
        );
    }

    /**
     * Batch update products
     */
    public static function batch_update($offset = 0, $limit = 10, $dry_run = false) {
        $items = self::get_products_to_update($offset, $limit);
        $results = array();

        foreach ($items as $item) {
            if ($item['type'] === 'variation') {
                $result = self::update_single_variation($item['id'], $dry_run);
            } else {
                $result = self::update_product_price($item['id'], $dry_run);
            }
            $results[] = $result;
        }

        return array(
            'offset' => $offset,
            'limit' => $limit,
            'processed' => count($items),
            'results' => $results,
            'has_more' => count($items) === $limit
        );
    }
}
