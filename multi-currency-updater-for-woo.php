<?php
/**
 * Plugin Name: Multi-Currency Price Updater for Woo
 * Plugin URI: https://alisahafi.ir
 * Description: A comprehensive multi-currency product price updater for WooCommerce with batch processing and real-time logging.
 * Version: 1.0.0
 * Author: Ali Sahafi
 * Author URI: https://alisahafi.ir
 * Text Domain: multi-currency-updater-for-woo
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('GMC_VERSION', '1.0.0');
define('GMC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GMC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GMC_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class Multi_Currency_Woo {

    /**
     * The single instance of the class
     */
    private static $instance = null;

    /**
     * Database table name for currencies
     */
    public static $currencies_table;

    /**
     * Get the single instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        self::$currencies_table = $wpdb->prefix . 'gmc_currencies';

        // Check if WooCommerce is active
        add_action('plugins_loaded', array($this, 'check_woocommerce'));

        // Initialize plugin
        add_action('init', array($this, 'init'));

        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'), 99);

        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Check if WooCommerce is active
     */
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            deactivate_plugins(GMC_PLUGIN_BASENAME);
            return;
        }
    }

    /**
     * Display notice if WooCommerce is not active
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Multi-Currency Price Updater for Woo requires WooCommerce to be installed and active.', 'multi-currency-updater-for-woo'); ?></p>
        </div>
        <?php
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Load plugin text domain
        load_plugin_textdomain('multi-currency-updater-for-woo', false, dirname(GMC_PLUGIN_BASENAME) . '/languages');

        // Include required files
        $this->include_files();
    }

    /**
     * Include required files
     */
    private function include_files() {
        require_once GMC_PLUGIN_DIR . 'includes/class-gmc-database.php';
        require_once GMC_PLUGIN_DIR . 'includes/class-gmc-currency-manager.php';
        require_once GMC_PLUGIN_DIR . 'includes/class-gmc-product-fields.php';
        require_once GMC_PLUGIN_DIR . 'includes/class-gmc-exclusion-manager.php';
        require_once GMC_PLUGIN_DIR . 'includes/class-gmc-price-updater.php';
        require_once GMC_PLUGIN_DIR . 'includes/class-gmc-ajax-handler.php';
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        add_submenu_page(
            'woocommerce',
            __('Multi-Currency', 'multi-currency-updater-for-woo'),
            __('Multi-Currency', 'multi-currency-updater-for-woo'),
            'manage_woocommerce',
            'gmc-currencies',
            array($this, 'render_currencies_page')
        );

        // Exclusions submenu
        add_submenu_page(
            'woocommerce',
            __('Price Update Exclusions', 'multi-currency-updater-for-woo'),
            __('Price Exclusions', 'multi-currency-updater-for-woo'),
            'manage_woocommerce',
            'gmc-exclusions',
            array($this, 'render_exclusions_page')
        );

        // Bulk update submenu
        add_submenu_page(
            'woocommerce',
            __('Bulk Price Update', 'multi-currency-updater-for-woo'),
            __('Bulk Price Update', 'multi-currency-updater-for-woo'),
            'manage_woocommerce',
            'gmc-bulk-update',
            array($this, 'render_bulk_update_page')
        );
    }

    /**
     * Render currencies management page
     */
    public function render_currencies_page() {
        require_once GMC_PLUGIN_DIR . 'admin/views/currencies.php';
    }

    /**
     * Render exclusions page
     */
    public function render_exclusions_page() {
        require_once GMC_PLUGIN_DIR . 'admin/views/exclusions.php';
    }

    /**
     * Render bulk update page
     */
    public function render_bulk_update_page() {
        require_once GMC_PLUGIN_DIR . 'admin/views/bulk-update.php';
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages and product edit pages
        if (strpos($hook, 'gmc-') !== false || $hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_style('gmc-admin-styles', GMC_PLUGIN_URL . 'assets/css/admin.css', array(), GMC_VERSION);
            wp_enqueue_script('gmc-admin-scripts', GMC_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), GMC_VERSION, true);

            // Localize script
            wp_localize_script('gmc-admin-scripts', 'gmcData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('gmc_nonce'),
                'strings' => array(
                    'confirmDelete' => __('Are you sure you want to delete this currency?', 'multi-currency-updater-for-woo'),
                    'confirmRemove' => __('Are you sure you want to remove this product from exclusions?', 'multi-currency-updater-for-woo'),
                    'processing' => __('Processing...', 'multi-currency-updater-for-woo'),
                    'completed' => __('Update completed!', 'multi-currency-updater-for-woo'),
                    'error' => __('An error occurred. Please try again.', 'multi-currency-updater-for-woo'),
                )
            ));
        }
    }
}

/**
 * Plugin activation
 */
function gmc_activate() {
    require_once GMC_PLUGIN_DIR . 'includes/class-gmc-database.php';
    GMC_Database::create_tables();
}
register_activation_hook(__FILE__, 'gmc_activate');

/**
 * Plugin deactivation
 */
function gmc_deactivate() {
    // Cleanup if needed
}
register_deactivation_hook(__FILE__, 'gmc_deactivate');

/**
 * Initialize the plugin
 */
function GMC() {
    return Multi_Currency_Woo::instance();
}

// Start the plugin
GMC();
