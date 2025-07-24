<?php
/**
 * Admin Products functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCL_Admin_Products {

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
        add_action('woocommerce_product_data_panels', array($this, 'add_product_tab_content'));
        add_filter('woocommerce_product_data_tabs', array($this, 'add_product_tab'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_options'));
    }

    /**
     * Add product tab
     */
    public function add_product_tab($tabs) {
        $tabs['wcl_conditional_logic'] = array(
            'label' => __('Conditional Logic', 'woo-conditional-logic'),
            'target' => 'wcl_conditional_logic_options',
            'class' => array('show_if_simple', 'show_if_variable')
        );
        return $tabs;
    }

    /**
     * Add product tab content
     */
    public function add_product_tab_content() {
        global $post;
        
        $product_id = $post->ID;
        $assigned_sets = $this->get_assigned_option_sets($product_id);
        $available_sets = WCL_Option_Sets::get_instance()->get_option_sets();
        
        include WCL_PLUGIN_PATH . 'includes/admin/views/product-options-tab.php';
    }

    /**
     * Save product options
     */
    public function save_product_options($product_id) {
        if (!current_user_can('edit_product', $product_id)) {
            return;
        }

        $option_sets = $_POST['wcl_option_sets'] ?? array();
        
        // Clear existing assignments
        $this->clear_product_option_sets($product_id);
        
        // Save new assignments
        if (!empty($option_sets)) {
            foreach ($option_sets as $position => $set_data) {
                $this->assign_option_set_to_product(
                    $product_id,
                    $set_data['id'],
                    $position,
                    $set_data['replace_variations'] ?? 0,
                    $set_data['hide_original_options'] ?? 0
                );
            }
        }
    }

    /**
     * Get assigned option sets for a product
     */
    private function get_assigned_option_sets($product_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'wcl_product_option_sets';
        $sets_table = $wpdb->prefix . 'wcl_option_sets';

        $sql = "SELECT pos.*, s.name FROM $table pos
                INNER JOIN $sets_table s ON pos.option_set_id = s.id
                WHERE pos.product_id = %d
                ORDER BY pos.position ASC";

        $sql = $wpdb->prepare($sql, $product_id);
        return $wpdb->get_results($sql);
    }

    /**
     * Clear product option sets
     */
    private function clear_product_option_sets($product_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'wcl_product_option_sets';
        $wpdb->delete(
            $table,
            array('product_id' => $product_id),
            array('%d')
        );
    }

    /**
     * Assign option set to product
     */
    private function assign_option_set_to_product($product_id, $option_set_id, $position = 0, $replace_variations = 0, $hide_original_options = 0) {
        global $wpdb;

        $table = $wpdb->prefix . 'wcl_product_option_sets';
        
        $wpdb->insert(
            $table,
            array(
                'product_id' => $product_id,
                'option_set_id' => $option_set_id,
                'position' => $position,
                'replace_variations' => $replace_variations,
                'hide_original_options' => $hide_original_options
            ),
            array('%d', '%d', '%d', '%d', '%d')
        );
    }
}
