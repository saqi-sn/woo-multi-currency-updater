<?php
/**
 * Currencies management page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get currencies
$currencies = GMC_Database::get_currencies();

// Check if we're editing
$editing = false;
$edit_currency = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_currency = GMC_Database::get_currency(intval($_GET['id']));
    $editing = $edit_currency ? true : false;
}
?>

<div class="wrap gmc-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors('gmc_messages'); ?>

    <div class="gmc-container">
        <div class="gmc-form-section">
            <h2><?php echo $editing ? __('Edit Currency', 'woo-multi-currency-updater') : __('Add New Currency', 'woo-multi-currency-updater'); ?></h2>

            <form method="post" action="">
                <?php
                if ($editing) {
                    wp_nonce_field('gmc_edit_currency', 'gmc_currency_nonce');
                } else {
                    wp_nonce_field('gmc_add_currency', 'gmc_currency_nonce');
                }
                ?>

                <?php if ($editing): ?>
                    <input type="hidden" name="currency_id" value="<?php echo esc_attr($edit_currency->id); ?>">
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="currency_code"><?php _e('Currency Code', 'woo-multi-currency-updater'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="currency_code"
                                   name="currency_code"
                                   class="regular-text"
                                   placeholder="USD"
                                   value="<?php echo $editing ? esc_attr($edit_currency->currency_code) : ''; ?>"
                                   required
                                   maxlength="10">
                            <p class="description"><?php _e('Enter the 3-letter currency code (e.g., USD, EUR, GBP)', 'woo-multi-currency-updater'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="currency_name"><?php _e('Currency Name', 'woo-multi-currency-updater'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="currency_name"
                                   name="currency_name"
                                   class="regular-text"
                                   placeholder="United States Dollar"
                                   value="<?php echo $editing ? esc_attr($edit_currency->currency_name) : ''; ?>"
                                   required>
                            <p class="description"><?php _e('Enter the full currency name', 'woo-multi-currency-updater'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="exchange_rate"><?php _e('Exchange Rate', 'woo-multi-currency-updater'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="number"
                                   id="exchange_rate"
                                   name="exchange_rate"
                                   class="regular-text"
                                   placeholder="1.0"
                                   value="<?php echo $editing ? esc_attr($edit_currency->exchange_rate) : '1'; ?>"
                                   step="0.000001"
                                   min="0.000001"
                                   required>
                            <p class="description"><?php _e('Enter the exchange rate relative to your base currency', 'woo-multi-currency-updater'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="is_default"><?php _e('Set as Default', 'woo-multi-currency-updater'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       id="is_default"
                                       name="is_default"
                                       value="1"
                                       <?php echo ($editing && $edit_currency->is_default) ? 'checked' : ''; ?>>
                                <?php _e('Set this as the default currency', 'woo-multi-currency-updater'); ?>
                            </label>
                            <p class="description"><?php _e('Only one currency can be default at a time', 'woo-multi-currency-updater'); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <?php if ($editing): ?>
                        <input type="submit"
                               name="gmc_edit_currency"
                               class="button button-primary"
                               value="<?php _e('Update Currency', 'woo-multi-currency-updater'); ?>">
                        <a href="<?php echo admin_url('admin.php?page=gmc-currencies'); ?>" class="button"><?php _e('Cancel', 'woo-multi-currency-updater'); ?></a>
                    <?php else: ?>
                        <input type="submit"
                               name="gmc_add_currency"
                               class="button button-primary"
                               value="<?php _e('Add Currency', 'woo-multi-currency-updater'); ?>">
                    <?php endif; ?>
                </p>
            </form>
        </div>

        <div class="gmc-table-section">
            <h2><?php _e('Existing Currencies', 'woo-multi-currency-updater'); ?></h2>

            <?php if (empty($currencies)): ?>
                <p><?php _e('No currencies found. Add your first currency above.', 'woo-multi-currency-updater'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Code', 'woo-multi-currency-updater'); ?></th>
                            <th><?php _e('Name', 'woo-multi-currency-updater'); ?></th>
                            <th><?php _e('Exchange Rate', 'woo-multi-currency-updater'); ?></th>
                            <th><?php _e('Default', 'woo-multi-currency-updater'); ?></th>
                            <th><?php _e('Actions', 'woo-multi-currency-updater'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($currencies as $currency): ?>
                            <tr>
                                <td><strong><?php echo esc_html($currency->currency_code); ?></strong></td>
                                <td><?php echo esc_html($currency->currency_name); ?></td>
                                <td><?php echo esc_html(number_format($currency->exchange_rate, 6)); ?></td>
                                <td>
                                    <?php if ($currency->is_default): ?>
                                        <span class="gmc-badge gmc-badge-primary"><?php _e('Default', 'woo-multi-currency-updater'); ?></span>
                                    <?php else: ?>
                                        <span class="gmc-badge"><?php _e('No', 'woo-multi-currency-updater'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=gmc-currencies&action=edit&id=' . $currency->id); ?>" class="button button-small">
                                        <?php _e('Edit', 'woo-multi-currency-updater'); ?>
                                    </a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=gmc-currencies&action=delete&id=' . $currency->id), 'gmc_delete_currency_' . $currency->id); ?>"
                                       class="button button-small button-link-delete gmc-delete-currency">
                                        <?php _e('Delete', 'woo-multi-currency-updater'); ?>
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
