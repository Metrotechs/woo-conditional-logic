<?php
/**
 * Product Options Tab View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="wcl_conditional_logic_options" class="panel woocommerce_options_panel">
    <div class="options_group">
        <p class="form-field">
            <label for="wcl_option_sets"><?php esc_html_e('Option Sets', 'woo-conditional-logic'); ?></label>
            <span class="description"><?php esc_html_e('Select which option sets to apply to this product.', 'woo-conditional-logic'); ?></span>
        </p>
        
        <div class="wcl-product-option-sets">
            <?php if (!empty($assigned_sets)): ?>
                <?php foreach ($assigned_sets as $index => $assigned_set): ?>
                    <div class="wcl-assigned-set" data-set-id="<?php echo esc_attr($assigned_set->option_set_id); ?>">
                        <div class="wcl-set-header">
                            <h4><?php echo esc_html($assigned_set->name); ?></h4>
                            <button type="button" class="button wcl-remove-set"><?php esc_html_e('Remove', 'woo-conditional-logic'); ?></button>
                        </div>
                        
                        <div class="wcl-set-options">
                            <p class="form-field">
                                <label>
                                    <input type="checkbox" name="wcl_option_sets[<?php echo esc_attr($index); ?>][replace_variations]" value="1" <?php checked($assigned_set->replace_variations, 1); ?>>
                                    <?php esc_html_e('Replace existing product variations', 'woo-conditional-logic'); ?>
                                </label>
                            </p>
                            
                            <p class="form-field">
                                <label>
                                    <input type="checkbox" name="wcl_option_sets[<?php echo esc_attr($index); ?>][hide_original_options]" value="1" <?php checked($assigned_set->hide_original_options, 1); ?>>
                                    <?php esc_html_e('Hide original product options', 'woo-conditional-logic'); ?>
                                </label>
                            </p>
                            
                            <input type="hidden" name="wcl_option_sets[<?php echo esc_attr($index); ?>][id]" value="<?php echo esc_attr($assigned_set->option_set_id); ?>">
                            <input type="hidden" name="wcl_option_sets[<?php echo esc_attr($index); ?>][position]" value="<?php echo esc_attr($assigned_set->position); ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <p class="form-field">
            <label for="wcl_available_sets"><?php esc_html_e('Add Option Set', 'woo-conditional-logic'); ?></label>
            <select id="wcl_available_sets">
                <option value=""><?php esc_html_e('Select an option set...', 'woo-conditional-logic'); ?></option>
                <?php foreach ($available_sets as $set): ?>
                    <option value="<?php echo esc_attr($set->id); ?>" data-name="<?php echo esc_attr($set->name); ?>">
                        <?php echo esc_html($set->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="button wcl-add-option-set"><?php esc_html_e('Add', 'woo-conditional-logic'); ?></button>
        </p>
        
        <p class="form-field">
            <span class="description">
                <?php 
                printf(
                    esc_html__('Manage option sets in %s.', 'woo-conditional-logic'),
                    '<a href="' . esc_url(admin_url('admin.php?page=wcl-option-sets')) . '">' . esc_html__('WooCommerce > Conditional Logic', 'woo-conditional-logic') . '</a>'
                );
                ?>
            </span>
        </p>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Add option set functionality
    $('.wcl-add-option-set').on('click', function() {
        const $select = $('#wcl_available_sets');
        const setId = $select.val();
        const setName = $select.find('option:selected').data('name');
        
        if (!setId) {
            return;
        }
        
        // Check if already added
        if ($('.wcl-assigned-set[data-set-id="' + setId + '"]').length > 0) {
            alert('<?php esc_html_e('This option set is already added.', 'woo-conditional-logic'); ?>');
            return;
        }
        
        const index = $('.wcl-assigned-set').length;
        const template = `
            <div class="wcl-assigned-set" data-set-id="${setId}">
                <div class="wcl-set-header">
                    <h4>${setName}</h4>
                    <button type="button" class="button wcl-remove-set"><?php esc_html_e('Remove', 'woo-conditional-logic'); ?></button>
                </div>
                
                <div class="wcl-set-options">
                    <p class="form-field">
                        <label>
                            <input type="checkbox" name="wcl_option_sets[${index}][replace_variations]" value="1">
                            <?php esc_html_e('Replace existing product variations', 'woo-conditional-logic'); ?>
                        </label>
                    </p>
                    
                    <p class="form-field">
                        <label>
                            <input type="checkbox" name="wcl_option_sets[${index}][hide_original_options]" value="1">
                            <?php esc_html_e('Hide original product options', 'woo-conditional-logic'); ?>
                        </label>
                    </p>
                    
                    <input type="hidden" name="wcl_option_sets[${index}][id]" value="${setId}">
                    <input type="hidden" name="wcl_option_sets[${index}][position]" value="${index}">
                </div>
            </div>
        `;
        
        $('.wcl-product-option-sets').append(template);
        $select.val('');
    });
    
    // Remove option set functionality
    $(document).on('click', '.wcl-remove-set', function() {
        $(this).closest('.wcl-assigned-set').remove();
        
        // Update indexes
        $('.wcl-assigned-set').each(function(index) {
            $(this).find('input[name*="["]').each(function() {
                const name = $(this).attr('name');
                const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                $(this).attr('name', newName);
            });
            
            $(this).find('input[name*="position"]').val(index);
        });
    });
});
</script>

<style>
.wcl-product-option-sets {
    margin: 15px 0;
}

.wcl-assigned-set {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
    background: #f9f9f9;
}

.wcl-set-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}

.wcl-set-header h4 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.wcl-set-options {
    padding-left: 20px;
}

.wcl-set-options .form-field {
    margin-bottom: 10px;
}

.wcl-set-options label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: normal;
}
</style>
