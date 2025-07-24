<?php
/**
 * Rules Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCL_Rules {

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
        add_action('wp_ajax_wcl_save_rule', array($this, 'save_rule'));
        add_action('wp_ajax_wcl_delete_rule', array($this, 'ajax_delete_rule'));
        add_action('wp_ajax_wcl_test_rule', array($this, 'test_rule'));
    }

    /**
     * Get rules by option set
     */
    public function get_rules_by_set($option_set_id, $args = array()) {
        global $wpdb;

        $defaults = array(
            'status' => 'active',
            'orderby' => 'name',
            'order' => 'ASC'
        );

        $args = wp_parse_args($args, $defaults);

        $table = $wpdb->prefix . 'wcl_rules';
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
     * Get rule by ID
     */
    public function get_rule($id) {
        global $wpdb;

        $table = $wpdb->prefix . 'wcl_rules';
        $sql = $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id);
        
        return $wpdb->get_row($sql);
    }

    /**
     * Create new rule
     */
    public function create_rule($data) {
        global $wpdb;

        $defaults = array(
            'option_set_id' => 0,
            'name' => '',
            'condition_json' => '',
            'action_json' => '',
            'status' => 'active'
        );

        $data = wp_parse_args($data, $defaults);

        if (empty($data['name']) || empty($data['option_set_id'])) {
            return new WP_Error('required_fields', __('Rule name and option set are required.', 'woo-conditional-logic'));
        }

        if (empty($data['condition_json']) || empty($data['action_json'])) {
            return new WP_Error('required_fields', __('Rule condition and action are required.', 'woo-conditional-logic'));
        }

        // Validate JSON
        $condition = json_decode($data['condition_json'], true);
        $action = json_decode($data['action_json'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', __('Invalid rule configuration.', 'woo-conditional-logic'));
        }

        $table = $wpdb->prefix . 'wcl_rules';
        $result = $wpdb->insert(
            $table,
            array(
                'option_set_id' => intval($data['option_set_id']),
                'name' => sanitize_text_field($data['name']),
                'condition_json' => wp_json_encode($condition),
                'action_json' => wp_json_encode($action),
                'status' => sanitize_text_field($data['status'])
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );

        if (false === $result) {
            return new WP_Error('db_error', __('Failed to create rule.', 'woo-conditional-logic'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Update rule
     */
    public function update_rule($id, $data) {
        global $wpdb;

        $rule = $this->get_rule($id);
        if (!$rule) {
            return new WP_Error('not_found', __('Rule not found.', 'woo-conditional-logic'));
        }

        $allowed_fields = array('name', 'condition_json', 'action_json', 'status');
        $update_data = array();
        $format = array();

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                if ($field === 'name' && empty($data[$field])) {
                    return new WP_Error('empty_name', __('Rule name is required.', 'woo-conditional-logic'));
                }

                if (in_array($field, array('condition_json', 'action_json'))) {
                    if (empty($data[$field])) {
                        return new WP_Error('empty_rule', __('Rule condition and action are required.', 'woo-conditional-logic'));
                    }

                    // Validate JSON
                    $decoded = json_decode($data[$field], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return new WP_Error('invalid_json', __('Invalid rule configuration.', 'woo-conditional-logic'));
                    }

                    $update_data[$field] = wp_json_encode($decoded);
                } else {
                    $update_data[$field] = sanitize_text_field($data[$field]);
                }
                
                $format[] = '%s';
            }
        }

        if (empty($update_data)) {
            return true;
        }

        $table = $wpdb->prefix . 'wcl_rules';
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
     * Delete rule
     */
    public function delete_rule($id) {
        global $wpdb;

        $rule = $this->get_rule($id);
        if (!$rule) {
            return new WP_Error('not_found', __('Rule not found.', 'woo-conditional-logic'));
        }

        $table = $wpdb->prefix . 'wcl_rules';
        $result = $wpdb->delete(
            $table,
            array('id' => $id),
            array('%d')
        );

        return false !== $result;
    }

    /**
     * Evaluate rule condition
     */
    public function evaluate_condition($condition, $selected_values) {
        if (empty($condition) || !is_array($condition)) {
            return false;
        }

        $operator = $condition['operator'] ?? 'and';
        $conditions = $condition['conditions'] ?? array();

        if (empty($conditions)) {
            return false;
        }

        $results = array();

        foreach ($conditions as $cond) {
            $option_id = $cond['option_id'] ?? 0;
            $comparison = $cond['comparison'] ?? 'equals';
            $value = $cond['value'] ?? '';

            $selected = $selected_values[$option_id] ?? '';
            $result = $this->compare_values($selected, $value, $comparison);
            $results[] = $result;
        }

        // Apply logical operator
        if ($operator === 'or') {
            return in_array(true, $results);
        } else {
            return !in_array(false, $results);
        }
    }

    /**
     * Compare values based on comparison type
     */
    private function compare_values($selected, $target, $comparison) {
        switch ($comparison) {
            case 'equals':
                return $selected === $target;
            case 'not_equals':
                return $selected !== $target;
            case 'contains':
                if (is_array($selected)) {
                    return in_array($target, $selected);
                }
                return strpos($selected, $target) !== false;
            case 'not_contains':
                if (is_array($selected)) {
                    return !in_array($target, $selected);
                }
                return strpos($selected, $target) === false;
            case 'empty':
                return empty($selected);
            case 'not_empty':
                return !empty($selected);
            case 'greater_than':
                return floatval($selected) > floatval($target);
            case 'less_than':
                return floatval($selected) < floatval($target);
            default:
                return false;
        }
    }

    /**
     * Apply rule action
     */
    public function apply_action($action, $option_set_id) {
        if (empty($action) || !is_array($action)) {
            return array();
        }

        $action_type = $action['type'] ?? 'hide';
        $target_options = $action['target_options'] ?? array();
        $target_values = $action['target_values'] ?? array();

        $result = array(
            'type' => $action_type,
            'hidden_options' => array(),
            'hidden_values' => array(),
            'shown_options' => array(),
            'shown_values' => array()
        );

        switch ($action_type) {
            case 'hide':
                $result['hidden_options'] = $target_options;
                $result['hidden_values'] = $target_values;
                break;
            case 'show':
                $result['shown_options'] = $target_options;
                $result['shown_values'] = $target_values;
                break;
            case 'require':
                // Mark options as required
                $result['required_options'] = $target_options;
                break;
            case 'price_modifier':
                // Apply price modification
                $result['price_modifier'] = $action['price_modifier'] ?? 0;
                $result['price_type'] = $action['price_type'] ?? 'fixed';
                break;
        }

        return $result;
    }

    /**
     * Get available comparison operators
     */
    public function get_comparison_operators() {
        return array(
            'equals' => __('Equals', 'woo-conditional-logic'),
            'not_equals' => __('Does not equal', 'woo-conditional-logic'),
            'contains' => __('Contains', 'woo-conditional-logic'),
            'not_contains' => __('Does not contain', 'woo-conditional-logic'),
            'empty' => __('Is empty', 'woo-conditional-logic'),
            'not_empty' => __('Is not empty', 'woo-conditional-logic'),
            'greater_than' => __('Greater than', 'woo-conditional-logic'),
            'less_than' => __('Less than', 'woo-conditional-logic')
        );
    }

    /**
     * Get available actions
     */
    public function get_available_actions() {
        return array(
            'hide' => __('Hide', 'woo-conditional-logic'),
            'show' => __('Show', 'woo-conditional-logic'),
            'require' => __('Require', 'woo-conditional-logic'),
            'price_modifier' => __('Modify Price', 'woo-conditional-logic')
        );
    }

    /**
     * AJAX: Save rule
     */
    public function save_rule() {
        check_ajax_referer('wcl_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woo-conditional-logic'));
        }

        $id = intval($_POST['id'] ?? 0);
        $data = $_POST['data'] ?? array();

        if ($id > 0) {
            $result = $this->update_rule($id, $data);
        } else {
            $result = $this->create_rule($data);
        }

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array(
                'id' => $id > 0 ? $id : $result,
                'message' => __('Rule saved successfully.', 'woo-conditional-logic')
            ));
        }
    }

    /**
     * AJAX: Delete rule
     */
    public function ajax_delete_rule() {
        check_ajax_referer('wcl_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woo-conditional-logic'));
        }

        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            wp_send_json_error(__('Invalid rule ID.', 'woo-conditional-logic'));
        }

        $result = $this->delete_rule($id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Rule deleted successfully.', 'woo-conditional-logic'));
        }
    }

    /**
     * AJAX: Test rule
     */
    public function test_rule() {
        check_ajax_referer('wcl_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woo-conditional-logic'));
        }

        $condition = $_POST['condition'] ?? '';
        $selected_values = $_POST['selected_values'] ?? array();

        if (empty($condition)) {
            wp_send_json_error(__('No condition provided.', 'woo-conditional-logic'));
        }

        $condition_array = json_decode($condition, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(__('Invalid condition format.', 'woo-conditional-logic'));
        }

        $result = $this->evaluate_condition($condition_array, $selected_values);

        wp_send_json_success(array(
            'result' => $result,
            'message' => $result 
                ? __('Condition is true with the provided values.', 'woo-conditional-logic')
                : __('Condition is false with the provided values.', 'woo-conditional-logic')
        ));
    }
}
