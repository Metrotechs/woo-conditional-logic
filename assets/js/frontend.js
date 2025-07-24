/**
 * WooCommerce Conditional Logic - Frontend JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';

    const WCL_Frontend = {
        
        /**
         * Initialize frontend functionality
         */
        init: function() {
            this.bindEvents();
            this.initializeConditionalLogic();
            this.updatePricing();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Listen for option changes
            $(document).on('change', '.wcl-option input, .wcl-option select', this.handleOptionChange.bind(this));
            
            // File upload handling
            $(document).on('change', '.wcl-file-input', this.handleFileUpload.bind(this));
            
            // Form submission validation
            $('form.cart').on('submit', this.validateForm.bind(this));
        },

        /**
         * Handle option change
         */
        handleOptionChange: function(e) {
            const $changedInput = $(e.target);
            const $optionSet = $changedInput.closest('.wcl-option-set');
            
            // Update conditional logic
            this.updateConditionalLogic($optionSet);
            
            // Update pricing
            this.updatePricing();
            
            // Validate required fields
            this.validateOption($changedInput.closest('.wcl-option'));
        },

        /**
         * Initialize conditional logic for all option sets
         */
        initializeConditionalLogic: function() {
            $('.wcl-option-set').each((index, element) => {
                this.updateConditionalLogic($(element));
            });
        },

        /**
         * Update conditional logic for an option set
         */
        updateConditionalLogic: function($optionSet) {
            const setId = $optionSet.data('set-id');
            const selectedValues = this.getSelectedValues($optionSet);
            
            // Get rules from script tag
            const $rulesScript = $optionSet.find('.wcl-rules-data');
            if ($rulesScript.length === 0) {
                return;
            }

            let rules;
            try {
                rules = JSON.parse($rulesScript.text());
            } catch (e) {
                console.error('Error parsing rules:', e);
                return;
            }

            // Apply rules
            this.applyRules(rules, selectedValues, $optionSet);
        },

        /**
         * Get selected values for an option set
         */
        getSelectedValues: function($optionSet) {
            const values = {};
            
            $optionSet.find('.wcl-option').each(function() {
                const $option = $(this);
                const optionId = $option.data('option-id');
                const optionType = $option.data('type');
                
                let selectedValue = null;
                
                switch (optionType) {
                    case 'checkbox':
                    case 'multi_swatch':
                        const checkedValues = [];
                        $option.find('input:checked').each(function() {
                            checkedValues.push($(this).val());
                        });
                        selectedValue = checkedValues;
                        break;
                        
                    case 'radio':
                    case 'dropdown':
                    case 'swatch':
                    case 'button':
                        selectedValue = $option.find('input:checked, select').val() || '';
                        break;
                        
                    case 'text':
                    case 'textarea':
                    case 'number':
                    case 'date':
                        selectedValue = $option.find('input, textarea').val() || '';
                        break;
                        
                    case 'file':
                        const fileInput = $option.find('input[type="file"]')[0];
                        selectedValue = fileInput && fileInput.files.length > 0 ? 'file_selected' : '';
                        break;
                }
                
                if (selectedValue !== null) {
                    values[optionId] = selectedValue;
                }
            });
            
            return values;
        },

        /**
         * Apply rules to show/hide options and values
         */
        applyRules: function(rules, selectedValues, $optionSet) {
            // Reset visibility
            $optionSet.find('.wcl-option, .wcl-checkbox-label, .wcl-radio-label, .wcl-swatch-label, .wcl-button-label, .wcl-dropdown option')
                .removeClass('wcl-hidden');

            // Apply each rule
            rules.forEach(rule => {
                if (rule.status !== 'active') {
                    return;
                }

                let condition, action;
                try {
                    condition = JSON.parse(rule.condition_json);
                    action = JSON.parse(rule.action_json);
                } catch (e) {
                    console.error('Error parsing rule JSON:', e);
                    return;
                }

                if (this.evaluateCondition(condition, selectedValues)) {
                    this.applyAction(action, $optionSet);
                }
            });
        },

        /**
         * Evaluate rule condition
         */
        evaluateCondition: function(condition, selectedValues) {
            if (!condition || !condition.conditions) {
                return false;
            }

            const operator = condition.operator || 'and';
            const conditions = condition.conditions;
            const results = [];

            conditions.forEach(cond => {
                const optionId = cond.option_id;
                const comparison = cond.comparison;
                const targetValue = cond.value;
                const selectedValue = selectedValues[optionId];

                results.push(this.compareValues(selectedValue, targetValue, comparison));
            });

            if (operator === 'or') {
                return results.some(result => result === true);
            } else {
                return results.every(result => result === true);
            }
        },

        /**
         * Compare values based on comparison type
         */
        compareValues: function(selected, target, comparison) {
            switch (comparison) {
                case 'equals':
                    return selected === target;
                case 'not_equals':
                    return selected !== target;
                case 'contains':
                    if (Array.isArray(selected)) {
                        return selected.includes(target);
                    }
                    return String(selected).includes(target);
                case 'not_contains':
                    if (Array.isArray(selected)) {
                        return !selected.includes(target);
                    }
                    return !String(selected).includes(target);
                case 'empty':
                    return !selected || (Array.isArray(selected) && selected.length === 0);
                case 'not_empty':
                    return selected && (!Array.isArray(selected) || selected.length > 0);
                case 'greater_than':
                    return parseFloat(selected) > parseFloat(target);
                case 'less_than':
                    return parseFloat(selected) < parseFloat(target);
                default:
                    return false;
            }
        },

        /**
         * Apply rule action
         */
        applyAction: function(action, $optionSet) {
            if (!action) {
                return;
            }

            const actionType = action.type;
            const targetOptions = action.target_options || [];
            const targetValues = action.target_values || [];

            switch (actionType) {
                case 'hide':
                    // Hide options
                    targetOptions.forEach(optionId => {
                        $optionSet.find(`[data-option-id="${optionId}"]`).addClass('wcl-hidden');
                    });
                    
                    // Hide specific values
                    targetValues.forEach(valueData => {
                        const optionId = valueData.option_id;
                        const value = valueData.value;
                        
                        // Hide checkbox/radio labels
                        $optionSet.find(`[data-option-id="${optionId}"] input[value="${value}"]`)
                            .closest('.wcl-checkbox-label, .wcl-radio-label, .wcl-swatch-label, .wcl-button-label')
                            .addClass('wcl-hidden');
                            
                        // Hide dropdown options
                        $optionSet.find(`[data-option-id="${optionId}"] option[value="${value}"]`)
                            .addClass('wcl-hidden');
                    });
                    break;

                case 'show':
                    // Show options (remove hidden class)
                    targetOptions.forEach(optionId => {
                        $optionSet.find(`[data-option-id="${optionId}"]`).removeClass('wcl-hidden');
                    });
                    
                    // Show specific values
                    targetValues.forEach(valueData => {
                        const optionId = valueData.option_id;
                        const value = valueData.value;
                        
                        $optionSet.find(`[data-option-id="${optionId}"] input[value="${value}"]`)
                            .closest('.wcl-checkbox-label, .wcl-radio-label, .wcl-swatch-label, .wcl-button-label')
                            .removeClass('wcl-hidden');
                            
                        $optionSet.find(`[data-option-id="${optionId}"] option[value="${value}"]`)
                            .removeClass('wcl-hidden');
                    });
                    break;

                case 'require':
                    // Mark options as required
                    targetOptions.forEach(optionId => {
                        const $option = $optionSet.find(`[data-option-id="${optionId}"]`);
                        $option.find('input, select, textarea').prop('required', true);
                        $option.find('.wcl-option-label .required').remove();
                        $option.find('.wcl-option-label label').append(' <span class="required">*</span>');
                    });
                    break;
            }
        },

        /**
         * Update pricing based on selected options
         */
        updatePricing: function() {
            const selectedOptions = {};
            
            $('.wcl-option').each(function() {
                const $option = $(this);
                const optionId = $option.data('option-id');
                const optionType = $option.data('type');
                
                // Skip hidden options
                if ($option.hasClass('wcl-hidden')) {
                    return;
                }
                
                let selectedValues = [];
                
                switch (optionType) {
                    case 'checkbox':
                    case 'multi_swatch':
                        $option.find('input:checked').each(function() {
                            selectedValues.push($(this).val());
                        });
                        break;
                        
                    case 'radio':
                    case 'dropdown':
                    case 'swatch':
                    case 'button':
                        const value = $option.find('input:checked, select').val();
                        if (value) {
                            selectedValues.push(value);
                        }
                        break;
                        
                    default:
                        // For text inputs, we might have a price modifier on the option itself
                        const inputValue = $option.find('input, textarea').val();
                        if (inputValue) {
                            selectedValues.push(inputValue);
                        }
                        break;
                }
                
                if (selectedValues.length > 0) {
                    selectedOptions[optionId] = selectedValues;
                }
            });

            // Send AJAX request to calculate price
            if (Object.keys(selectedOptions).length > 0) {
                this.calculatePrice(selectedOptions);
            }
        },

        /**
         * Calculate price via AJAX
         */
        calculatePrice: function(selectedOptions) {
            const productId = $('input[name="product_id"], input[name="add-to-cart"]').val();
            
            if (!productId) {
                return;
            }

            $.ajax({
                url: wcl_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcl_calculate_price',
                    nonce: wcl_ajax.nonce,
                    product_id: productId,
                    selected_options: selectedOptions
                },
                success: function(response) {
                    if (response.success) {
                        // Update price display
                        const $priceDisplay = $('.woocommerce-Price-amount, .price .amount').first();
                        if ($priceDisplay.length) {
                            $priceDisplay.html(response.data.formatted_price);
                        }
                    }
                },
                error: function() {
                    console.error('Error calculating price');
                }
            });
        },

        /**
         * Validate option
         */
        validateOption: function($option) {
            const isRequired = $option.find('input, select, textarea').prop('required');
            const hasValue = this.optionHasValue($option);
            
            $option.removeClass('wcl-error');
            $option.find('.wcl-error-message').remove();
            
            if (isRequired && !hasValue && !$option.hasClass('wcl-hidden')) {
                $option.addClass('wcl-error');
                $option.find('.wcl-option-input').append(
                    '<div class="wcl-error-message">This field is required.</div>'
                );
                return false;
            }
            
            return true;
        },

        /**
         * Check if option has value
         */
        optionHasValue: function($option) {
            const optionType = $option.data('type');
            
            switch (optionType) {
                case 'checkbox':
                case 'multi_swatch':
                    return $option.find('input:checked').length > 0;
                    
                case 'radio':
                case 'swatch':
                case 'button':
                    return $option.find('input:checked').length > 0;
                    
                case 'dropdown':
                    return $option.find('select').val() !== '';
                    
                case 'text':
                case 'textarea':
                case 'number':
                case 'date':
                    return $option.find('input, textarea').val().trim() !== '';
                    
                case 'file':
                    const fileInput = $option.find('input[type="file"]')[0];
                    return fileInput && fileInput.files.length > 0;
                    
                default:
                    return true;
            }
        },

        /**
         * Handle file upload
         */
        handleFileUpload: function(e) {
            const $input = $(e.target);
            const $option = $input.closest('.wcl-option');
            
            // Trigger option change to update conditional logic
            this.handleOptionChange(e);
        },

        /**
         * Validate entire form
         */
        validateForm: function(e) {
            let isValid = true;
            
            $('.wcl-option').each((index, element) => {
                const $option = $(element);
                if (!$option.hasClass('wcl-hidden')) {
                    if (!this.validateOption($option)) {
                        isValid = false;
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                
                // Scroll to first error
                const $firstError = $('.wcl-option.wcl-error').first();
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 300);
                }
                
                return false;
            }
            
            return true;
        }
    };

    // Initialize when DOM is ready
    WCL_Frontend.init();
});
