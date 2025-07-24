<?php
/**
 * Plugin Name: WooCommerce Conditional Logic
 * Plugin URI: https://github.com/metrotechs/woo-conditional-logic
 * Description: Add conditional logic to WooCommerce variable products with advanced product options and rules.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: woo-conditional-logic
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WCL_PLUGIN_FILE', __FILE__);
define('WCL_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WCL_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WCL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WCL_VERSION', '1.0.0');

/**
 * Main WooCommerce Conditional Logic class
 */
class WooCommerce_Conditional_Logic {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get single instance
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
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core classes
        require_once WCL_PLUGIN_PATH . 'includes/class-wcl-install.php';
        require_once WCL_PLUGIN_PATH . 'includes/class-wcl-option-sets.php';
        require_once WCL_PLUGIN_PATH . 'includes/class-wcl-options.php';
        require_once WCL_PLUGIN_PATH . 'includes/class-wcl-rules.php';
        require_once WCL_PLUGIN_PATH . 'includes/class-wcl-frontend.php';
        
        // Admin classes
        if (is_admin()) {
            require_once WCL_PLUGIN_PATH . 'includes/admin/class-wcl-admin.php';
            require_once WCL_PLUGIN_PATH . 'includes/admin/class-wcl-admin-option-sets.php';
            require_once WCL_PLUGIN_PATH . 'includes/admin/class-wcl-admin-products.php';
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(WCL_PLUGIN_FILE, array('WCL_Install', 'install'));
        register_deactivation_hook(WCL_PLUGIN_FILE, array('WCL_Install', 'deactivate'));

        // Init classes
        add_action('init', array($this, 'init_classes'));
        
        // Load text domain
        add_action('init', array($this, 'load_textdomain'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Initialize plugin classes
     */
    public function init_classes() {
        // Initialize core classes
        WCL_Option_Sets::get_instance();
        WCL_Options::get_instance();
        WCL_Rules::get_instance();
        WCL_Frontend::get_instance();
        
        // Initialize admin classes
        if (is_admin()) {
            WCL_Admin::get_instance();
            WCL_Admin_Option_Sets::get_instance();
            WCL_Admin_Products::get_instance();
        }
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('woo-conditional-logic', false, dirname(WCL_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        if (is_product() || is_shop() || is_product_category()) {
            wp_enqueue_script(
                'wcl-frontend',
                WCL_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                WCL_VERSION,
                true
            );

            wp_enqueue_style(
                'wcl-frontend',
                WCL_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                WCL_VERSION
            );

            wp_localize_script('wcl-frontend', 'wcl_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wcl_nonce'),
            ));
        }
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        $admin_pages = array(
            'woocommerce_page_wcl-option-sets',
            'product',
            'edit-product'
        );

        if (in_array($hook, $admin_pages) || strpos($hook, 'wcl-') !== false) {
            wp_enqueue_script(
                'wcl-admin',
                WCL_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'jquery-ui-sortable'),
                WCL_VERSION,
                true
            );

            wp_enqueue_style(
                'wcl-admin',
                WCL_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                WCL_VERSION
            );

            wp_localize_script('wcl-admin', 'wcl_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wcl_admin_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Are you sure you want to delete this item?', 'woo-conditional-logic'),
                    'save_changes' => __('Please save your changes before adding rules.', 'woo-conditional-logic'),
                ),
            ));
        }
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        echo '<div class="error"><p><strong>' . 
             sprintf(
                 esc_html__('%s requires WooCommerce to be installed and active.', 'woo-conditional-logic'),
                 '<strong>WooCommerce Conditional Logic</strong>'
             ) . 
             '</strong></p></div>';
    }
}

/**
 * Initialize the plugin
 */
function WCL() {
    return WooCommerce_Conditional_Logic::get_instance();
}

// Start the plugin
WCL();
