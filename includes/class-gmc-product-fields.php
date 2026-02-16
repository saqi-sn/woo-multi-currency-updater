<?php
/**
 * Product Fields class - Handles custom fields for products and variations
 */

if (!defined('ABSPATH')) {
    exit;
}

class GMC_Product_Fields {

    public function __construct() {
        // Simple products
        add_action('woocommerce_product_options_pricing', array($this, 'add_simple_product_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_simple_product_fields'));

        // Variable products - variations
        add_action('woocommerce_variation_options_pricing', array($this, 'add_variation_fields'), 10, 3);
        add_action('woocommerce_save_product_variation', array($this, 'save_variation_fields'), 10, 2);

        // Manual update button on product page
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_manual_update_button'));
        add_action('woocommerce_product_after_variable_attributes', array($this, 'add_variation_manual_update_button'), 10, 3);
    }

    /**
     * Add fields to simple products
     */
    public function add_simple_product_fields() {
        global $post;

        echo '<div class="options_group gmc-product-fields">';

        echo '<h3 style="padding: 10px 12px; margin: 0; border-top: 1px solid #eee;">' . esc_html__('Multi-Currency Price Settings', 'multi-currency-updater-for-woo') . '</h3>';

        // Base price field
        woocommerce_wp_text_input(array(
            'id' => '_gmc_base_price',
            'label' => esc_html__('Base Price', 'multi-currency-updater-for-woo'),
            'desc_tip' => true,
            'description' => esc_html__('Enter the base price in the selected currency. This will be used to calculate the regular price.', 'multi-currency-updater-for-woo'),
            'type' => 'number',
            'custom_attributes' => array(
                'step' => '0.01',
                'min' => '0'
            )
        ));

        // Currency selector
        woocommerce_wp_select(array(
            'id' => '_gmc_currency',
            'label' => esc_html__('Currency', 'multi-currency-updater-for-woo'),
            'desc_tip' => true,
            'description' => esc_html__('Select the currency for the base price.', 'multi-currency-updater-for-woo'),
            'options' => GMC_Database::get_currency_options()
        ));

        echo '</div>';
    }

    /**
     * Save simple product fields
     */
    public function save_simple_product_fields($post_id) {
        // Verify nonce - WooCommerce uses update_post_meta nonce
        if (!isset($_POST['woocommerce_meta_nonce']) || !wp_verify_nonce($_POST['woocommerce_meta_nonce'], 'woocommerce_save_data')) {
            return;
        }

        $base_price = isset($_POST['_gmc_base_price']) ? sanitize_text_field($_POST['_gmc_base_price']) : '';
        $currency = isset($_POST['_gmc_currency']) ? sanitize_text_field($_POST['_gmc_currency']) : '';

        update_post_meta($post_id, '_gmc_base_price', $base_price);
        update_post_meta($post_id, '_gmc_currency', $currency);
    }

    /**
     * Add fields to product variations
     */
    public function add_variation_fields($loop, $variation_data, $variation) {
        $variation_id = $variation->ID;

        echo '<div class="gmc-variation-fields" style="padding: 10px 12px; border-top: 1px solid #eee;">';
        echo '<h4 style="margin: 0 0 10px 0;">' . esc_html__('Multi-Currency Settings', 'multi-currency-updater-for-woo') . '</h4>';

        // Base price field
        woocommerce_wp_text_input(array(
            'id' => '_gmc_base_price_' . $loop,
            'name' => '_gmc_base_price[' . $loop . ']',
            'label' => esc_html__('Base Price', 'multi-currency-updater-for-woo'),
            'desc_tip' => true,
            'description' => esc_html__('Enter the base price in the selected currency.', 'multi-currency-updater-for-woo'),
            'type' => 'number',
            'custom_attributes' => array(
                'step' => '0.01',
                'min' => '0'
            ),
            'value' => get_post_meta($variation_id, '_gmc_base_price', true),
            'wrapper_class' => 'form-row form-row-first'
        ));

        // Currency selector
        woocommerce_wp_select(array(
            'id' => '_gmc_currency_' . $loop,
            'name' => '_gmc_currency[' . $loop . ']',
            'label' => esc_html__('Currency', 'multi-currency-updater-for-woo'),
            'desc_tip' => true,
            'description' => esc_html__('Select the currency for the base price.', 'multi-currency-updater-for-woo'),
            'options' => GMC_Database::get_currency_options(),
            'value' => get_post_meta($variation_id, '_gmc_currency', true),
            'wrapper_class' => 'form-row form-row-last'
        ));

        echo '</div>';
    }

    /**
     * Save variation fields
     */
    public function save_variation_fields($variation_id, $loop) {
        if (isset($_POST['_gmc_base_price'][$loop])) {
            $base_price = sanitize_text_field($_POST['_gmc_base_price'][$loop]);
            update_post_meta($variation_id, '_gmc_base_price', $base_price);
        }

        if (isset($_POST['_gmc_currency'][$loop])) {
            $currency = sanitize_text_field($_POST['_gmc_currency'][$loop]);
            update_post_meta($variation_id, '_gmc_currency', $currency);
        }
    }

    /**
     * Add manual update button for simple products
     */
    public function add_manual_update_button() {
        global $post;

        $product = wc_get_product($post->ID);
        if (!$product || $product->is_type('variable')) {
            return;
        }

        $base_price = get_post_meta($post->ID, '_gmc_base_price', true);
        $currency = get_post_meta($post->ID, '_gmc_currency', true);

        if (empty($base_price) || empty($currency)) {
            return;
        }

        ?>
        <div class="options_group gmc-manual-update">
            <p class="form-field">
                <label><?php esc_html_e('Update Price Now', 'multi-currency-updater-for-woo'); ?></label>
                <button type="button" class="button button-secondary gmc-manual-update-btn" data-product-id="<?php echo esc_attr($post->ID); ?>">
                    <?php esc_html_e('Update Regular Price from Base Price', 'multi-currency-updater-for-woo'); ?>
                </button>
                <span class="gmc-manual-update-result"></span>
            </p>
        </div>
        <?php
    }

    /**
     * Add manual update button for variations
     */
    public function add_variation_manual_update_button($loop, $variation_data, $variation) {
        $variation_id = $variation->ID;
        $base_price = get_post_meta($variation_id, '_gmc_base_price', true);
        $currency = get_post_meta($variation_id, '_gmc_currency', true);

        if (empty($base_price) || empty($currency)) {
            return;
        }

        ?>
        <div class="gmc-variation-manual-update" style="padding: 10px 12px;">
            <button type="button" class="button button-secondary gmc-manual-update-btn" data-product-id="<?php echo esc_attr($variation_id); ?>" data-is-variation="1">
                <?php esc_html_e('Update Price from Base Price', 'multi-currency-updater-for-woo'); ?>
            </button>
            <span class="gmc-manual-update-result"></span>
        </div>
        <?php
    }
}

new GMC_Product_Fields();
