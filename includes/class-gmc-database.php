<?php
/**
 * Database operations class
 */

if (!defined('ABSPATH')) {
    exit;
}

class GMC_Database {

    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'gmc_currencies';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            currency_code varchar(10) NOT NULL,
            currency_name varchar(100) NOT NULL,
            exchange_rate decimal(20,6) NOT NULL DEFAULT 1.000000,
            is_default tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY currency_code (currency_code)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Update version
        update_option('gmc_db_version', GMC_VERSION);
    }

    /**
     * Get all currencies
     */
    public static function get_currencies() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gmc_currencies';
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY is_default DESC, currency_code ASC");
    }

    /**
     * Get currency by ID
     */
    public static function get_currency($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gmc_currencies';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    }

    /**
     * Get currency by code
     */
    public static function get_currency_by_code($code) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gmc_currencies';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE currency_code = %s", $code));
    }

    /**
     * Add currency
     */
    public static function add_currency($currency_code, $currency_name, $exchange_rate, $is_default = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gmc_currencies';

        // If this is set as default, remove default from others
        if ($is_default) {
            $wpdb->update($table_name, array('is_default' => 0), array('is_default' => 1));
        }

        return $wpdb->insert(
            $table_name,
            array(
                'currency_code' => sanitize_text_field($currency_code),
                'currency_name' => sanitize_text_field($currency_name),
                'exchange_rate' => floatval($exchange_rate),
                'is_default' => intval($is_default)
            ),
            array('%s', '%s', '%f', '%d')
        );
    }

    /**
     * Update currency
     */
    public static function update_currency($id, $currency_code, $currency_name, $exchange_rate, $is_default = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gmc_currencies';

        // If this is set as default, remove default from others
        if ($is_default) {
            $wpdb->update($table_name, array('is_default' => 0), array('is_default' => 1));
        }

        return $wpdb->update(
            $table_name,
            array(
                'currency_code' => sanitize_text_field($currency_code),
                'currency_name' => sanitize_text_field($currency_name),
                'exchange_rate' => floatval($exchange_rate),
                'is_default' => intval($is_default)
            ),
            array('id' => $id),
            array('%s', '%s', '%f', '%d'),
            array('%d')
        );
    }

    /**
     * Delete currency
     */
    public static function delete_currency($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gmc_currencies';
        return $wpdb->delete($table_name, array('id' => $id), array('%d'));
    }

    /**
     * Get currencies as options array
     */
    public static function get_currency_options() {
        $currencies = self::get_currencies();
        $options = array('' => __('Select Currency', 'multi-currency-updater-for-woo'));

        foreach ($currencies as $currency) {
            $options[$currency->currency_code] = $currency->currency_code . ' - ' . $currency->currency_name;
        }

        return $options;
    }
}
