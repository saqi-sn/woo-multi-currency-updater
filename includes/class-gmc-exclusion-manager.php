<?php
/**
 * Exclusion Manager class
 */

if (!defined('ABSPATH')) {
    exit;
}

class GMC_Exclusion_Manager {

    const OPTION_NAME = 'gmc_excluded_products';

    public function __construct() {
        add_action('admin_init', array($this, 'handle_exclusion_actions'));
    }

    /**
     * Handle exclusion actions
     */
    public function handle_exclusion_actions() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        // Handle remove exclusion
        if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['product_id']) && check_admin_referer('gmc_remove_exclusion_' . $_GET['product_id'], '_wpnonce')) {
            $this->remove_exclusion();
        }
    }

    /**
     * Get excluded products
     */
    public static function get_exclusions() {
        $exclusions = get_option(self::OPTION_NAME, array());
        return is_array($exclusions) ? $exclusions : array();
    }

    /**
     * Add product to exclusions
     */
    public static function add_exclusion($product_id) {
        $exclusions = self::get_exclusions();

        if (!in_array($product_id, $exclusions)) {
            $exclusions[] = intval($product_id);
            update_option(self::OPTION_NAME, $exclusions);
            return true;
        }

        return false;
    }

    /**
     * Remove product from exclusions
     */
    private function remove_exclusion() {
        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

        if (empty($product_id)) {
            add_settings_error('gmc_messages', 'gmc_message', __('Invalid product ID.', 'multi-currency-updater-for-woo'), 'error');
            return;
        }

        $exclusions = self::get_exclusions();
        $key = array_search($product_id, $exclusions);

        if ($key !== false) {
            unset($exclusions[$key]);
            update_option(self::OPTION_NAME, array_values($exclusions));
            add_settings_error('gmc_messages', 'gmc_message', __('Product removed from exclusions.', 'multi-currency-updater-for-woo'), 'success');
        } else {
            add_settings_error('gmc_messages', 'gmc_message', __('Product not found in exclusions.', 'multi-currency-updater-for-woo'), 'error');
        }

        wp_redirect(admin_url('admin.php?page=gmc-exclusions'));
        exit;
    }

    /**
     * Check if product is excluded
     */
    public static function is_excluded($product_id) {
        $exclusions = self::get_exclusions();
        return in_array($product_id, $exclusions);
    }

    /**
     * Get excluded products with details
     */
    public static function get_exclusions_with_details() {
        $exclusions = self::get_exclusions();
        $products = array();

        foreach ($exclusions as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $products[] = array(
                    'id' => $product_id,
                    'name' => $product->get_name(),
                    'sku' => $product->get_sku(),
                    'type' => $product->get_type(),
                    'price' => $product->get_price()
                );
            }
        }

        return $products;
    }
}

new GMC_Exclusion_Manager();
