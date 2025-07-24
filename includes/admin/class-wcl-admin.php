<?php
/**
 * Admin functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCL_Admin {

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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Conditional Logic', 'woo-conditional-logic'),
            __('Conditional Logic', 'woo-conditional-logic'),
            'manage_woocommerce',
            'wcl-option-sets',
            array($this, 'option_sets_page')
        );
        
        // Add debug info for troubleshooting
        if (WP_DEBUG) {
            error_log('WCL: Admin menu added successfully');
        }
    }

    /**
     * Admin init
     */
    public function admin_init() {
        // Register settings if needed
    }

    /**
     * Option sets page
     */
    public function option_sets_page() {
        $action = $_GET['action'] ?? 'list';
        $id = intval($_GET['id'] ?? 0);

        switch ($action) {
            case 'edit':
            case 'new':
                $this->edit_option_set_page($id);
                break;
            default:
                $this->list_option_sets_page();
                break;
        }
    }

    /**
     * List option sets page
     */
    private function list_option_sets_page() {
        $option_sets = WCL_Option_Sets::get_instance()->get_option_sets();
        
        include WCL_PLUGIN_PATH . 'includes/admin/views/option-sets-list.php';
    }

    /**
     * Edit option set page
     */
    private function edit_option_set_page($id = 0) {
        $option_set = null;
        $options = array();
        $rules = array();

        if ($id > 0) {
            $option_set = WCL_Option_Sets::get_instance()->get_option_set($id);
            if ($option_set) {
                $options = WCL_Options::get_instance()->get_options_by_set($id);
                $rules = WCL_Rules::get_instance()->get_rules_by_set($id);
            }
        }

        $option_types = WCL_Options::get_instance()->get_option_types();
        $comparison_operators = WCL_Rules::get_instance()->get_comparison_operators();
        $available_actions = WCL_Rules::get_instance()->get_available_actions();

        include WCL_PLUGIN_PATH . 'includes/admin/views/option-set-edit.php';
    }
}
