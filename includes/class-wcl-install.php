<?php
/**
 * Installation and database setup
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCL_Install {

    /**
     * Install the plugin
     */
    public static function install() {
        if (!defined('WCL_INSTALLING')) {
            define('WCL_INSTALLING', true);
        }

        self::create_tables();
        self::create_default_option_types();
        
        // Set version
        update_option('wcl_version', WCL_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        // Clean up if needed
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Option sets table
        $table_option_sets = $wpdb->prefix . 'wcl_option_sets';
        $sql_option_sets = "CREATE TABLE $table_option_sets (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY name (name)
        ) $charset_collate;";

        // Options table
        $table_options = $wpdb->prefix . 'wcl_options';
        $sql_options = "CREATE TABLE $table_options (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            option_set_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            required tinyint(1) DEFAULT 0,
            multiple tinyint(1) DEFAULT 0,
            min_selection int(11) DEFAULT 0,
            max_selection int(11) DEFAULT 0,
            description text,
            position int(11) DEFAULT 0,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY option_set_id (option_set_id),
            KEY type (type),
            KEY position (position)
        ) $charset_collate;";

        // Option values table
        $table_option_values = $wpdb->prefix . 'wcl_option_values';
        $sql_option_values = "CREATE TABLE $table_option_values (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            option_id bigint(20) NOT NULL,
            label varchar(255) NOT NULL,
            value varchar(255) NOT NULL,
            price_modifier decimal(10,2) DEFAULT 0.00,
            price_type varchar(20) DEFAULT 'fixed',
            description text,
            image_url varchar(500),
            color_hex varchar(7),
            position int(11) DEFAULT 0,
            is_default tinyint(1) DEFAULT 0,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY option_id (option_id),
            KEY position (position)
        ) $charset_collate;";

        // Rules table
        $table_rules = $wpdb->prefix . 'wcl_rules';
        $sql_rules = "CREATE TABLE $table_rules (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            option_set_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            condition_json text NOT NULL,
            action_json text NOT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY option_set_id (option_set_id)
        ) $charset_collate;";

        // Product option sets table (many-to-many relationship)
        $table_product_option_sets = $wpdb->prefix . 'wcl_product_option_sets';
        $sql_product_option_sets = "CREATE TABLE $table_product_option_sets (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            option_set_id bigint(20) NOT NULL,
            position int(11) DEFAULT 0,
            replace_variations tinyint(1) DEFAULT 0,
            hide_original_options tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY product_option_set (product_id, option_set_id),
            KEY product_id (product_id),
            KEY option_set_id (option_set_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_option_sets);
        dbDelta($sql_options);
        dbDelta($sql_option_values);
        dbDelta($sql_rules);
        dbDelta($sql_product_option_sets);
    }

    /**
     * Create default option types
     */
    private static function create_default_option_types() {
        $option_types = array(
            'checkbox' => array(
                'label' => __('Checkbox', 'woo-conditional-logic'),
                'description' => __('Allow multiple selections with checkboxes', 'woo-conditional-logic'),
                'multiple' => true,
                'has_price' => true,
                'has_image' => false,
                'has_color' => false
            ),
            'radio' => array(
                'label' => __('Radio Button', 'woo-conditional-logic'),
                'description' => __('Allow single selection with radio buttons', 'woo-conditional-logic'),
                'multiple' => false,
                'has_price' => true,
                'has_image' => false,
                'has_color' => false
            ),
            'dropdown' => array(
                'label' => __('Dropdown', 'woo-conditional-logic'),
                'description' => __('Dropdown selection list', 'woo-conditional-logic'),
                'multiple' => false,
                'has_price' => true,
                'has_image' => false,
                'has_color' => false
            ),
            'swatch' => array(
                'label' => __('Swatch', 'woo-conditional-logic'),
                'description' => __('Color or image swatches', 'woo-conditional-logic'),
                'multiple' => false,
                'has_price' => true,
                'has_image' => true,
                'has_color' => true
            ),
            'multi_swatch' => array(
                'label' => __('Multi-select Swatch', 'woo-conditional-logic'),
                'description' => __('Multiple color or image swatches', 'woo-conditional-logic'),
                'multiple' => true,
                'has_price' => true,
                'has_image' => true,
                'has_color' => true
            ),
            'button' => array(
                'label' => __('Button', 'woo-conditional-logic'),
                'description' => __('Button selection', 'woo-conditional-logic'),
                'multiple' => false,
                'has_price' => true,
                'has_image' => false,
                'has_color' => false
            ),
            'text' => array(
                'label' => __('Text Field', 'woo-conditional-logic'),
                'description' => __('Single line text input', 'woo-conditional-logic'),
                'multiple' => false,
                'has_price' => true,
                'has_image' => false,
                'has_color' => false
            ),
            'textarea' => array(
                'label' => __('Multi-line Text', 'woo-conditional-logic'),
                'description' => __('Multi-line text input', 'woo-conditional-logic'),
                'multiple' => false,
                'has_price' => true,
                'has_image' => false,
                'has_color' => false
            ),
            'number' => array(
                'label' => __('Number Field', 'woo-conditional-logic'),
                'description' => __('Numeric input field', 'woo-conditional-logic'),
                'multiple' => false,
                'has_price' => true,
                'has_image' => false,
                'has_color' => false
            ),
            'date' => array(
                'label' => __('Date Picker', 'woo-conditional-logic'),
                'description' => __('Date selection with calendar', 'woo-conditional-logic'),
                'multiple' => false,
                'has_price' => true,
                'has_image' => false,
                'has_color' => false
            ),
            'file' => array(
                'label' => __('File Upload', 'woo-conditional-logic'),
                'description' => __('File upload field', 'woo-conditional-logic'),
                'multiple' => false,
                'has_price' => true,
                'has_image' => false,
                'has_color' => false
            )
        );

        update_option('wcl_option_types', $option_types);
    }
}
