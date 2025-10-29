<?php
/**
 * Test script to check YCAM plugin features
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>YCAM Plugin Feature Test</h1>";

global $wpdb;

// Test 1: Check if database tables exist
echo "<h2>1. Database Tables Check</h2>";
$tables_to_check = [
    'tfsp_students',
    'tfsp_attendance_records', 
    'tfsp_sessions',
    'tfsp_resources',
    'tfsp_student_progress',
    'tfsp_documents',
    'tfsp_coach_sessions'
];

foreach ($tables_to_check as $table) {
    $table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    echo "<p>✓ Table $table_name: " . ($exists ? "EXISTS" : "MISSING") . "</p>";
}

// Test 2: Check resources functionality
echo "<h2>2. Resources Table Data</h2>";
$resources = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tfsp_resources WHERE is_active = 1");
echo "<p>Active resources count: " . count($resources) . "</p>";
if (count($resources) > 0) {
    echo "<ul>";
    foreach ($resources as $resource) {
        echo "<li>{$resource->name} ({$resource->type})</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No resources found. This might be why the resources section is empty.</p>";
}

// Test 3: Check attendance data
echo "<h2>3. Attendance System Check</h2>";
$sessions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tfsp_sessions ORDER BY session_date DESC LIMIT 5");
echo "<p>Recent sessions count: " . count($sessions) . "</p>";

$attendance_records = $wpdb->get_results("SELECT COUNT(*) as count FROM {$wpdb->prefix}tfsp_attendance_records");
echo "<p>Total attendance records: " . ($attendance_records[0]->count ?? 0) . "</p>";

// Test 4: Check students
echo "<h2>4. Students Check</h2>";
$students = $wpdb->get_results("SELECT COUNT(*) as count FROM {$wpdb->prefix}tfsp_students");
echo "<p>Total students: " . ($students[0]->count ?? 0) . "</p>";

// Test 5: Check WordPress users with subscriber role
$wp_students = get_users(['role' => 'subscriber']);
echo "<p>WordPress subscriber users: " . count($wp_students) . "</p>";

// Test 6: Plugin activation status
echo "<h2>5. Plugin Status</h2>";
$active_plugins = get_option('active_plugins');
$plugin_active = in_array('Turner-Foundation-Student-Portal-main/turner-foundation-student-portal.php', $active_plugins);
echo "<p>Plugin active: " . ($plugin_active ? "YES" : "NO") . "</p>";

// Test 7: Check shortcodes
echo "<h2>6. Shortcodes Check</h2>";
global $shortcode_tags;
$tfsp_shortcodes = ['student_registration', 'student_login', 'tfsp_student_dashboard', 'student_dashboard'];
foreach ($tfsp_shortcodes as $shortcode) {
    echo "<p>Shortcode [$shortcode]: " . (isset($shortcode_tags[$shortcode]) ? "REGISTERED" : "NOT REGISTERED") . "</p>";
}

echo "<h2>7. Recommendations</h2>";
echo "<ul>";
echo "<li>If resources are empty, add some test resources via the admin panel</li>";
echo "<li>If attendance is not showing, check if sessions are created and students have attendance records</li>";
echo "<li>Verify that the plugin is properly activated</li>";
echo "<li>Check if there are any PHP errors in the WordPress debug log</li>";
echo "</ul>";
?>
