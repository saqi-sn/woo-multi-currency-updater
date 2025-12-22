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
            <h2><?php _e('Add Product to Exclusion List', 'woo-multi-currency-updater'); ?></h2>

            <p class="description">
                <?php _e('Search for products and add them to the exclusion list. Excluded products will not have their prices updated during bulk updates.', 'woo-multi-currency-updater'); ?>
            </p>

            <div class="gmc-product-search">
                <input type="text" id="gmc-product-search-input" class="regular-text" placeholder="<?php _e('Search for a product by name or SKU...', 'woo-multi-currency-updater'); ?>">
                <button type="button" id="gmc-search-products" class="button"><?php _e('Search', 'woo-multi-currency-updater'); ?></button>
            </div>

            <div id="gmc-search-results" class="gmc-search-results"></div>
        </div>

        <div class="gmc-table-section">
            <h2><?php _e('Excluded Products', 'woo-multi-currency-updater'); ?></h2>

            <?php if (empty($excluded_products)): ?>
                <p><?php _e('No products are currently excluded. Search and add products above.', 'woo-multi-currency-updater'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Product ID', 'woo-multi-currency-updater'); ?></th>
                            <th><?php _e('Product Name', 'woo-multi-currency-updater'); ?></th>
                            <th><?php _e('SKU', 'woo-multi-currency-updater'); ?></th>
                            <th><?php _e('Type', 'woo-multi-currency-updater'); ?></th>
                            <th><?php _e('Current Price', 'woo-multi-currency-updater'); ?></th>
                            <th><?php _e('Actions', 'woo-multi-currency-updater'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($excluded_products as $product): ?>
                            <tr>
                                <td><?php echo esc_html($product['id']); ?></td>
                                <td>
                                    <strong>
                                        <a href="<?php echo get_edit_post_link($product['id']); ?>" target="_blank">
                                            <?php echo esc_html($product['name']); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td><?php echo esc_html($product['sku']); ?></td>
                                <td><?php echo esc_html(ucfirst($product['type'])); ?></td>
                                <td><?php echo wc_price($product['price']); ?></td>
                                <td>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=gmc-exclusions&action=remove&product_id=' . $product['id']), 'gmc_remove_exclusion_' . $product['id']); ?>"
                                       class="button button-small button-link-delete gmc-remove-exclusion">
                                        <?php _e('Remove', 'woo-multi-currency-updater'); ?>
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
