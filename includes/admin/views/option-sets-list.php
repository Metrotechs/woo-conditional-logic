<?php
/**
 * Option Sets List View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Option Sets', 'woo-conditional-logic'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=wcl-option-sets&action=new')); ?>" class="page-title-action">
        <?php esc_html_e('Add New', 'woo-conditional-logic'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (empty($option_sets)): ?>
        <div class="notice notice-info">
            <p>
                <strong><?php esc_html_e('Welcome to WooCommerce Conditional Logic!', 'woo-conditional-logic'); ?></strong><br>
                <?php esc_html_e('No option sets found. Click the "Add New" button above to create your first option set and start adding conditional logic to your products.', 'woo-conditional-logic'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Quick Start:', 'woo-conditional-logic'); ?></strong>
                <ol style="margin: 10px 0; padding-left: 20px;">
                    <li><?php esc_html_e('Click "Add New" above to create an option set', 'woo-conditional-logic'); ?></li>
                    <li><?php esc_html_e('Give it a name and add product options', 'woo-conditional-logic'); ?></li>
                    <li><?php esc_html_e('Add conditional rules (optional)', 'woo-conditional-logic'); ?></li>
                    <li><?php esc_html_e('Assign the option set to products', 'woo-conditional-logic'); ?></li>
                </ol>
            </p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-name column-primary">
                        <?php esc_html_e('Name', 'woo-conditional-logic'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php esc_html_e('Description', 'woo-conditional-logic'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php esc_html_e('Options', 'woo-conditional-logic'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php esc_html_e('Rules', 'woo-conditional-logic'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php esc_html_e('Status', 'woo-conditional-logic'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php esc_html_e('Created', 'woo-conditional-logic'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($option_sets as $set): ?>
                    <?php
                    $options_count = count(WCL_Options::get_instance()->get_options_by_set($set->id));
                    $rules_count = count(WCL_Rules::get_instance()->get_rules_by_set($set->id));
                    $edit_url = admin_url('admin.php?page=wcl-option-sets&action=edit&id=' . $set->id);
                    $duplicate_url = wp_nonce_url(admin_url('admin.php?page=wcl-option-sets&action=duplicate&id=' . $set->id), 'duplicate_option_set');
                    $delete_url = wp_nonce_url(admin_url('admin.php?page=wcl-option-sets&action=delete&id=' . $set->id), 'delete_option_set');
                    ?>
                    <tr>
                        <td class="column-name column-primary" data-colname="<?php esc_attr_e('Name', 'woo-conditional-logic'); ?>">
                            <strong>
                                <a href="<?php echo esc_url($edit_url); ?>" class="row-title">
                                    <?php echo esc_html($set->name); ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo esc_url($edit_url); ?>">
                                        <?php esc_html_e('Edit', 'woo-conditional-logic'); ?>
                                    </a> |
                                </span>
                                <span class="duplicate">
                                    <a href="<?php echo esc_url($duplicate_url); ?>">
                                        <?php esc_html_e('Duplicate', 'woo-conditional-logic'); ?>
                                    </a> |
                                </span>
                                <span class="trash">
                                    <a href="<?php echo esc_url($delete_url); ?>" class="submitdelete" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this option set?', 'woo-conditional-logic'); ?>')">
                                        <?php esc_html_e('Delete', 'woo-conditional-logic'); ?>
                                    </a>
                                </span>
                            </div>
                            <button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e('Show more details', 'woo-conditional-logic'); ?></span></button>
                        </td>
                        <td data-colname="<?php esc_attr_e('Description', 'woo-conditional-logic'); ?>">
                            <?php echo esc_html(wp_trim_words($set->description, 10)); ?>
                        </td>
                        <td data-colname="<?php esc_attr_e('Options', 'woo-conditional-logic'); ?>">
                            <?php echo esc_html($options_count); ?>
                        </td>
                        <td data-colname="<?php esc_attr_e('Rules', 'woo-conditional-logic'); ?>">
                            <?php echo esc_html($rules_count); ?>
                        </td>
                        <td data-colname="<?php esc_attr_e('Status', 'woo-conditional-logic'); ?>">
                            <span class="status-<?php echo esc_attr($set->status); ?>">
                                <?php echo esc_html(ucfirst($set->status)); ?>
                            </span>
                        </td>
                        <td data-colname="<?php esc_attr_e('Created', 'woo-conditional-logic'); ?>">
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($set->created_at))); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
.status-active {
    color: #008a00;
    font-weight: bold;
}
.status-inactive {
    color: #999;
}
</style>
