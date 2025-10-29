<?php
// Standardize student progress data to 10 steps
require_once('../../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Unauthorized');
}

global $wpdb;

// Define the 10 standard roadmap steps
$standard_steps = array(
    'academic_resume' => 'Academic Resume',
    'personal_essay' => 'Personal Essay',
    'recommendation_letters' => 'Recommendation Letters',
    'transcript' => 'Transcript',
    'financial_aid' => 'Financial Aid',
    'community_service' => 'Community Service',
    'college_list' => 'Create Interest List of Colleges',
    'college_tours' => 'College Tours',
    'fafsa' => 'FAFSA',
    'college_admissions_tests' => 'College Admissions Tests'
);

// Get all students
$students = get_users(array('role' => 'subscriber'));

echo "<h2>Standardizing Student Progress Data</h2>";
echo "<p>Ensuring all students have the 10 standard roadmap steps...</p>";

foreach ($students as $student) {
    echo "<br><strong>Processing: {$student->display_name}</strong><br>";
    
    // Get existing progress
    $existing = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d",
        $student->ID
    ), OBJECT_K);
    
    echo "- Current steps: " . count($existing) . "<br>";
    
    $step_order = 1;
    foreach ($standard_steps as $step_key => $step_name) {
        if (isset($existing[$step_key])) {
            // Update existing step
            $wpdb->update(
                $wpdb->prefix . 'tfsp_student_progress',
                array(
                    'step_order' => $step_order,
                    'step_description' => $step_name
                ),
                array('id' => $existing[$step_key]->id),
                array('%d', '%s'),
                array('%d')
            );
            echo "  ✓ Updated: {$step_name}<br>";
        } else {
            // Insert missing step
            $wpdb->insert(
                $wpdb->prefix . 'tfsp_student_progress',
                array(
                    'student_id' => $student->ID,
                    'step_key' => $step_key,
                    'step_order' => $step_order,
                    'step_description' => $step_name,
                    'status' => 'not_started'
                ),
                array('%d', '%s', '%d', '%s', '%s')
            );
            echo "  + Added: {$step_name}<br>";
        }
        $step_order++;
    }
    
    // Delete any extra steps not in standard list
    $standard_keys = array_keys($standard_steps);
    $placeholders = implode(',', array_fill(0, count($standard_keys), '%s'));
    $query = $wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}tfsp_student_progress 
         WHERE student_id = %d AND step_key NOT IN ($placeholders)",
        array_merge(array($student->ID), $standard_keys)
    );
    $deleted = $wpdb->query($query);
    if ($deleted > 0) {
        echo "  - Removed {$deleted} old/invalid steps<br>";
    }
    
    echo "✅ Complete - Now has 10 standard steps<br>";
}

echo "<br><h3>✅ All Done!</h3>";
echo "<p>All students now have the standardized 10-step roadmap.</p>";
echo "<p><a href='?view=dashboard'>← Back to Dashboard</a></p>";
