<?php
/**
 * Admin Option Sets functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCL_Admin_Option_Sets {

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
        // Constructor implementation if needed
    }
}
