<?php
/**
 * Attendance System Integration
 * Add this to your main plugin file's __construct() method:
 * require_once TFSP_PLUGIN_PATH . 'includes/attendance-integration.php';
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load attendance system classes
require_once TFSP_PLUGIN_PATH . 'includes/class-attendance-activator.php';
require_once TFSP_PLUGIN_PATH . 'includes/class-attendance-rest.php';
require_once TFSP_PLUGIN_PATH . 'includes/class-attendance-export.php';
require_once TFSP_PLUGIN_PATH . 'includes/class-student-360.php';
require_once TFSP_PLUGIN_PATH . 'includes/helpers.php';
require_once TFSP_PLUGIN_PATH . 'admin/class-admin-menu.php';

// Initialize attendance system
add_action('init', function() {
    // Initialize components
    new TFSP_Attendance_REST();
    new TFSP_Attendance_Export();
    new TFSP_Student_360();
    new TFSP_Admin_Menu();
    
    // Add shortcode for student profile
    add_shortcode('student_profile_attendance', array('TFSP_Student_360', 'render_student_profile_shortcode'));
});

// Hook into plugin activation
add_action('tfsp_plugin_activated', function() {
    TFSP_Attendance_Activator::activate();
    
    // Create some default sessions for current week
    tfsp_create_default_sessions();
});

// Add AJAX handlers for attendance updates
add_action('wp_ajax_update_attendance', function() {
    if (!current_user_can('manage_attendance')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'attendance_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    global $wpdb;
    
    $student_id = intval($_POST['student_id']);
    $date = sanitize_text_field($_POST['date']);
    $status = tfsp_sanitize_attendance_status($_POST['status']);
    
    if (!$status) {
        wp_send_json_error('Invalid status');
    }
    
    // Find or create session for this date
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}tfsp_sessions WHERE session_date = %s LIMIT 1",
        $date
    ));
    
    if (!$session) {
        // Create session
        $wpdb->insert(
            $wpdb->prefix . 'tfsp_sessions',
            array(
                'class_id' => 1,
                'session_date' => $date,
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'subject' => 'General'
            )
        );
        $session_id = $wpdb->insert_id;
    } else {
        $session_id = $session->id;
    }
    
    // Update attendance
    $result = $wpdb->replace(
        $wpdb->prefix . 'tfsp_attendance_records',
        array(
            'session_id' => $session_id,
            'student_id' => $student_id,
            'status' => $status,
            'marked_by' => get_current_user_id(),
            'marked_at' => current_time('mysql')
        )
    );
    
    if ($result !== false) {
        wp_send_json_success('Attendance updated');
    } else {
        wp_send_json_error('Failed to update attendance');
    }
});

// Add attendance data to existing external admin dashboard
add_action('wp_ajax_get_attendance_summary', function() {
    if (!current_user_can('manage_attendance')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    global $wpdb;
    
    $week_start = sanitize_text_field($_POST['week_start'] ?? date('Y-m-d', strtotime('monday this week')));
    $week_end = date('Y-m-d', strtotime('+6 days', strtotime($week_start)));
    
    $students = get_users(array('role' => 'subscriber'));
    $summary = array();
    
    foreach ($students as $student) {
        $percentage = tfsp_calculate_attendance_percentage($student->ID, $week_start, $week_end);
        $summary[] = array(
            'id' => $student->ID,
            'name' => $student->display_name,
            'email' => $student->user_email,
            'percentage' => $percentage
        );
    }
    
    wp_send_json_success($summary);
});

// Note: Previously injected a "Your Attendance (Last 30 days)" widget on the student dashboard via wp_footer.
// Per current requirements, that widget has been removed.
