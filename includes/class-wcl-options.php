<?php
/**
 * Options Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCL_Options {

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
        add_action('wp_ajax_wcl_save_option', array($this, 'save_option'));
        add_action('wp_ajax_wcl_delete_option', array($this, 'ajax_delete_option'));
        add_action('wp_ajax_wcl_save_option_value', array($this, 'save_option_value'));
        add_action('wp_ajax_wcl_delete_option_value', array($this, 'ajax_delete_option_value'));
        add_action('wp_ajax_wcl_reorder_options', array($this, 'reorder_options'));
        add_action('wp_ajax_wcl_reorder_option_values', array($this, 'reorder_option_values'));
    }

    /**
     * Get options by option set
     */
    public function get_options_by_set($option_set_id, $args = array()) {
        global $wpdb;

        $defaults = array(
            'status' => 'active',
            'orderby' => 'position',
            'order' => 'ASC'
        );

        $args = wp_parse_args($args, $defaults);

        $table = $wpdb->prefix . 'wcl_options';
        $where = array('option_set_id = %d');
        $values = array($option_set_id);

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        $where_clause = implode(' AND ', $where);
        $order_clause = $args['orderby'] . ' ' . $args['order'];

        $sql = "SELECT * FROM $table WHERE $where_clause ORDER BY $order_clause";
        $sql = $wpdb->prepare($sql, $values);

        return $wpdb->get_results($sql);
    }

    /**
     * Get option by ID
     */
    public function get_option($id) {
        global $wpdb;

        $table = $wpdb->prefix . 'wcl_options';
        $sql = $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id);
        
        return $wpdb->get_row($sql);
    }

    /**
     * Create new option
     */
    public function create_option($data) {
        global $wpdb;

        $defaults = array(
            'option_set_id' => 0,
            'name' => '',
            'type' => 'text',
            'required' => 0,
            'multiple' => 0,
            'min_selection' => 0,
            'max_selection' => 0,
            'description' => '',
            'position' => 0,
            'status' => 'active'
        );

        $data = wp_parse_args($data, $defaults);

        if (empty($data['name']) || empty($data['option_set_id'])) {
            return new WP_Error('required_fields', __('Option name and option set are required.', 'woo-conditional-logic'));
        }

        // Get next position if not set
        if ($data['position'] == 0) {
            $data['position'] = $this->get_next_option_position($data['option_set_id']);
        }

        $table = $wpdb->prefix . 'wcl_options';
        $result = $wpdb->insert(
            $table,
            array(
                'option_set_id' => intval($data['option_set_id']),
                'name' => sanitize_text_field($data['name']),
                'type' => sanitize_text_field($data['type']),
                'required' => intval($data['required']),
                'multiple' => intval($data['multiple']),
                'min_selection' => intval($data['min_selection']),
                'max_selection' => intval($data['max_selection']),
                'description' => sanitize_textarea_field($data['description']),
                'position' => intval($data['position']),
                'status' => sanitize_text_field($data['status'])
            ),
            array('%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%d', '%s')
        );

        if (false === $result) {
            return new WP_Error('db_error', __('Failed to create option.', 'woo-conditional-logic'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Update option
     */
    public function update_option($id, $data) {
        global $wpdb;

        $option = $this->get_option($id);
        if (!$option) {
            return new WP_Error('not_found', __('Option not found.', 'woo-conditional-logic'));
        }

        $allowed_fields = array(
            'name', 'type', 'required', 'multiple', 'min_selection', 
            'max_selection', 'description', 'position', 'status'
        );
        
        $update_data = array();
        $format = array();

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                if ($field === 'name' && empty($data[$field])) {
                    return new WP_Error('empty_name', __('Option name is required.', 'woo-conditional-logic'));
                }
                
                if (in_array($field, array('required', 'multiple', 'min_selection', 'max_selection', 'position'))) {
                    $update_data[$field] = intval($data[$field]);
                    $format[] = '%d';
                } elseif ($field === 'description') {
                    $update_data[$field] = sanitize_textarea_field($data[$field]);
                    $format[] = '%s';
                } else {
                    $update_data[$field] = sanitize_text_field($data[$field]);
                    $format[] = '%s';
                }
            }
        }

        if (empty($update_data)) {
            return true;
        }

        $table = $wpdb->prefix . 'wcl_options';
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
     * Delete option
     */
    public function delete_option($id) {
        global $wpdb;

        $option = $this->get_option($id);
        if (!$option) {
            return new WP_Error('not_found', __('Option not found.', 'woo-conditional-logic'));
        }

        // Delete option values first
        $values_table = $wpdb->prefix . 'wcl_option_values';
        $wpdb->delete(
            $values_table,
            array('option_id' => $id),
            array('%d')
        );

        // Delete option
        $table = $wpdb->prefix . 'wcl_options';
        $result = $wpdb->delete(
            $table,
            array('id' => $id),
            array('%d')
        );

        return false !== $result;
    }

    /**
     * Get option values
     */
    public function get_option_values($option_id, $args = array()) {
        global $wpdb;

        $defaults = array(
            'status' => 'active',
            'orderby' => 'position',
            'order' => 'ASC'
        );

        $args = wp_parse_args($args, $defaults);

        $table = $wpdb->prefix . 'wcl_option_values';
        $where = array('option_id = %d');
        $values = array($option_id);

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        $where_clause = implode(' AND ', $where);
        $order_clause = $args['orderby'] . ' ' . $args['order'];

        $sql = "SELECT * FROM $table WHERE $where_clause ORDER BY $order_clause";
        $sql = $wpdb->prepare($sql, $values);

        return $wpdb->get_results($sql);
    }

    /**
     * Get option value by ID
     */
    public function get_option_value($id) {
        global $wpdb;

        $table = $wpdb->prefix . 'wcl_option_values';
        $sql = $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id);
        
        return $wpdb->get_row($sql);
    }

    /**
     * Create option value
     */
    public function create_option_value($data) {
        global $wpdb;

        $defaults = array(
            'option_id' => 0,
            'label' => '',
            'value' => '',
            'price_modifier' => 0.00,
            'price_type' => 'fixed',
            'description' => '',
            'image_url' => '',
            'color_hex' => '',
            'position' => 0,
            'is_default' => 0,
            'status' => 'active'
        );

        $data = wp_parse_args($data, $defaults);

        if (empty($data['label']) || empty($data['option_id'])) {
            return new WP_Error('required_fields', __('Option value label and option are required.', 'woo-conditional-logic'));
        }

        // Generate value if not provided
        if (empty($data['value'])) {
            $data['value'] = sanitize_title($data['label']);
        }

        // Get next position if not set
        if ($data['position'] == 0) {
            $data['position'] = $this->get_next_value_position($data['option_id']);
        }

        $table = $wpdb->prefix . 'wcl_option_values';
        $result = $wpdb->insert(
            $table,
            array(
                'option_id' => intval($data['option_id']),
                'label' => sanitize_text_field($data['label']),
                'value' => sanitize_text_field($data['value']),
                'price_modifier' => floatval($data['price_modifier']),
                'price_type' => sanitize_text_field($data['price_type']),
                'description' => sanitize_textarea_field($data['description']),
                'image_url' => esc_url_raw($data['image_url']),
                'color_hex' => sanitize_hex_color($data['color_hex']),
                'position' => intval($data['position']),
                'is_default' => intval($data['is_default']),
                'status' => sanitize_text_field($data['status'])
            ),
            array('%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d', '%d', '%s')
        );

        if (false === $result) {
            return new WP_Error('db_error', __('Failed to create option value.', 'woo-conditional-logic'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Update option value
     */
    public function update_option_value($id, $data) {
        global $wpdb;

        $value = $this->get_option_value($id);
        if (!$value) {
            return new WP_Error('not_found', __('Option value not found.', 'woo-conditional-logic'));
        }

        $allowed_fields = array(
            'label', 'value', 'price_modifier', 'price_type', 'description',
            'image_url', 'color_hex', 'position', 'is_default', 'status'
        );
        
        $update_data = array();
        $format = array();

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                if ($field === 'label' && empty($data[$field])) {
                    return new WP_Error('empty_label', __('Option value label is required.', 'woo-conditional-logic'));
                }
                
                if (in_array($field, array('position', 'is_default'))) {
                    $update_data[$field] = intval($data[$field]);
                    $format[] = '%d';
                } elseif ($field === 'price_modifier') {
                    $update_data[$field] = floatval($data[$field]);
                    $format[] = '%f';
                } elseif ($field === 'description') {
                    $update_data[$field] = sanitize_textarea_field($data[$field]);
                    $format[] = '%s';
                } elseif ($field === 'image_url') {
                    $update_data[$field] = esc_url_raw($data[$field]);
                    $format[] = '%s';
                } elseif ($field === 'color_hex') {
                    $update_data[$field] = sanitize_hex_color($data[$field]);
                    $format[] = '%s';
                } else {
                    $update_data[$field] = sanitize_text_field($data[$field]);
                    $format[] = '%s';
                }
            }
        }

        if (empty($update_data)) {
            return true;
        }

        $table = $wpdb->prefix . 'wcl_option_values';
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
     * Delete option value
     */
    public function delete_option_value($id) {
        global $wpdb;

        $value = $this->get_option_value($id);
        if (!$value) {
            return new WP_Error('not_found', __('Option value not found.', 'woo-conditional-logic'));
        }

        $table = $wpdb->prefix . 'wcl_option_values';
        $result = $wpdb->delete(
            $table,
            array('id' => $id),
            array('%d')
        );

        return false !== $result;
    }

    /**
     * Get next option position
     */
    private function get_next_option_position($option_set_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'wcl_options';
        $sql = $wpdb->prepare(
            "SELECT MAX(position) FROM $table WHERE option_set_id = %d",
            $option_set_id
        );
        
        $max_position = $wpdb->get_var($sql);
        return intval($max_position) + 1;
    }

    /**
     * Get next value position
     */
    private function get_next_value_position($option_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'wcl_option_values';
        $sql = $wpdb->prepare(
            "SELECT MAX(position) FROM $table WHERE option_id = %d",
            $option_id
        );
        
        $max_position = $wpdb->get_var($sql);
        return intval($max_position) + 1;
    }

    /**
     * Get option types
     */
    public function get_option_types() {
        return get_option('wcl_option_types', array());
    }

    /**
     * AJAX: Save option
     */
    public function save_option() {
        check_ajax_referer('wcl_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woo-conditional-logic'));
        }

        $id = intval($_POST['id'] ?? 0);
        $data = $_POST['data'] ?? array();

        if ($id > 0) {
            $result = $this->update_option($id, $data);
        } else {
            $result = $this->create_option($data);
        }

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array(
                'id' => $id > 0 ? $id : $result,
                'message' => __('Option saved successfully.', 'woo-conditional-logic')
            ));
        }
    }

    /**
     * AJAX: Delete option
     */
    public function ajax_delete_option() {
        check_ajax_referer('wcl_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woo-conditional-logic'));
        }

        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            wp_send_json_error(__('Invalid option ID.', 'woo-conditional-logic'));
        }

        $result = $this->delete_option($id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Option deleted successfully.', 'woo-conditional-logic'));
        }
    }

    /**
     * AJAX: Save option value
     */
    public function save_option_value() {
        check_ajax_referer('wcl_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woo-conditional-logic'));
        }

        $id = intval($_POST['id'] ?? 0);
        $data = $_POST['data'] ?? array();

        if ($id > 0) {
            $result = $this->update_option_value($id, $data);
        } else {
            $result = $this->create_option_value($data);
        }

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array(
                'id' => $id > 0 ? $id : $result,
                'message' => __('Option value saved successfully.', 'woo-conditional-logic')
            ));
        }
    }

    /**
     * AJAX: Delete option value
     */
    public function ajax_delete_option_value() {
        check_ajax_referer('wcl_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woo-conditional-logic'));
        }

        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            wp_send_json_error(__('Invalid option value ID.', 'woo-conditional-logic'));
        }

        $result = $this->delete_option_value($id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Option value deleted successfully.', 'woo-conditional-logic'));
        }
    }

    /**
     * AJAX: Reorder options
     */
    public function reorder_options() {
        check_ajax_referer('wcl_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woo-conditional-logic'));
        }

        $order = $_POST['order'] ?? array();

        if (empty($order) || !is_array($order)) {
            wp_send_json_error(__('Invalid order data.', 'woo-conditional-logic'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'wcl_options';

        foreach ($order as $position => $id) {
            $wpdb->update(
                $table,
                array('position' => $position + 1),
                array('id' => intval($id)),
                array('%d'),
                array('%d')
            );
        }

        wp_send_json_success(__('Options reordered successfully.', 'woo-conditional-logic'));
    }

    /**
     * AJAX: Reorder option values
     */
    public function reorder_option_values() {
        check_ajax_referer('wcl_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woo-conditional-logic'));
        }

        $order = $_POST['order'] ?? array();

        if (empty($order) || !is_array($order)) {
            wp_send_json_error(__('Invalid order data.', 'woo-conditional-logic'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'wcl_option_values';

        foreach ($order as $position => $id) {
            $wpdb->update(
                $table,
                array('position' => $position + 1),
                array('id' => intval($id)),
                array('%d'),
                array('%d')
            );
        }

        wp_send_json_success(__('Option values reordered successfully.', 'woo-conditional-logic'));
    }
}
