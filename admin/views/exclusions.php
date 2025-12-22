<?php
/**
 * Exclusions management page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get excluded products
$excluded_products = GMC_Exclusion_Manager::get_exclusions_with_details();
?>

<div class="wrap gmc-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors('gmc_messages'); ?>

    <div class="gmc-container">
        <div class="gmc-form-section">
            <h2><?php esc_html_e('Add Product to Exclusion List', 'multi-currency-updater-for-woo'); ?></h2>

            <p class="description">
                <?php esc_html_e('Search for products and add them to the exclusion list. Excluded products will not have their prices updated during bulk updates.', 'multi-currency-updater-for-woo'); ?>
            </p>

            <div class="gmc-product-search">
                <input type="text" id="gmc-product-search-input" class="regular-text" placeholder="<?php esc_html_e('Search for a product by name or SKU...', 'multi-currency-updater-for-woo'); ?>">
                <button type="button" id="gmc-search-products" class="button"><?php esc_html_e('Search', 'multi-currency-updater-for-woo'); ?></button>
            </div>

            <div id="gmc-search-results" class="gmc-search-results"></div>
        </div>

        <div class="gmc-table-section">
            <h2><?php esc_html_e('Excluded Products', 'multi-currency-updater-for-woo'); ?></h2>

            <?php if (empty($excluded_products)): ?>
                <p><?php esc_html_e('No products are currently excluded. Search and add products above.', 'multi-currency-updater-for-woo'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Product ID', 'multi-currency-updater-for-woo'); ?></th>
                            <th><?php esc_html_e('Product Name', 'multi-currency-updater-for-woo'); ?></th>
                            <th><?php esc_html_e('SKU', 'multi-currency-updater-for-woo'); ?></th>
                            <th><?php esc_html_e('Type', 'multi-currency-updater-for-woo'); ?></th>
                            <th><?php esc_html_e('Current Price', 'multi-currency-updater-for-woo'); ?></th>
                            <th><?php esc_html_e('Actions', 'multi-currency-updater-for-woo'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($excluded_products as $product): ?>
                            <tr>
                                <td><?php echo esc_html($product['id']); ?></td>
                                <td>
                                    <strong>
                                        <a href="<?php echo esc_url(get_edit_post_link($product['id'])); ?>" target="_blank">
                                            <?php echo esc_html($product['name']); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td><?php echo esc_html($product['sku']); ?></td>
                                <td><?php echo esc_html(ucfirst($product['type'])); ?></td>
                                <td><?php echo wc_price($product['price']); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=gmc-exclusions&action=remove&product_id=' . $product['id']), 'gmc_remove_exclusion_' . $product['id'])); ?>"
                                       class="button button-small button-link-delete gmc-remove-exclusion">
                                        <?php esc_html_e('Remove', 'multi-currency-updater-for-woo'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
