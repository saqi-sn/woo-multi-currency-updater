<?php
/**
 * Currency Manager class
 */

if (!defined('ABSPATH')) {
    exit;
}

class GMC_Currency_Manager {

    public function __construct() {
        add_action('admin_init', array($this, 'handle_currency_actions'));
    }

    /**
     * Handle currency add/edit/delete actions
     */
    public function handle_currency_actions() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        // Handle add currency
        if (isset($_POST['gmc_add_currency']) && check_admin_referer('gmc_add_currency', 'gmc_currency_nonce')) {
            $this->add_currency();
        }

        // Handle edit currency
        if (isset($_POST['gmc_edit_currency']) && check_admin_referer('gmc_edit_currency', 'gmc_currency_nonce')) {
            $this->edit_currency();
        }

        // Handle delete currency
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && check_admin_referer('gmc_delete_currency_' . $_GET['id'], '_wpnonce')) {
            $this->delete_currency();
        }
    }

    /**
     * Add new currency
     */
    private function add_currency() {
        $currency_code = isset($_POST['currency_code']) ? strtoupper(sanitize_text_field($_POST['currency_code'])) : '';
        $currency_name = isset($_POST['currency_name']) ? sanitize_text_field($_POST['currency_name']) : '';
        $exchange_rate = isset($_POST['exchange_rate']) ? floatval($_POST['exchange_rate']) : 1;
        $is_default = isset($_POST['is_default']) ? 1 : 0;

        if (empty($currency_code) || empty($currency_name) || $exchange_rate <= 0) {
            add_settings_error('gmc_messages', 'gmc_message', __('Please fill all required fields correctly.', 'multi-currency-updater-for-woo'), 'error');
            return;
        }

        $result = GMC_Database::add_currency($currency_code, $currency_name, $exchange_rate, $is_default);

        if ($result) {
            add_settings_error('gmc_messages', 'gmc_message', __('Currency added successfully.', 'multi-currency-updater-for-woo'), 'success');
        } else {
            add_settings_error('gmc_messages', 'gmc_message', __('Failed to add currency. Currency code might already exist.', 'multi-currency-updater-for-woo'), 'error');
        }
    }

    /**
     * Edit currency
     */
    private function edit_currency() {
        $id = isset($_POST['currency_id']) ? intval($_POST['currency_id']) : 0;
        $currency_code = isset($_POST['currency_code']) ? strtoupper(sanitize_text_field($_POST['currency_code'])) : '';
        $currency_name = isset($_POST['currency_name']) ? sanitize_text_field($_POST['currency_name']) : '';
        $exchange_rate = isset($_POST['exchange_rate']) ? floatval($_POST['exchange_rate']) : 1;
        $is_default = isset($_POST['is_default']) ? 1 : 0;

        if (empty($id) || empty($currency_code) || empty($currency_name) || $exchange_rate <= 0) {
            add_settings_error('gmc_messages', 'gmc_message', __('Please fill all required fields correctly.', 'multi-currency-updater-for-woo'), 'error');
            return;
        }

        $result = GMC_Database::update_currency($id, $currency_code, $currency_name, $exchange_rate, $is_default);

        if ($result !== false) {
            add_settings_error('gmc_messages', 'gmc_message', __('Currency updated successfully.', 'multi-currency-updater-for-woo'), 'success');
        } else {
            add_settings_error('gmc_messages', 'gmc_message', __('Failed to update currency.', 'multi-currency-updater-for-woo'), 'error');
        }
    }

    /**
     * Delete currency
     */
    private function delete_currency() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if (empty($id)) {
            add_settings_error('gmc_messages', 'gmc_message', __('Invalid currency ID.', 'multi-currency-updater-for-woo'), 'error');
            return;
        }

        $result = GMC_Database::delete_currency($id);

        if ($result) {
            add_settings_error('gmc_messages', 'gmc_message', __('Currency deleted successfully.', 'multi-currency-updater-for-woo'), 'success');
        } else {
            add_settings_error('gmc_messages', 'gmc_message', __('Failed to delete currency.', 'multi-currency-updater-for-woo'), 'error');
        }

        wp_redirect(admin_url('admin.php?page=gmc-currencies'));
        exit;
    }
}

new GMC_Currency_Manager();
