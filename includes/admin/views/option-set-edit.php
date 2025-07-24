<?php
/**
 * Option Set Edit View
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_new = empty($option_set);
$page_title = $is_new ? __('Add New Option Set', 'woo-conditional-logic') : __('Edit Option Set', 'woo-conditional-logic');
?>

<div class="wrap wcl-admin-page">
    <h1><?php echo esc_html($page_title); ?></h1>
    
    <form id="wcl-option-set-form" method="post">
        <?php wp_nonce_field('wcl_save_option_set', 'wcl_nonce'); ?>
        <input type="hidden" id="wcl-option-set-id" name="option_set_id" value="<?php echo $is_new ? 0 : esc_attr($option_set->id); ?>">
        
        <table class="wcl-form-table">
            <tr>
                <th><?php esc_html_e('Option Set Name', 'woo-conditional-logic'); ?></th>
                <td>
                    <input type="text" name="name" value="<?php echo esc_attr($option_set->name ?? ''); ?>" class="regular-text" required>
                    <p class="description"><?php esc_html_e('Enter a name for this option set.', 'woo-conditional-logic'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Description', 'woo-conditional-logic'); ?></th>
                <td>
                    <textarea name="description" rows="3" class="large-text"><?php echo esc_textarea($option_set->description ?? ''); ?></textarea>
                    <p class="description"><?php esc_html_e('Optional description for this option set.', 'woo-conditional-logic'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Status', 'woo-conditional-logic'); ?></th>
                <td>
                    <select name="status">
                        <option value="active" <?php selected($option_set->status ?? 'active', 'active'); ?>><?php esc_html_e('Active', 'woo-conditional-logic'); ?></option>
                        <option value="inactive" <?php selected($option_set->status ?? 'active', 'inactive'); ?>><?php esc_html_e('Inactive', 'woo-conditional-logic'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="button" class="button-primary wcl-save-option-set"><?php esc_html_e('Save Option Set', 'woo-conditional-logic'); ?></button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wcl-option-sets')); ?>" class="button"><?php esc_html_e('Cancel', 'woo-conditional-logic'); ?></a>
        </p>
    </form>
    
    <?php if (!$is_new): ?>
        <!-- Options Section -->
        <div class="wcl-section">
            <h2 class="wcl-section-header"><?php esc_html_e('Options', 'woo-conditional-logic'); ?></h2>
            
            <div class="wcl-add-new-section">
                <button type="button" class="button wcl-add-option"><?php esc_html_e('Add New Option', 'woo-conditional-logic'); ?></button>
            </div>
            
            <div class="wcl-options-list">
                <?php foreach ($options as $option): ?>
                    <?php $values = WCL_Options::get_instance()->get_option_values($option->id); ?>
                    <div class="wcl-option-item" data-option-id="<?php echo esc_attr($option->id); ?>">
                        <div class="wcl-option-header">
                            <div class="wcl-drag-handle">⋮⋮</div>
                            <div class="wcl-option-title"><?php echo esc_html($option->name); ?></div>
                            <div class="wcl-option-actions">
                                <button type="button" class="wcl-btn wcl-save-option"><?php esc_html_e('Save', 'woo-conditional-logic'); ?></button>
                                <button type="button" class="wcl-btn wcl-btn-danger wcl-delete-option"><?php esc_html_e('Delete', 'woo-conditional-logic'); ?></button>
                                <button type="button" class="wcl-collapse-toggle"><?php esc_html_e('Collapse', 'woo-conditional-logic'); ?></button>
                            </div>
                        </div>
                        <div class="wcl-collapsible-content">
                            <div class="wcl-form-row">
                                <div class="wcl-form-field wcl-form-field-medium">
                                    <label><?php esc_html_e('Option Name', 'woo-conditional-logic'); ?></label>
                                    <input type="text" class="wcl-option-name" value="<?php echo esc_attr($option->name); ?>">
                                </div>
                                <div class="wcl-form-field wcl-form-field-medium">
                                    <label><?php esc_html_e('Option Type', 'woo-conditional-logic'); ?></label>
                                    <select class="wcl-option-type-select">
                                        <?php foreach ($option_types as $type_key => $type_data): ?>
                                            <option value="<?php echo esc_attr($type_key); ?>" <?php selected($option->type, $type_key); ?>>
                                                <?php echo esc_html($type_data['label']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="wcl-form-field wcl-form-field-small">
                                    <label><?php esc_html_e('Required', 'woo-conditional-logic'); ?></label>
                                    <select class="wcl-option-required">
                                        <option value="0" <?php selected($option->required, 0); ?>><?php esc_html_e('No', 'woo-conditional-logic'); ?></option>
                                        <option value="1" <?php selected($option->required, 1); ?>><?php esc_html_e('Yes', 'woo-conditional-logic'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="wcl-form-row">
                                <div class="wcl-form-field">
                                    <label><?php esc_html_e('Description', 'woo-conditional-logic'); ?></label>
                                    <textarea class="wcl-option-description" rows="2"><?php echo esc_textarea($option->description); ?></textarea>
                                </div>
                            </div>
                            
                            <?php if (in_array($option->type, array('checkbox', 'radio', 'dropdown', 'swatch', 'multi_swatch', 'button'))): ?>
                                <div class="wcl-option-values">
                                    <h4><?php esc_html_e('Option Values', 'woo-conditional-logic'); ?></h4>
                                    <button type="button" class="wcl-btn wcl-add-option-value"><?php esc_html_e('Add Value', 'woo-conditional-logic'); ?></button>
                                    
                                    <div class="wcl-values-list">
                                        <?php foreach ($values as $value): ?>
                                            <div class="wcl-value-item" data-value-id="<?php echo esc_attr($value->id); ?>">
                                                <div class="wcl-value-drag-handle">⋮⋮</div>
                                                <div class="wcl-value-fields">
                                                    <div class="wcl-form-field">
                                                        <input type="text" class="wcl-value-label" value="<?php echo esc_attr($value->label); ?>" placeholder="<?php esc_attr_e('Label', 'woo-conditional-logic'); ?>">
                                                    </div>
                                                    <div class="wcl-form-field">
                                                        <input type="text" class="wcl-value-value" value="<?php echo esc_attr($value->value); ?>" placeholder="<?php esc_attr_e('Value', 'woo-conditional-logic'); ?>">
                                                    </div>
                                                    <div class="wcl-form-field wcl-form-field-small">
                                                        <input type="number" class="wcl-value-price" step="0.01" value="<?php echo esc_attr($value->price_modifier); ?>" placeholder="<?php esc_attr_e('Price', 'woo-conditional-logic'); ?>">
                                                    </div>
                                                    <?php if (in_array($option->type, array('swatch', 'multi_swatch'))): ?>
                                                        <div class="wcl-form-field">
                                                            <input type="text" class="wcl-color-picker wcl-value-color" value="<?php echo esc_attr($value->color_hex); ?>">
                                                        </div>
                                                        <div class="wcl-form-field">
                                                            <button type="button" class="wcl-btn wcl-upload-image"><?php esc_html_e('Image', 'woo-conditional-logic'); ?></button>
                                                            <div class="wcl-image-preview">
                                                                <?php if (!empty($value->image_url)): ?>
                                                                    <img src="<?php echo esc_url($value->image_url); ?>" alt="">
                                                                    <input type="hidden" class="wcl-value-image" value="<?php echo esc_url($value->image_url); ?>">
                                                                    <button type="button" class="wcl-remove-image">×</button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="wcl-value-actions">
                                                    <button type="button" class="wcl-btn wcl-btn-danger wcl-delete-option-value">×</button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Rules Section -->
        <div class="wcl-rules-section">
            <h2 class="wcl-section-header"><?php esc_html_e('Conditional Rules', 'woo-conditional-logic'); ?></h2>
            
            <div class="wcl-add-new-section">
                <button type="button" class="button wcl-add-rule"><?php esc_html_e('Add New Rule', 'woo-conditional-logic'); ?></button>
            </div>
            
            <div class="wcl-rules-list">
                <?php foreach ($rules as $rule): ?>
                    <div class="wcl-rule-item" data-rule-id="<?php echo esc_attr($rule->id); ?>">
                        <div class="wcl-rule-header">
                            <div class="wcl-rule-title"><?php echo esc_html($rule->name); ?></div>
                            <div class="wcl-rule-actions">
                                <button type="button" class="wcl-btn wcl-save-rule"><?php esc_html_e('Save', 'woo-conditional-logic'); ?></button>
                                <button type="button" class="wcl-btn wcl-test-rule"><?php esc_html_e('Test', 'woo-conditional-logic'); ?></button>
                                <button type="button" class="wcl-btn wcl-btn-danger wcl-delete-rule"><?php esc_html_e('Delete', 'woo-conditional-logic'); ?></button>
                                <button type="button" class="wcl-collapse-toggle"><?php esc_html_e('Collapse', 'woo-conditional-logic'); ?></button>
                            </div>
                        </div>
                        <div class="wcl-collapsible-content">
                            <div class="wcl-form-row">
                                <div class="wcl-form-field">
                                    <label><?php esc_html_e('Rule Name', 'woo-conditional-logic'); ?></label>
                                    <input type="text" class="wcl-rule-name" value="<?php echo esc_attr($rule->name); ?>">
                                </div>
                            </div>
                            
                            <div class="wcl-rule-condition">
                                <h4><?php esc_html_e('Condition (IF)', 'woo-conditional-logic'); ?></h4>
                                <div class="wcl-condition-builder" data-conditions="<?php echo esc_attr($rule->condition_json); ?>">
                                    <!-- Condition builder will be populated by JavaScript -->
                                </div>
                            </div>
                            
                            <div class="wcl-rule-action">
                                <h4><?php esc_html_e('Action (THEN)', 'woo-conditional-logic'); ?></h4>
                                <div class="wcl-action-builder" data-action="<?php echo esc_attr($rule->action_json); ?>">
                                    <!-- Action builder will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Pass PHP data to JavaScript
    window.wcl_option_types = <?php echo wp_json_encode($option_types); ?>;
    window.wcl_comparison_operators = <?php echo wp_json_encode($comparison_operators); ?>;
    window.wcl_available_actions = <?php echo wp_json_encode($available_actions); ?>;
});
</script>
