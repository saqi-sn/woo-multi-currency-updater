<?php
/**
 * AJAX Handler class - Handles all AJAX requests
 */

if (!defined('ABSPATH')) {
    exit;
}

class GMC_Ajax_Handler {

    public function __construct() {
        // Product search for exclusions
        add_action('wp_ajax_gmc_search_products', array($this, 'search_products'));

        // Add product to exclusions
        add_action('wp_ajax_gmc_add_exclusion', array($this, 'add_exclusion'));

        // Batch update
        add_action('wp_ajax_gmc_batch_update', array($this, 'batch_update'));

        // Manual single product update
        add_action('wp_ajax_gmc_manual_update', array($this, 'manual_update'));
    }

    /**
     * Search products for exclusion list
     */
    public function search_products() {
        check_ajax_referer('gmc_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'multi-currency-updater-for-woo')));
        }

        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        if (empty($search)) {
            wp_send_json_error(array('message' => __('Please enter a search term', 'multi-currency-updater-for-woo')));
        }

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 20,
            'post_status' => 'publish',
            's' => $search
        );

        $products = get_posts($args);
        $results = array();

        foreach ($products as $post) {
            $product = wc_get_product($post->ID);

            if (!$product) {
                continue;
            }

            $is_excluded = GMC_Exclusion_Manager::is_excluded($post->ID);

            $results[] = array(
                'id' => $post->ID,
                'name' => $product->get_name(),
                'sku' => $product->get_sku(),
                'type' => $product->get_type(),
                'price' => $product->get_price(),
                'price_html' => $product->get_price_html(),
                'is_excluded' => $is_excluded
            );
        }

        wp_send_json_success(array('products' => $results));
    }

    /**
     * Add product to exclusions
     */
    public function add_exclusion() {
        check_ajax_referer('gmc_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'multi-currency-updater-for-woo')));
        }

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

        if (empty($product_id)) {
            wp_send_json_error(array('message' => __('Invalid product ID', 'multi-currency-updater-for-woo')));
        }

        $result = GMC_Exclusion_Manager::add_exclusion($product_id);

        if ($result) {
            wp_send_json_success(array('message' => __('Product added to exclusions', 'multi-currency-updater-for-woo')));
        } else {
            wp_send_json_error(array('message' => __('Product already excluded or error occurred', 'multi-currency-updater-for-woo')));
        }
    }

    /**
     * Batch update products
     */
    public function batch_update() {
        check_ajax_referer('gmc_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'multi-currency-updater-for-woo')));
        }

        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $limit = 10; // Fixed batch size
        $dry_run = isset($_POST['dry_run']) && $_POST['dry_run'] === 'true';

        // Set longer timeout for this request
        set_time_limit(30);

        $result = GMC_Price_Updater::batch_update($offset, $limit, $dry_run);
        $total = GMC_Price_Updater::get_total_products_count();

        $result['total'] = $total;
        $result['dry_run'] = $dry_run;

        wp_send_json_success($result);
    }

    /**
     * Manual single product update
     */
    public function manual_update() {
        check_ajax_referer('gmc_nonce', 'nonce');

        if (!current_user_can('edit_products')) {
            wp_send_json_error(array('message' => __('Permission denied', 'multi-currency-updater-for-woo')));
        }

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

        if (empty($product_id)) {
            wp_send_json_error(array('message' => __('Invalid product ID', 'multi-currency-updater-for-woo')));
        }

        $result = GMC_Price_Updater::update_product_price($product_id, false);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
}

new GMC_Ajax_Handler();
