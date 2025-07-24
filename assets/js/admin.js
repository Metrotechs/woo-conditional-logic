/**
 * WooCommerce Conditional Logic - Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';

    const WCL_Admin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initializeSortables();
            this.initializeColorPickers();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Option set actions
            $(document).on('click', '.wcl-save-option-set', this.saveOptionSet.bind(this));
            $(document).on('click', '.wcl-delete-option-set', this.deleteOptionSet.bind(this));
            $(document).on('click', '.wcl-duplicate-option-set', this.duplicateOptionSet.bind(this));
            
            // Option actions
            $(document).on('click', '.wcl-add-option', this.addOption.bind(this));
            $(document).on('click', '.wcl-save-option', this.saveOption.bind(this));
            $(document).on('click', '.wcl-delete-option', this.deleteOption.bind(this));
            $(document).on('change', '.wcl-option-type-select', this.handleOptionTypeChange.bind(this));
            
            // Option value actions
            $(document).on('click', '.wcl-add-option-value', this.addOptionValue.bind(this));
            $(document).on('click', '.wcl-save-option-value', this.saveOptionValue.bind(this));
            $(document).on('click', '.wcl-delete-option-value', this.deleteOptionValue.bind(this));
            
            // Rule actions
            $(document).on('click', '.wcl-add-rule', this.addRule.bind(this));
            $(document).on('click', '.wcl-save-rule', this.saveRule.bind(this));
            $(document).on('click', '.wcl-delete-rule', this.deleteRule.bind(this));
            $(document).on('click', '.wcl-test-rule', this.testRule.bind(this));
            
            // Condition and action builders
            $(document).on('click', '.wcl-add-condition', this.addCondition.bind(this));
            $(document).on('click', '.wcl-remove-condition', this.removeCondition.bind(this));
            $(document).on('change', '.wcl-condition-option-select', this.handleConditionOptionChange.bind(this));
            
            // Collapse toggles
            $(document).on('click', '.wcl-collapse-toggle', this.toggleCollapse.bind(this));
            
            // Image uploads
            $(document).on('click', '.wcl-upload-image', this.uploadImage.bind(this));
            $(document).on('click', '.wcl-remove-image', this.removeImage.bind(this));
        },

        /**
         * Initialize sortable elements
         */
        initializeSortables: function() {
            // Make options sortable
            $('.wcl-options-list').sortable({
                handle: '.wcl-drag-handle',
                placeholder: 'wcl-sortable-placeholder',
                update: this.updateOptionOrder.bind(this)
            });
            
            // Make option values sortable
            $('.wcl-option-values').sortable({
                handle: '.wcl-value-drag-handle',
                placeholder: 'wcl-sortable-placeholder',
                update: this.updateValueOrder.bind(this)
            });
        },

        /**
         * Initialize color pickers
         */
        initializeColorPickers: function() {
            if ($.fn.wpColorPicker) {
                $('.wcl-color-picker').wpColorPicker();
            }
        },

        /**
         * Save option set
         */
        saveOptionSet: function(e) {
            e.preventDefault();
            
            const $form = $(e.target).closest('form');
            const formData = new FormData($form[0]);
            formData.append('action', 'wcl_save_option_set');
            formData.append('nonce', wcl_admin.nonce);
            
            this.showLoading($form);
            
            $.ajax({
                url: wcl_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        
                        // Update URL if this was a new option set
                        if (response.data.id && !this.getUrlParameter('id')) {
                            const newUrl = window.location.href + '&id=' + response.data.id;
                            window.history.replaceState({}, '', newUrl);
                        }
                    } else {
                        this.showNotice('error', response.data);
                    }
                }.bind(this),
                error: function() {
                    this.showNotice('error', 'An error occurred while saving.');
                }.bind(this),
                complete: function() {
                    this.hideLoading($form);
                }.bind(this)
            });
        },

        /**
         * Delete option set
         */
        deleteOptionSet: function(e) {
            e.preventDefault();
            
            if (!confirm(wcl_admin.strings.confirm_delete)) {
                return;
            }
            
            const optionSetId = $(e.target).data('option-set-id');
            
            $.ajax({
                url: wcl_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcl_delete_option_set',
                    nonce: wcl_admin.nonce,
                    id: optionSetId
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = 'admin.php?page=wcl-option-sets';
                    } else {
                        this.showNotice('error', response.data);
                    }
                }.bind(this),
                error: function() {
                    this.showNotice('error', 'An error occurred while deleting.');
                }.bind(this)
            });
        },

        /**
         * Add new option
         */
        addOption: function(e) {
            e.preventDefault();
            
            const $container = $('.wcl-options-list');
            const optionSetId = $('#wcl-option-set-id').val();
            
            if (!optionSetId) {
                alert(wcl_admin.strings.save_changes);
                return;
            }
            
            const optionTemplate = this.getOptionTemplate();
            $container.append(optionTemplate);
            
            // Reinitialize sortables and color pickers
            this.initializeSortables();
            this.initializeColorPickers();
        },

        /**
         * Get option template HTML
         */
        getOptionTemplate: function() {
            return `
                <div class="wcl-option-item" data-option-id="0">
                    <div class="wcl-option-header">
                        <div class="wcl-drag-handle">⋮⋮</div>
                        <div class="wcl-option-title">New Option</div>
                        <div class="wcl-option-actions">
                            <button type="button" class="wcl-btn wcl-save-option">Save</button>
                            <button type="button" class="wcl-btn wcl-btn-danger wcl-delete-option">Delete</button>
                            <button type="button" class="wcl-collapse-toggle">Collapse</button>
                        </div>
                    </div>
                    <div class="wcl-collapsible-content">
                        <div class="wcl-form-row">
                            <div class="wcl-form-field wcl-form-field-medium">
                                <label>Option Name</label>
                                <input type="text" class="wcl-option-name" placeholder="Enter option name">
                            </div>
                            <div class="wcl-form-field wcl-form-field-medium">
                                <label>Option Type</label>
                                <select class="wcl-option-type-select">
                                    <option value="text">Text Field</option>
                                    <option value="textarea">Multi-line Text</option>
                                    <option value="number">Number Field</option>
                                    <option value="date">Date Picker</option>
                                    <option value="checkbox">Checkbox</option>
                                    <option value="radio">Radio Button</option>
                                    <option value="dropdown">Dropdown</option>
                                    <option value="swatch">Swatch</option>
                                    <option value="multi_swatch">Multi-select Swatch</option>
                                    <option value="button">Button</option>
                                    <option value="file">File Upload</option>
                                </select>
                            </div>
                            <div class="wcl-form-field wcl-form-field-small">
                                <label>Required</label>
                                <select class="wcl-option-required">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class="wcl-form-row">
                            <div class="wcl-form-field">
                                <label>Description</label>
                                <textarea class="wcl-option-description" rows="2" placeholder="Optional description"></textarea>
                            </div>
                        </div>
                        <div class="wcl-option-values">
                            <h4>Option Values</h4>
                            <button type="button" class="wcl-btn wcl-add-option-value">Add Value</button>
                            <div class="wcl-values-list"></div>
                        </div>
                    </div>
                </div>
            `;
        },

        /**
         * Handle option type change
         */
        handleOptionTypeChange: function(e) {
            const $select = $(e.target);
            const optionType = $select.val();
            const $optionItem = $select.closest('.wcl-option-item');
            const $valuesSection = $optionItem.find('.wcl-option-values');
            
            // Show/hide values section based on option type
            const typesWithValues = ['checkbox', 'radio', 'dropdown', 'swatch', 'multi_swatch', 'button'];
            
            if (typesWithValues.includes(optionType)) {
                $valuesSection.show();
            } else {
                $valuesSection.hide();
            }
        },

        /**
         * Add option value
         */
        addOptionValue: function(e) {
            e.preventDefault();
            
            const $valuesList = $(e.target).siblings('.wcl-values-list');
            const valueTemplate = this.getValueTemplate();
            $valuesList.append(valueTemplate);
            
            this.initializeColorPickers();
        },

        /**
         * Get value template HTML
         */
        getValueTemplate: function() {
            return `
                <div class="wcl-value-item" data-value-id="0">
                    <div class="wcl-value-drag-handle">⋮⋮</div>
                    <div class="wcl-value-fields">
                        <div class="wcl-form-field">
                            <input type="text" class="wcl-value-label" placeholder="Label">
                        </div>
                        <div class="wcl-form-field">
                            <input type="text" class="wcl-value-value" placeholder="Value">
                        </div>
                        <div class="wcl-form-field wcl-form-field-small">
                            <input type="number" class="wcl-value-price" step="0.01" placeholder="Price">
                        </div>
                        <div class="wcl-form-field">
                            <input type="text" class="wcl-color-picker wcl-value-color" placeholder="#000000">
                        </div>
                        <div class="wcl-form-field">
                            <button type="button" class="wcl-btn wcl-upload-image">Image</button>
                            <div class="wcl-image-preview"></div>
                        </div>
                    </div>
                    <div class="wcl-value-actions">
                        <button type="button" class="wcl-btn wcl-btn-danger wcl-delete-option-value">×</button>
                    </div>
                </div>
            `;
        },

        /**
         * Update option order
         */
        updateOptionOrder: function(event, ui) {
            const order = [];
            $('.wcl-option-item').each(function(index) {
                const optionId = $(this).data('option-id');
                if (optionId) {
                    order.push(optionId);
                }
            });
            
            $.ajax({
                url: wcl_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcl_reorder_options',
                    nonce: wcl_admin.nonce,
                    order: order
                },
                success: function(response) {
                    if (!response.success) {
                        this.showNotice('error', response.data);
                    }
                }.bind(this)
            });
        },

        /**
         * Update value order
         */
        updateValueOrder: function(event, ui) {
            const order = [];
            ui.item.closest('.wcl-values-list').find('.wcl-value-item').each(function(index) {
                const valueId = $(this).data('value-id');
                if (valueId) {
                    order.push(valueId);
                }
            });
            
            $.ajax({
                url: wcl_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcl_reorder_option_values',
                    nonce: wcl_admin.nonce,
                    order: order
                },
                success: function(response) {
                    if (!response.success) {
                        this.showNotice('error', response.data);
                    }
                }.bind(this)
            });
        },

        /**
         * Toggle collapse
         */
        toggleCollapse: function(e) {
            e.preventDefault();
            
            const $item = $(e.target).closest('.wcl-option-item, .wcl-rule-item');
            $item.toggleClass('wcl-collapsed');
            
            const $toggle = $(e.target);
            $toggle.text($item.hasClass('wcl-collapsed') ? 'Expand' : 'Collapse');
        },

        /**
         * Upload image
         */
        uploadImage: function(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            
            // WordPress media uploader
            if (wp && wp.media) {
                const mediaUploader = wp.media({
                    title: 'Select Image',
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });
                
                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    const $preview = $button.siblings('.wcl-image-preview');
                    
                    $preview.html(`
                        <img src="${attachment.url}" alt="">
                        <input type="hidden" class="wcl-value-image" value="${attachment.url}">
                        <button type="button" class="wcl-remove-image">×</button>
                    `);
                });
                
                mediaUploader.open();
            }
        },

        /**
         * Remove image
         */
        removeImage: function(e) {
            e.preventDefault();
            
            const $preview = $(e.target).closest('.wcl-image-preview');
            $preview.empty();
        },

        /**
         * Show loading state
         */
        showLoading: function($element) {
            $element.addClass('wcl-loading');
            $element.append('<div class="wcl-loading-overlay">Saving...</div>');
        },

        /**
         * Hide loading state
         */
        hideLoading: function($element) {
            $element.removeClass('wcl-loading');
            $element.find('.wcl-loading-overlay').remove();
        },

        /**
         * Show notice
         */
        showNotice: function(type, message) {
            const $notice = $(`
                <div class="wcl-notice wcl-notice-${type}">
                    <p>${message}</p>
                </div>
            `);
            
            $('.wrap').prepend($notice);
            
            // Auto-hide success notices
            if (type === 'success') {
                setTimeout(() => {
                    $notice.fadeOut();
                }, 3000);
            }
        },

        /**
         * Get URL parameter
         */
        getUrlParameter: function(name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }
    };

    // Initialize when DOM is ready
    WCL_Admin.init();
});
