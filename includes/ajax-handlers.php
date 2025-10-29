<?php
/**
 * AJAX Handlers for YCAM Portal
 */

// Progress Update Handler
add_action('wp_ajax_tfsp_update_progress', 'tfsp_ajax_update_progress');

function tfsp_ajax_update_progress() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }
    
    check_ajax_referer('tfsp_progress', 'nonce');
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : get_current_user_id();
    $step = sanitize_text_field($_POST['step']);
    $status = sanitize_text_field($_POST['status']);
    
    global $wpdb;
    $table = $wpdb->prefix . 'tfsp_student_progress';
    
    // Use step_key column
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE student_id = %d AND step_key = %s",
        $user_id, $step
    ));
    
    if ($exists) {
        $result = $wpdb->update(
            $table,
            array('status' => $status, 'updated_at' => current_time('mysql')),
            array('student_id' => $user_id, 'step_key' => $step)
        );
    } else {
        $result = $wpdb->insert(
            $table,
            array(
                'student_id' => $user_id,
                'step_key' => $step,
                'status' => $status,
                'updated_at' => current_time('mysql')
            )
        );
    }
    
    if ($result !== false) {
        wp_send_json_success('Progress updated');
    } else {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
    }
}

// Roadmap Status Update Handler (for student-dashboard-exact.php)
add_action('wp_ajax_update_roadmap_status', 'tfsp_ajax_update_roadmap_status');
add_action('wp_ajax_nopriv_update_roadmap_status', 'tfsp_ajax_update_roadmap_status');

function tfsp_ajax_update_roadmap_status() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }
    
    check_ajax_referer('roadmap_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    $step_key = sanitize_text_field($_POST['step_key']);
    $status = sanitize_text_field($_POST['status']);
    
    global $wpdb;
    $table = $wpdb->prefix . 'tfsp_student_progress';
    
    // Use step_key column
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE student_id = %d AND step_key = %s",
        $user_id, $step_key
    ));
    
    if ($exists) {
        $result = $wpdb->update(
            $table,
            array('status' => $status, 'updated_at' => current_time('mysql')),
            array('student_id' => $user_id, 'step_key' => $step_key)
        );
    } else {
        $result = $wpdb->insert(
            $table,
            array(
                'student_id' => $user_id,
                'step_key' => $step_key,
                'status' => $status,
                'updated_at' => current_time('mysql')
            )
        );
    }
    
    if ($result !== false) {
        wp_send_json_success('Status updated');
    } else {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
    }
}
