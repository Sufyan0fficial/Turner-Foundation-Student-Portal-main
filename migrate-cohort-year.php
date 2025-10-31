<?php
/**
 * Migration script to add cohort_year field to existing installations
 * Run this once after updating the plugin
 */

// Load WordPress
require_once(dirname(__FILE__, 4) . '/wp-load.php');

// Check if user has admin privileges
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

// Load the database activator
require_once(dirname(__FILE__) . '/includes/class-database-activator.php');

echo "<h2>YCAM Student Portal - Cohort Year Migration</h2>";

try {
    // Run the migration
    TFSP_Database_Activator::maybe_add_cohort_year_field();
    
    echo "<p style='color: green;'>✅ Migration completed successfully!</p>";
    echo "<p>The cohort_year field has been added to the tfsp_students table.</p>";
    echo "<p>Students can now select their cohort year during registration, and it will be displayed in the admin dashboard.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Migration failed: " . $e->getMessage() . "</p>";
}

echo "<p><a href='" . admin_url() . "'>← Back to WordPress Admin</a></p>";
?>
