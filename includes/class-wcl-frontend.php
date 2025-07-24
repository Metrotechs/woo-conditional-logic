<?php
/**
 * Frontend functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCL_Frontend {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
    }

    /**
     * Initialize
     */
    public function init() {
        // Product page hooks
        add_action('woocommerce_before_add_to_cart_button', array($this, 'display_product_options'), 10);
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_cart_item_data'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_order_item_data'), 10, 4);
        
        // Price modification
        add_filter('woocommerce_add_cart_item', array($this, 'modify_cart_item_price'), 10, 2);
        add_filter('woocommerce_get_cart_item_from_session', array($this, 'modify_cart_item_price'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_wcl_get_conditional_options', array($this, 'get_conditional_options'));
        add_action('wp_ajax_nopriv_wcl_get_conditional_options', array($this, 'get_conditional_options'));
        add_action('wp_ajax_wcl_calculate_price', array($this, 'calculate_price'));
        add_action('wp_ajax_nopriv_wcl_calculate_price', array($this, 'calculate_price'));
    }

    /**
     * Display product options on product page
     */
    public function display_product_options() {
        global $product;

        if (!$product || !$product->get_id()) {
            return;
        }

        $option_sets = $this->get_product_option_sets($product->get_id());

        if (empty($option_sets)) {
            return;
        }

        echo '<div id="wcl-product-options" class="wcl-product-options">';
        
        foreach ($option_sets as $option_set) {
            $this->display_option_set($option_set);
        }
        
        echo '</div>';
    }

    /**
     * Get option sets for a product
     */
    private function get_product_option_sets($product_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'wcl_product_option_sets';
        $sets_table = $wpdb->prefix . 'wcl_option_sets';

        $sql = "SELECT s.* FROM $sets_table s
                INNER JOIN $table pos ON s.id = pos.option_set_id
                WHERE pos.product_id = %d AND s.status = 'active'
                ORDER BY pos.position ASC";

        $sql = $wpdb->prepare($sql, $product_id);
        return $wpdb->get_results($sql);
    }

    /**
     * Display option set
     */
    private function display_option_set($option_set) {
        $options = WCL_Options::get_instance()->get_options_by_set($option_set->id);
        $rules = WCL_Rules::get_instance()->get_rules_by_set($option_set->id);

        if (empty($options)) {
            return;
        }

        echo '<div class="wcl-option-set" data-set-id="' . esc_attr($option_set->id) . '">';
        
        if (!empty($option_set->name)) {
            echo '<h3 class="wcl-option-set-title">' . esc_html($option_set->name) . '</h3>';
        }

        if (!empty($option_set->description)) {
            echo '<div class="wcl-option-set-description">' . wp_kses_post($option_set->description) . '</div>';
        }

        foreach ($options as $option) {
            $this->display_option($option);
        }

        // Include rules data for JavaScript
        if (!empty($rules)) {
            echo '<script type="application/json" class="wcl-rules-data">' . wp_json_encode($rules) . '</script>';
        }

        echo '</div>';
    }

    /**
     * Display individual option
     */
    private function display_option($option) {
        $values = WCL_Options::get_instance()->get_option_values($option->id);
        $option_types = WCL_Options::get_instance()->get_option_types();
        $option_type = $option_types[$option->type] ?? array();

        if (empty($values) && !in_array($option->type, array('text', 'textarea', 'number', 'date', 'file'))) {
            return;
        }

        $required_attr = $option->required ? 'required' : '';
        $multiple_attr = $option->multiple ? 'multiple' : '';

        echo '<div class="wcl-option" data-option-id="' . esc_attr($option->id) . '" data-type="' . esc_attr($option->type) . '">';
        
        echo '<div class="wcl-option-label">';
        echo '<label>' . esc_html($option->name);
        if ($option->required) {
            echo ' <span class="required">*</span>';
        }
        echo '</label>';
        
        if (!empty($option->description)) {
            echo '<div class="wcl-option-description">' . wp_kses_post($option->description) . '</div>';
        }
        echo '</div>';

        echo '<div class="wcl-option-input">';

        switch ($option->type) {
            case 'checkbox':
                $this->render_checkbox_option($option, $values);
                break;
            case 'radio':
                $this->render_radio_option($option, $values);
                break;
            case 'dropdown':
                $this->render_dropdown_option($option, $values);
                break;
            case 'swatch':
            case 'multi_swatch':
                $this->render_swatch_option($option, $values);
                break;
            case 'button':
                $this->render_button_option($option, $values);
                break;
            case 'text':
                $this->render_text_option($option);
                break;
            case 'textarea':
                $this->render_textarea_option($option);
                break;
            case 'number':
                $this->render_number_option($option);
                break;
            case 'date':
                $this->render_date_option($option);
                break;
            case 'file':
                $this->render_file_option($option);
                break;
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Render checkbox option
     */
    private function render_checkbox_option($option, $values) {
        $name = "wcl_option_{$option->id}[]";
        
        foreach ($values as $value) {
            $price_display = $this->get_price_display($value);
            
            echo '<label class="wcl-checkbox-label">';
            echo '<input type="checkbox" name="' . esc_attr($name) . '" value="' . esc_attr($value->value) . '" data-price="' . esc_attr($value->price_modifier) . '" data-price-type="' . esc_attr($value->price_type) . '">';
            echo '<span class="wcl-checkbox-text">' . esc_html($value->label) . $price_display . '</span>';
            echo '</label>';
        }
    }

    /**
     * Render radio option
     */
    private function render_radio_option($option, $values) {
        $name = "wcl_option_{$option->id}";
        
        foreach ($values as $value) {
            $price_display = $this->get_price_display($value);
            $checked = $value->is_default ? 'checked' : '';
            
            echo '<label class="wcl-radio-label">';
            echo '<input type="radio" name="' . esc_attr($name) . '" value="' . esc_attr($value->value) . '" data-price="' . esc_attr($value->price_modifier) . '" data-price-type="' . esc_attr($value->price_type) . '" ' . $checked . '>';
            echo '<span class="wcl-radio-text">' . esc_html($value->label) . $price_display . '</span>';
            echo '</label>';
        }
    }

    /**
     * Render dropdown option
     */
    private function render_dropdown_option($option, $values) {
        $name = "wcl_option_{$option->id}";
        $required = $option->required ? 'required' : '';
        
        echo '<select name="' . esc_attr($name) . '" class="wcl-dropdown" ' . $required . '>';
        
        if (!$option->required) {
            echo '<option value="">' . __('Please select...', 'woo-conditional-logic') . '</option>';
        }
        
        foreach ($values as $value) {
            $price_display = $this->get_price_display($value);
            $selected = $value->is_default ? 'selected' : '';
            
            echo '<option value="' . esc_attr($value->value) . '" data-price="' . esc_attr($value->price_modifier) . '" data-price-type="' . esc_attr($value->price_type) . '" ' . $selected . '>';
            echo esc_html($value->label) . $price_display;
            echo '</option>';
        }
        
        echo '</select>';
    }

    /**
     * Render swatch option
     */
    private function render_swatch_option($option, $values) {
        $name = $option->type === 'multi_swatch' ? "wcl_option_{$option->id}[]" : "wcl_option_{$option->id}";
        $input_type = $option->type === 'multi_swatch' ? 'checkbox' : 'radio';
        
        echo '<div class="wcl-swatches">';
        
        foreach ($values as $value) {
            $price_display = $this->get_price_display($value);
            $checked = $value->is_default ? 'checked' : '';
            
            echo '<label class="wcl-swatch-label">';
            echo '<input type="' . $input_type . '" name="' . esc_attr($name) . '" value="' . esc_attr($value->value) . '" data-price="' . esc_attr($value->price_modifier) . '" data-price-type="' . esc_attr($value->price_type) . '" ' . $checked . '>';
            
            echo '<span class="wcl-swatch"';
            if (!empty($value->color_hex)) {
                echo ' style="background-color: ' . esc_attr($value->color_hex) . '"';
            } elseif (!empty($value->image_url)) {
                echo ' style="background-image: url(' . esc_url($value->image_url) . ')"';
            }
            echo '>';
            
            if (empty($value->color_hex) && empty($value->image_url)) {
                echo esc_html($value->label);
            }
            
            echo '</span>';
            
            if (!empty($value->label)) {
                echo '<span class="wcl-swatch-label-text">' . esc_html($value->label) . $price_display . '</span>';
            }
            
            echo '</label>';
        }
        
        echo '</div>';
    }

    /**
     * Render button option
     */
    private function render_button_option($option, $values) {
        $name = "wcl_option_{$option->id}";
        
        echo '<div class="wcl-buttons">';
        
        foreach ($values as $value) {
            $price_display = $this->get_price_display($value);
            $checked = $value->is_default ? 'checked' : '';
            
            echo '<label class="wcl-button-label">';
            echo '<input type="radio" name="' . esc_attr($name) . '" value="' . esc_attr($value->value) . '" data-price="' . esc_attr($value->price_modifier) . '" data-price-type="' . esc_attr($value->price_type) . '" ' . $checked . '>';
            echo '<span class="wcl-button">' . esc_html($value->label) . $price_display . '</span>';
            echo '</label>';
        }
        
        echo '</div>';
    }

    /**
     * Render text option
     */
    private function render_text_option($option) {
        $name = "wcl_option_{$option->id}";
        $required = $option->required ? 'required' : '';
        
        echo '<input type="text" name="' . esc_attr($name) . '" class="wcl-text-input" ' . $required . '>';
    }

    /**
     * Render textarea option
     */
    private function render_textarea_option($option) {
        $name = "wcl_option_{$option->id}";
        $required = $option->required ? 'required' : '';
        
        echo '<textarea name="' . esc_attr($name) . '" class="wcl-textarea-input" rows="4" ' . $required . '></textarea>';
    }

    /**
     * Render number option
     */
    private function render_number_option($option) {
        $name = "wcl_option_{$option->id}";
        $required = $option->required ? 'required' : '';
        
        echo '<input type="number" name="' . esc_attr($name) . '" class="wcl-number-input" ' . $required . '>';
    }

    /**
     * Render date option
     */
    private function render_date_option($option) {
        $name = "wcl_option_{$option->id}";
        $required = $option->required ? 'required' : '';
        
        echo '<input type="date" name="' . esc_attr($name) . '" class="wcl-date-input" ' . $required . '>';
    }

    /**
     * Render file option
     */
    private function render_file_option($option) {
        $name = "wcl_option_{$option->id}";
        $required = $option->required ? 'required' : '';
        
        echo '<input type="file" name="' . esc_attr($name) . '" class="wcl-file-input" ' . $required . '>';
    }

    /**
     * Get price display for option value
     */
    private function get_price_display($value) {
        if ($value->price_modifier == 0) {
            return '';
        }

        $price = wc_price($value->price_modifier);
        $symbol = $value->price_modifier > 0 ? '+' : '';
        
        return ' <span class="wcl-price">(' . $symbol . $price . ')</span>';
    }

    /**
     * Add cart item data
     */
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        $wcl_options = array();

        foreach ($_POST as $key => $value) {
            if (strpos($key, 'wcl_option_') === 0) {
                $option_id = intval(str_replace('wcl_option_', '', $key));
                $wcl_options[$option_id] = is_array($value) ? $value : sanitize_text_field($value);
            }
        }

        if (!empty($wcl_options)) {
            $cart_item_data['wcl_options'] = $wcl_options;
            $cart_item_data['wcl_price_modifier'] = $this->calculate_total_price_modifier($wcl_options);
        }

        return $cart_item_data;
    }

    /**
     * Calculate total price modifier
     */
    private function calculate_total_price_modifier($options) {
        $total_modifier = 0;

        foreach ($options as $option_id => $selected_values) {
            $values = WCL_Options::get_instance()->get_option_values($option_id);
            
            if (!is_array($selected_values)) {
                $selected_values = array($selected_values);
            }

            foreach ($values as $value) {
                if (in_array($value->value, $selected_values)) {
                    $total_modifier += floatval($value->price_modifier);
                }
            }
        }

        return $total_modifier;
    }

    /**
     * Display cart item data
     */
    public function display_cart_item_data($item_data, $cart_item) {
        if (!isset($cart_item['wcl_options'])) {
            return $item_data;
        }

        foreach ($cart_item['wcl_options'] as $option_id => $selected_values) {
            $option = WCL_Options::get_instance()->get_option($option_id);
            $values = WCL_Options::get_instance()->get_option_values($option_id);

            if (!$option) {
                continue;
            }

            if (!is_array($selected_values)) {
                $selected_values = array($selected_values);
            }

            $display_values = array();
            foreach ($values as $value) {
                if (in_array($value->value, $selected_values)) {
                    $display_values[] = $value->label;
                }
            }

            if (!empty($display_values)) {
                $item_data[] = array(
                    'key' => $option->name,
                    'value' => implode(', ', $display_values),
                    'display' => ''
                );
            }
        }

        return $item_data;
    }

    /**
     * Modify cart item price
     */
    public function modify_cart_item_price($cart_item, $cart_item_key) {
        if (isset($cart_item['wcl_price_modifier']) && $cart_item['wcl_price_modifier'] != 0) {
            $product = $cart_item['data'];
            $new_price = $product->get_price() + $cart_item['wcl_price_modifier'];
            $product->set_price(max(0, $new_price));
        }

        return $cart_item;
    }

    /**
     * Save order item data
     */
    public function save_order_item_data($item, $cart_item_key, $values, $order) {
        $cart_item = WC()->cart->get_cart_item($cart_item_key);
        
        if (isset($cart_item['wcl_options'])) {
            foreach ($cart_item['wcl_options'] as $option_id => $selected_values) {
                $option = WCL_Options::get_instance()->get_option($option_id);
                $option_values = WCL_Options::get_instance()->get_option_values($option_id);

                if (!$option) {
                    continue;
                }

                if (!is_array($selected_values)) {
                    $selected_values = array($selected_values);
                }

                $display_values = array();
                foreach ($option_values as $value) {
                    if (in_array($value->value, $selected_values)) {
                        $display_values[] = $value->label;
                    }
                }

                if (!empty($display_values)) {
                    $item->add_meta_data($option->name, implode(', ', $display_values));
                }
            }
        }
    }

    /**
     * AJAX: Get conditional options
     */
    public function get_conditional_options() {
        check_ajax_referer('wcl_nonce', 'nonce');

        $option_set_id = intval($_POST['option_set_id'] ?? 0);
        $selected_values = $_POST['selected_values'] ?? array();

        if ($option_set_id <= 0) {
            wp_send_json_error(__('Invalid option set.', 'woo-conditional-logic'));
        }

        $rules = WCL_Rules::get_instance()->get_rules_by_set($option_set_id);
        $hidden_options = array();
        $hidden_values = array();

        foreach ($rules as $rule) {
            $condition = json_decode($rule->condition_json, true);
            $action = json_decode($rule->action_json, true);

            if (WCL_Rules::get_instance()->evaluate_condition($condition, $selected_values)) {
                $result = WCL_Rules::get_instance()->apply_action($action, $option_set_id);
                
                $hidden_options = array_merge($hidden_options, $result['hidden_options'] ?? array());
                $hidden_values = array_merge($hidden_values, $result['hidden_values'] ?? array());
            }
        }

        wp_send_json_success(array(
            'hidden_options' => array_unique($hidden_options),
            'hidden_values' => array_unique($hidden_values)
        ));
    }

    /**
     * AJAX: Calculate price
     */
    public function calculate_price() {
        check_ajax_referer('wcl_nonce', 'nonce');

        $product_id = intval($_POST['product_id'] ?? 0);
        $selected_options = $_POST['selected_options'] ?? array();

        if ($product_id <= 0) {
            wp_send_json_error(__('Invalid product.', 'woo-conditional-logic'));
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(__('Product not found.', 'woo-conditional-logic'));
        }

        $base_price = $product->get_price();
        $modifier = $this->calculate_total_price_modifier($selected_options);
        $new_price = $base_price + $modifier;

        wp_send_json_success(array(
            'base_price' => $base_price,
            'modifier' => $modifier,
            'new_price' => max(0, $new_price),
            'formatted_price' => wc_price(max(0, $new_price))
        ));
    }
}
