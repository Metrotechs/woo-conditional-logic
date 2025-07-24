<?php
/**
 * WooCommerce Conditional Logic Debug Tool
 * 
 * Add this to your WordPress URL: yoursite.com/wp-content/plugins/woo-conditional-logic/debug.php
 * Or run it from the plugin directory to check installation status.
 */

// Load WordPress
$wp_load_paths = array(
    '../../../wp-load.php',
    '../../../../wp-load.php',
    '../../../../../wp-load.php'
);

foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

if (!defined('ABSPATH')) {
    die('WordPress not found. Run this from your plugin directory or add the correct path to wp-load.php');
}

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied. Please login as administrator.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>WCL Debug Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .check { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .code { background: #f5f5f5; padding: 10px; margin: 10px 0; font-family: monospace; }
    </style>
</head>
<body>
    <h1>WooCommerce Conditional Logic Debug Report</h1>
    
    <?php
    // Check WordPress and WooCommerce
    echo '<h2>System Checks</h2>';
    echo '<table>';
    
    echo '<tr><td>WordPress Version</td><td>' . get_bloginfo('version') . '</td></tr>';
    
    if (class_exists('WooCommerce')) {
        echo '<tr><td>WooCommerce</td><td class="check">✓ Active (v' . WC()->version . ')</td></tr>';
    } else {
        echo '<tr><td>WooCommerce</td><td class="error">✗ Not Active</td></tr>';
    }
    
    // Check plugin activation
    if (class_exists('WooCommerce_Conditional_Logic')) {
        echo '<tr><td>WCL Plugin</td><td class="check">✓ Active</td></tr>';
    } else {
        echo '<tr><td>WCL Plugin</td><td class="error">✗ Not Active</td></tr>';
    }
    
    // Check database tables
    global $wpdb;
    $tables = array(
        'wcl_option_sets',
        'wcl_options', 
        'wcl_option_values',
        'wcl_rules',
        'wcl_product_option_sets'
    );
    
    echo '<tr><td colspan="2"><strong>Database Tables</strong></td></tr>';
    foreach ($tables as $table) {
        $table_name = $wpdb->prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        $status = $exists ? '<span class="check">✓ Exists</span>' : '<span class="error">✗ Missing</span>';
        echo "<tr><td>$table_name</td><td>$status</td></tr>";
        
        if ($exists && $table == 'wcl_option_sets') {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            echo "<tr><td>&nbsp;&nbsp;→ Record Count</td><td>$count option sets</td></tr>";
        }
    }
    
    echo '</table>';
    
    // Check admin menu
    echo '<h2>Admin Menu Check</h2>';
    if (is_admin()) {
        $menu_url = admin_url('admin.php?page=wcl-option-sets');
        echo '<p><strong>Admin URL:</strong> <a href="' . $menu_url . '">' . $menu_url . '</a></p>';
    }
    
    // Check file permissions
    echo '<h2>File System</h2>';
    echo '<table>';
    $plugin_path = WP_PLUGIN_DIR . '/woo-conditional-logic/';
    echo '<tr><td>Plugin Directory</td><td>' . $plugin_path . '</td></tr>';
    echo '<tr><td>Directory Writable</td><td>' . (is_writable($plugin_path) ? '<span class="check">✓ Yes</span>' : '<span class="warning">⚠ No</span>') . '</td></tr>';
    echo '</table>';
    
    // Quick option set creation test
    echo '<h2>Quick Test</h2>';
    if (isset($_POST['create_test_set'])) {
        if (class_exists('WCL_Option_Sets')) {
            $option_sets = WCL_Option_Sets::get_instance();
            $result = $option_sets->create_option_set(array(
                'name' => 'Debug Test Set - ' . date('Y-m-d H:i:s'),
                'description' => 'Test option set created by debug tool',
                'status' => 'active'
            ));
            
            if (is_wp_error($result)) {
                echo '<p class="error">✗ Test failed: ' . $result->get_error_message() . '</p>';
            } else {
                echo '<p class="check">✓ Test option set created successfully! ID: ' . $result . '</p>';
                echo '<p><a href="' . admin_url('admin.php?page=wcl-option-sets') . '">Go to Conditional Logic admin</a></p>';
            }
        } else {
            echo '<p class="error">✗ WCL_Option_Sets class not found</p>';
        }
    } else {
        echo '<form method="post">';
        echo '<button type="submit" name="create_test_set" style="background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer;">Create Test Option Set</button>';
        echo '</form>';
    }
    
    // Show debug info
    echo '<h2>Debug Information</h2>';
    echo '<div class="code">';
    echo 'WordPress Debug: ' . (WP_DEBUG ? 'Enabled' : 'Disabled') . '<br>';
    echo 'Current User Can Manage WooCommerce: ' . (current_user_can('manage_woocommerce') ? 'Yes' : 'No') . '<br>';
    echo 'Plugin Constants:<br>';
    if (defined('WCL_PLUGIN_PATH')) echo '&nbsp;&nbsp;WCL_PLUGIN_PATH: ' . WCL_PLUGIN_PATH . '<br>';
    if (defined('WCL_PLUGIN_URL')) echo '&nbsp;&nbsp;WCL_PLUGIN_URL: ' . WCL_PLUGIN_URL . '<br>';
    if (defined('WCL_VERSION')) echo '&nbsp;&nbsp;WCL_VERSION: ' . WCL_VERSION . '<br>';
    echo '</div>';
    
    ?>
    
    <h2>Next Steps</h2>
    <ol>
        <li>If database tables are missing, deactivate and reactivate the plugin</li>
        <li>If WooCommerce is not active, activate it first</li>
        <li>Try the "Create Test Option Set" button above</li>
        <li>Go to <strong>WooCommerce → Conditional Logic</strong> in your admin menu</li>
        <li>Check your browser console for JavaScript errors</li>
    </ol>
    
</body>
</html>
