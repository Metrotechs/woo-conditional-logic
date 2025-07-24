<?php
/**
 * Option Sets Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCL_Option_Sets {

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
        // Add AJAX handlers
        add_action('wp_ajax_wcl_save_option_set', array($this, 'save_option_set'));
        add_action('wp_ajax_wcl_delete_option_set', array($this, 'ajax_delete_option_set'));
        add_action('wp_ajax_wcl_duplicate_option_set', array($this, 'ajax_duplicate_option_set'));
    }

    /**
     * Get all option sets
     */
    public function get_option_sets($args = array()) {
        global $wpdb;

        $defaults = array(
            'status' => 'active',
            'orderby' => 'name',
            'order' => 'ASC',
            'limit' => -1,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);

        $table = $wpdb->prefix . 'wcl_option_sets';
        $where = array('1=1');
        $values = array();

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        $where_clause = implode(' AND ', $where);
        $order_clause = $args['orderby'] . ' ' . $args['order'];
        $limit_clause = '';

        if ($args['limit'] > 0) {
            $limit_clause = $wpdb->prepare('LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        }

        $sql = "SELECT * FROM $table WHERE $where_clause ORDER BY $order_clause $limit_clause";
        
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Get option set by ID
     */
    public function get_option_set($id) {
        global $wpdb;

        $table = $wpdb->prefix . 'wcl_option_sets';
        $sql = $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id);
        
        return $wpdb->get_row($sql);
    }

    /**
     * Create new option set
     */
    public function create_option_set($data) {
        global $wpdb;

        $defaults = array(
            'name' => '',
            'description' => '',
            'status' => 'active'
        );

        $data = wp_parse_args($data, $defaults);

        if (empty($data['name'])) {
            return new WP_Error('empty_name', __('Option set name is required.', 'woo-conditional-logic'));
        }

        $table = $wpdb->prefix . 'wcl_option_sets';
        $result = $wpdb->insert(
            $table,
            array(
                'name' => sanitize_text_field($data['name']),
                'description' => sanitize_textarea_field($data['description']),
                'status' => sanitize_text_field($data['status'])
            ),
            array('%s', '%s', '%s')
        );

        if (false === $result) {
            return new WP_Error('db_error', __('Failed to create option set.', 'woo-conditional-logic'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Update option set
     */
    public function update_option_set($id, $data) {
        global $wpdb;

        $option_set = $this->get_option_set($id);
        if (!$option_set) {
            return new WP_Error('not_found', __('Option set not found.', 'woo-conditional-logic'));
        }

        $allowed_fields = array('name', 'description', 'status');
        $update_data = array();
        $format = array();

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                if ($field === 'name' && empty($data[$field])) {
                    return new WP_Error('empty_name', __('Option set name is required.', 'woo-conditional-logic'));
                }
                
                $update_data[$field] = ($field === 'description') 
                    ? sanitize_textarea_field($data[$field])
                    : sanitize_text_field($data[$field]);
                $format[] = '%s';
            }
        }

        if (empty($update_data)) {
            return true;
        }

        $table = $wpdb->prefix . 'wcl_option_sets';
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $id),
            $format,
            array('%d')
        );

        return false !== $result;
    }

    /**
     * Delete option set
     */
    public function delete_option_set($id) {
        global $wpdb;

        $option_set = $this->get_option_set($id);
        if (!$option_set) {
            return new WP_Error('not_found', __('Option set not found.', 'woo-conditional-logic'));
        }

        // Delete related data
        $this->delete_option_set_data($id);

        // Delete option set
        $table = $wpdb->prefix . 'wcl_option_sets';
        $result = $wpdb->delete(
            $table,
            array('id' => $id),
            array('%d')
        );

        return false !== $result;
    }

    /**
     * Delete option set related data
     */
    private function delete_option_set_data($option_set_id) {
        global $wpdb;

        // Get all options for this set
        $options_table = $wpdb->prefix . 'wcl_options';
        $options = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM $options_table WHERE option_set_id = %d",
            $option_set_id
        ));

        if (!empty($options)) {
            // Delete option values
            $values_table = $wpdb->prefix . 'wcl_option_values';
            $option_ids = implode(',', array_map('intval', $options));
            $wpdb->query("DELETE FROM $values_table WHERE option_id IN ($option_ids)");

            // Delete options
            $wpdb->delete(
                $options_table,
                array('option_set_id' => $option_set_id),
                array('%d')
            );
        }

        // Delete rules
        $rules_table = $wpdb->prefix . 'wcl_rules';
        $wpdb->delete(
            $rules_table,
            array('option_set_id' => $option_set_id),
            array('%d')
        );

        // Delete product associations
        $product_sets_table = $wpdb->prefix . 'wcl_product_option_sets';
        $wpdb->delete(
            $product_sets_table,
            array('option_set_id' => $option_set_id),
            array('%d')
        );
    }

    /**
     * Duplicate option set
     */
    public function duplicate_option_set($id) {
        global $wpdb;

        $original = $this->get_option_set($id);
        if (!$original) {
            return new WP_Error('not_found', __('Option set not found.', 'woo-conditional-logic'));
        }

        // Create new option set
        $new_name = sprintf(__('%s (Copy)', 'woo-conditional-logic'), $original->name);
        $new_id = $this->create_option_set(array(
            'name' => $new_name,
            'description' => $original->description,
            'status' => $original->status
        ));

        if (is_wp_error($new_id)) {
            return $new_id;
        }

        // Copy options and values
        $options = WCL_Options::get_instance()->get_options_by_set($id);
        foreach ($options as $option) {
            $new_option_id = WCL_Options::get_instance()->create_option(array(
                'option_set_id' => $new_id,
                'name' => $option->name,
                'type' => $option->type,
                'required' => $option->required,
                'multiple' => $option->multiple,
                'min_selection' => $option->min_selection,
                'max_selection' => $option->max_selection,
                'description' => $option->description,
                'position' => $option->position,
                'status' => $option->status
            ));

            if (!is_wp_error($new_option_id)) {
                // Copy option values
                $values = WCL_Options::get_instance()->get_option_values($option->id);
                foreach ($values as $value) {
                    WCL_Options::get_instance()->create_option_value(array(
                        'option_id' => $new_option_id,
                        'label' => $value->label,
                        'value' => $value->value,
                        'price_modifier' => $value->price_modifier,
                        'price_type' => $value->price_type,
                        'description' => $value->description,
                        'image_url' => $value->image_url,
                        'color_hex' => $value->color_hex,
                        'position' => $value->position,
                        'is_default' => $value->is_default,
                        'status' => $value->status
                    ));
                }
            }
        }

        // Copy rules
        $rules = WCL_Rules::get_instance()->get_rules_by_set($id);
        foreach ($rules as $rule) {
            WCL_Rules::get_instance()->create_rule(array(
                'option_set_id' => $new_id,
                'name' => $rule->name,
                'condition_json' => $rule->condition_json,
                'action_json' => $rule->action_json,
                'status' => $rule->status
            ));
        }

        return $new_id;
    }

    /**
     * AJAX: Save option set
     */
    public function save_option_set() {
        check_ajax_referer('wcl_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woo-conditional-logic'));
        }

        $id = intval($_POST['id'] ?? 0);
        $data = $_POST['data'] ?? array();

        if ($id > 0) {
            $result = $this->update_option_set($id, $data);
        } else {
            $result = $this->create_option_set($data);
        }

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array(
                'id' => $id > 0 ? $id : $result,
                'message' => __('Option set saved successfully.', 'woo-conditional-logic')
            ));
        }
    }

    /**
     * AJAX: Delete option set
     */
    public function ajax_delete_option_set() {
        check_ajax_referer('wcl_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woo-conditional-logic'));
        }

        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            wp_send_json_error(__('Invalid option set ID.', 'woo-conditional-logic'));
        }

        $result = $this->delete_option_set($id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Option set deleted successfully.', 'woo-conditional-logic'));
        }
    }

    /**
     * AJAX: Duplicate option set
     */
    public function ajax_duplicate_option_set() {
        check_ajax_referer('wcl_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woo-conditional-logic'));
        }

        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            wp_send_json_error(__('Invalid option set ID.', 'woo-conditional-logic'));
        }

        $result = $this->duplicate_option_set($id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array(
                'id' => $result,
                'message' => __('Option set duplicated successfully.', 'woo-conditional-logic')
            ));
        }
    }
}
