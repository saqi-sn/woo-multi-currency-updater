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
            <h2><?php echo $editing ? esc_html__('Edit Currency', 'multi-currency-updater-for-woo') : esc_html__('Add New Currency', 'multi-currency-updater-for-woo'); ?></h2>

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
                            <label for="currency_code"><?php esc_html_e('Currency Code', 'multi-currency-updater-for-woo'); ?> <span class="required">*</span></label>
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
                            <p class="description"><?php esc_html_e('Enter the 3-letter currency code (e.g., USD, EUR, GBP)', 'multi-currency-updater-for-woo'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="currency_name"><?php esc_html_e('Currency Name', 'multi-currency-updater-for-woo'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="currency_name"
                                   name="currency_name"
                                   class="regular-text"
                                   placeholder="United States Dollar"
                                   value="<?php echo $editing ? esc_attr($edit_currency->currency_name) : ''; ?>"
                                   required>
                            <p class="description"><?php esc_html_e('Enter the full currency name', 'multi-currency-updater-for-woo'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="exchange_rate"><?php esc_html_e('Exchange Rate', 'multi-currency-updater-for-woo'); ?> <span class="required">*</span></label>
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
                            <p class="description"><?php esc_html_e('Enter the exchange rate relative to your base currency', 'multi-currency-updater-for-woo'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="is_default"><?php esc_html_e('Set as Default', 'multi-currency-updater-for-woo'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       id="is_default"
                                       name="is_default"
                                       value="1"
                                       <?php echo ($editing && $edit_currency->is_default) ? 'checked' : ''; ?>>
                                <?php esc_html_e('Set this as the default currency', 'multi-currency-updater-for-woo'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Only one currency can be default at a time', 'multi-currency-updater-for-woo'); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <?php if ($editing): ?>
                        <input type="submit"
                               name="gmc_edit_currency"
                               class="button button-primary"
                               value="<?php esc_html_e('Update Currency', 'multi-currency-updater-for-woo'); ?>">
                        <a href="<?php echo admin_url('admin.php?page=gmc-currencies'); ?>" class="button"><?php esc_html_e('Cancel', 'multi-currency-updater-for-woo'); ?></a>
                    <?php else: ?>
                        <input type="submit"
                               name="gmc_add_currency"
                               class="button button-primary"
                               value="<?php esc_html_e('Add Currency', 'multi-currency-updater-for-woo'); ?>">
                    <?php endif; ?>
                </p>
            </form>
        </div>

        <div class="gmc-table-section">
            <h2><?php esc_html_e('Existing Currencies', 'multi-currency-updater-for-woo'); ?></h2>

            <?php if (empty($currencies)): ?>
                <p><?php esc_html_e('No currencies found. Add your first currency above.', 'multi-currency-updater-for-woo'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Code', 'multi-currency-updater-for-woo'); ?></th>
                            <th><?php esc_html_e('Name', 'multi-currency-updater-for-woo'); ?></th>
                            <th><?php esc_html_e('Exchange Rate', 'multi-currency-updater-for-woo'); ?></th>
                            <th><?php esc_html_e('Default', 'multi-currency-updater-for-woo'); ?></th>
                            <th><?php esc_html_e('Actions', 'multi-currency-updater-for-woo'); ?></th>
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
                                        <span class="gmc-badge gmc-badge-primary"><?php esc_html_e('Default', 'multi-currency-updater-for-woo'); ?></span>
                                    <?php else: ?>
                                        <span class="gmc-badge"><?php esc_html_e('No', 'multi-currency-updater-for-woo'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=gmc-currencies&action=edit&id=' . $currency->id); ?>" class="button button-small">
                                        <?php esc_html_e('Edit', 'multi-currency-updater-for-woo'); ?>
                                    </a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=gmc-currencies&action=delete&id=' . $currency->id), 'gmc_delete_currency_' . $currency->id); ?>"
                                       class="button button-small button-link-delete gmc-delete-currency">
                                        <?php esc_html_e('Delete', 'multi-currency-updater-for-woo'); ?>
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
