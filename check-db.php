<?php
require_once(dirname(__FILE__, 4) . '/wp-load.php');

global $wpdb;
$table_name = $wpdb->prefix . 'tfsp_students';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
echo "Table exists: " . ($table_exists ? "YES" : "NO") . "\n";

if ($table_exists) {
    // Check table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo "\nTable columns:\n";
    foreach ($columns as $column) {
        echo "- " . $column->Field . " (" . $column->Type . ")\n";
    }
    
    // Check if cohort_year field exists
    $cohort_field = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'cohort_year'");
    echo "\nCohort year field exists: " . ($cohort_field ? "YES" : "NO") . "\n";
    
    if (!$cohort_field) {
        echo "Adding cohort_year field...\n";
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN cohort_year varchar(50) DEFAULT NULL AFTER classification");
        echo "Field added!\n";
    }
}
?>
