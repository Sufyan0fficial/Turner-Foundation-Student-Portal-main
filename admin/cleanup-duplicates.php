<?php
// Remove duplicate progress entries
require_once('../../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Unauthorized');
}

global $wpdb;

echo "<h2>Cleaning Up Duplicate Progress Entries</h2>";

// Get all students
$students = get_users(array('role' => 'subscriber'));

foreach ($students as $student) {
    echo "<br><strong>{$student->display_name}</strong><br>";
    
    // Get all progress for this student
    $all_progress = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d ORDER BY id",
        $student->ID
    ));
    
    echo "Total entries: " . count($all_progress) . "<br>";
    
    // Keep track of which step_keys we've seen
    $seen_keys = array();
    $kept = 0;
    $deleted = 0;
    
    foreach ($all_progress as $progress) {
        if (in_array($progress->step_key, $seen_keys)) {
            // Duplicate - delete it
            $wpdb->delete(
                $wpdb->prefix . 'tfsp_student_progress',
                array('id' => $progress->id),
                array('%d')
            );
            $deleted++;
        } else {
            // First occurrence - keep it
            $seen_keys[] = $progress->step_key;
            $kept++;
        }
    }
    
    echo "✓ Kept: {$kept} | Deleted: {$deleted}<br>";
}

echo "<br><h3>✅ Cleanup Complete!</h3>";
echo "<p><a href='?view=dashboard'>← Back to Dashboard</a></p>";
