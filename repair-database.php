<?php
/**
 * Database Repair Script for TFSP Plugin
 * Run this file directly in browser to repair database issues
 */

// Load WordPress
require_once('../../../wp-config.php');
require_once('../../../wp-load.php');

// Check if user is admin
if (!current_user_can('administrator')) {
    die('Access denied. Admin privileges required.');
}

echo "<h2>TFSP Database Repair Tool</h2>";

global $wpdb;

// 1. Repair WordPress tables first
echo "<h3>1. Repairing WordPress Core Tables...</h3>";
$repair_queries = array(
    "REPAIR TABLE {$wpdb->users}",
    "REPAIR TABLE {$wpdb->usermeta}",
    "REPAIR TABLE {$wpdb->posts}",
    "REPAIR TABLE {$wpdb->postmeta}",
    "REPAIR TABLE {$wpdb->options}"
);

foreach ($repair_queries as $query) {
    $result = $wpdb->query($query);
    echo "✓ " . str_replace($wpdb->prefix, '', $query) . "<br>";
}

// 2. Recreate TFSP tables
echo "<h3>2. Recreating TFSP Tables...</h3>";

// Include the database activator
require_once('includes/class-database-activator.php');

try {
    TFSP_Database_Activator::activate();
    echo "✅ All TFSP tables recreated successfully!<br>";
} catch (Exception $e) {
    echo "❌ Error creating tables: " . $e->getMessage() . "<br>";
}

// 3. Check table status
echo "<h3>3. Checking TFSP Tables Status...</h3>";
$tfsp_tables = array(
    'tfsp_students',
    'tfsp_documents', 
    'tfsp_student_progress',
    'tfsp_attendance_records',
    'tfsp_attendance',
    'tfsp_coach_sessions',
    'tfsp_messages',
    'tfsp_challenges',
    'tfsp_recommendations',
    'tfsp_upcoming_sessions',
    'tfsp_resources',
    'tfsp_settings',
    'tfsp_portal_admins',
    'tfsp_applications',
    'tfsp_meetings',
    'tfsp_progress',
    'tfsp_notifications',
    'tfsp_activity'
);

foreach ($tfsp_tables as $table) {
    $full_table = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'");
    if ($exists) {
        echo "✅ $table - EXISTS<br>";
    } else {
        echo "❌ $table - MISSING<br>";
    }
}

// 4. Create test admin user if needed
echo "<h3>4. Creating Test Admin User...</h3>";
$test_user = get_user_by('login', 'testadmin');
if (!$test_user) {
    $user_id = wp_create_user('testadmin', 'TestAdmin123!', 'admin@test.com');
    if (!is_wp_error($user_id)) {
        $user = new WP_User($user_id);
        $user->set_role('administrator');
        echo "✅ Test admin user created: testadmin / TestAdmin123!<br>";
    } else {
        echo "❌ Error creating test user: " . $user_id->get_error_message() . "<br>";
    }
} else {
    echo "✅ Test admin user already exists<br>";
}

// 5. Reset plugin options
echo "<h3>5. Resetting Plugin Options...</h3>";
delete_option('tfsp_plugin_activated');
add_option('tfsp_plugin_activated', '1');
echo "✅ Plugin options reset<br>";

echo "<h3>✅ Database Repair Complete!</h3>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>Go to WordPress Admin → Plugins</li>";
echo "<li>Deactivate and reactivate the TFSP plugin</li>";
echo "<li>Test the student portal functionality</li>";
echo "<li>Delete this repair file for security</li>";
echo "</ul>";

echo "<p><a href='/wp-admin/plugins.php' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Plugins</a></p>";
?>
