<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper functions for attendance system
 */

function tfsp_sanitize_attendance_status($status) {
    $allowed = array('present', 'excused_absence', 'did_not_attend', 'postponed', 'late', 'remote');
    return in_array($status, $allowed) ? $status : '';
}

function tfsp_sanitize_document_status($status) {
    $allowed = array('Submitted', 'Accepted', 'Sent back to student for further development');
    return in_array($status, $allowed) ? $status : 'Submitted';
}

function tfsp_get_attendance_policy() {
    global $wpdb;
    
    $policy = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}tfsp_attendance_policy WHERE id = 1");
    
    if (!$policy) {
        return array(
            'present_weight' => 1.00,
            'excused_absence_weight' => 1.00,
            'did_not_attend_weight' => 0.00,
            'late_weight' => 1.00,
            'remote_weight' => 1.00,
            'exclude_postponed_from_denominator' => 1
        );
    }
    
    return (array) $policy;
}

function tfsp_calculate_attendance_percentage($student_id, $from_date, $to_date) {
    global $wpdb;
    
    $policy = tfsp_get_attendance_policy();
    
    // Get total sessions (excluding postponed if policy says so)
    $where_postponed = $policy['exclude_postponed_from_denominator'] ? 'AND is_postponed = 0' : '';
    
    $total_sessions = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_sessions
         WHERE session_date BETWEEN %s AND %s $where_postponed",
        $from_date, $to_date
    ));

    // Helper to compute from legacy table
    $compute_from_legacy = function() use ($wpdb, $student_id, $from_date, $to_date) {
        $legacy_table = $wpdb->prefix . 'tfsp_attendance';
        if (!$wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $legacy_table))) {
            return null;
        }
        $date_col = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM $legacy_table LIKE %s", 'session_date')) ? 'session_date' : 'date';
        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $legacy_table WHERE student_id = %d AND $date_col BETWEEN %s AND %s",
            $student_id, $from_date, $to_date
        ));
        if ($total === 0) { return 0; }
        $present = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $legacy_table WHERE student_id = %d AND $date_col BETWEEN %s AND %s AND status IN ('present','excused')",
            $student_id, $from_date, $to_date
        ));
        return round(($present / max($total,1)) * 100);
    };

    // If no sessions exist in range, use legacy
    if ($total_sessions === 0) {
        $legacy = $compute_from_legacy();
        return $legacy !== null ? $legacy : 0;
    }

    // Sessions exist; compute weighted student attendance via records
    $attendance_records = $wpdb->get_results($wpdb->prepare(
        "SELECT ar.status, COUNT(*) as count
         FROM {$wpdb->prefix}tfsp_attendance_records ar
         JOIN {$wpdb->prefix}tfsp_sessions s ON s.id = ar.session_id
         WHERE ar.student_id = %d AND s.session_date BETWEEN %s AND %s $where_postponed
         GROUP BY ar.status",
        $student_id, $from_date, $to_date
    ));

    // If student has zero session-based marks in the range, fallback to legacy for this display
    if (empty($attendance_records)) {
        $legacy = $compute_from_legacy();
        if ($legacy !== null) { return $legacy; }
        return 0;
    }

    $weighted_total = 0;
    foreach ($attendance_records as $record) {
        $weight_key = $record->status . '_weight';
        $weight = isset($policy[$weight_key]) ? $policy[$weight_key] : 0;
        $weighted_total += ((int)$record->count) * $weight;
    }

    return $total_sessions > 0 ? round(($weighted_total / $total_sessions) * 100) : 0;
}

function tfsp_get_status_color($status) {
    $colors = array(
        'present' => '#28a745',
        'excused_absence' => '#007bff',
        'did_not_attend' => '#dc3545',
        'late' => '#ffc107',
        'remote' => '#6c757d',
        'postponed' => '#fd7e14'
    );
    
    return isset($colors[$status]) ? $colors[$status] : '#6c757d';
}

function tfsp_get_document_status_color($status) {
    $colors = array(
        'Submitted' => '#ffc107',
        'Accepted' => '#28a745',
        'Sent back to student for further development' => '#dc3545'
    );
    
    return isset($colors[$status]) ? $colors[$status] : '#6c757d';
}

function tfsp_create_default_sessions($class_id = 1, $start_date = null, $days = 5) {
    global $wpdb;
    
    if (!$start_date) {
        $start_date = date('Y-m-d', strtotime('monday this week'));
    }
    
    $subjects = array('Math', 'English', 'Science', 'History', 'Art');
    
    for ($i = 0; $i < $days; $i++) {
        $session_date = date('Y-m-d', strtotime("+$i days", strtotime($start_date)));
        
        // Skip weekends
        if (date('N', strtotime($session_date)) > 5) {
            continue;
        }
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_sessions WHERE class_id = %d AND session_date = %s",
            $class_id, $session_date
        ));
        
        if ($existing == 0) {
            $wpdb->insert(
                $wpdb->prefix . 'tfsp_sessions',
                array(
                    'class_id' => $class_id,
                    'session_date' => $session_date,
                    'start_time' => '09:00:00',
                    'end_time' => '10:00:00',
                    'subject' => $subjects[array_rand($subjects)]
                )
            );
        }
    }
}
