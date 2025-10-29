<?php
if (!defined('ABSPATH')) {
    exit;
}

class TFSP_Attendance_Export {
    
    public function __construct() {
        add_action('wp_ajax_tfsp_export_student_attendance', array($this, 'export_student_attendance'));
        add_action('wp_ajax_tfsp_export_week_attendance', array($this, 'export_week_attendance'));
    }
    
    public function export_student_attendance() {
        if (!current_user_can('export_attendance')) {
            wp_die(__('Insufficient permissions', 'tfsp'));
        }
        
        if (!wp_verify_nonce($_GET['nonce'], 'export_attendance')) {
            wp_die(__('Security check failed', 'tfsp'));
        }
        
        $student_id = intval($_GET['student_id']);
        $from = sanitize_text_field($_GET['from'] ?? date('Y-m-d', strtotime('-30 days')));
        $to = sanitize_text_field($_GET['to'] ?? date('Y-m-d'));
        
        $student = get_user_by('ID', $student_id);
        if (!$student) {
            wp_die(__('Student not found', 'tfsp'));
        }
        
        global $wpdb;
        
        $records = $wpdb->get_results($wpdb->prepare(
            "SELECT s.session_date, s.start_time, s.end_time, s.subject, s.topic,
                    ar.status, ar.check_in_at, ar.check_out_at, ar.notes
             FROM {$wpdb->prefix}tfsp_sessions s
             LEFT JOIN {$wpdb->prefix}tfsp_attendance_records ar ON ar.session_id = s.id AND ar.student_id = %d
             WHERE s.session_date BETWEEN %s AND %s
             ORDER BY s.session_date, s.start_time",
            $student_id, $from, $to
        ));
        
        $filename = sprintf('attendance-%s-%s-%s.csv', 
            sanitize_file_name($student->display_name), 
            $from, 
            $to
        );
        
        $this->output_csv($filename, $records, array(
            'Student Name' => $student->display_name,
            'Student Email' => $student->user_email,
            'Session Date' => 'session_date',
            'Start Time' => 'start_time',
            'End Time' => 'end_time',
            'Subject' => 'subject',
            'Topic' => 'topic',
            'Status' => 'status',
            'Check In' => 'check_in_at',
            'Check Out' => 'check_out_at',
            'Notes' => 'notes'
        ));
    }
    
    public function export_week_attendance() {
        if (!current_user_can('export_attendance')) {
            wp_die(__('Insufficient permissions', 'tfsp'));
        }
        
        if (!wp_verify_nonce($_GET['nonce'], 'export_attendance')) {
            wp_die(__('Security check failed', 'tfsp'));
        }
        
        $week_start = sanitize_text_field($_GET['week_start']);
        $week_end = date('Y-m-d', strtotime('+6 days', strtotime($week_start)));
        
        global $wpdb;
        
        $records = $wpdb->get_results($wpdb->prepare(
            "SELECT u.display_name, u.user_email, s.session_date, s.start_time, s.end_time, 
                    s.subject, s.topic, ar.status, ar.check_in_at, ar.check_out_at, ar.notes
             FROM {$wpdb->prefix}tfsp_sessions s
             CROSS JOIN {$wpdb->users} u
             LEFT JOIN {$wpdb->prefix}tfsp_attendance_records ar ON ar.session_id = s.id AND ar.student_id = u.ID
             WHERE s.session_date BETWEEN %s AND %s
             AND u.ID IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'wp_capabilities' AND meta_value LIKE '%%subscriber%%')
             ORDER BY u.display_name, s.session_date, s.start_time",
            $week_start, $week_end
        ));
        
        $filename = sprintf('week-attendance-%s.csv', $week_start);
        
        $this->output_csv($filename, $records, array(
            'Student Name' => 'display_name',
            'Student Email' => 'user_email',
            'Session Date' => 'session_date',
            'Start Time' => 'start_time',
            'End Time' => 'end_time',
            'Subject' => 'subject',
            'Topic' => 'topic',
            'Status' => 'status',
            'Check In' => 'check_in_at',
            'Check Out' => 'check_out_at',
            'Notes' => 'notes'
        ));
    }
    
    private function output_csv($filename, $records, $columns) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Write BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write header
        fputcsv($output, array_keys($columns));
        
        // Write data
        foreach ($records as $record) {
            $row = array();
            foreach ($columns as $header => $field) {
                if (is_string($field)) {
                    $row[] = $record->$field ?? '';
                } else {
                    $row[] = $field; // Static value
                }
            }
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}
