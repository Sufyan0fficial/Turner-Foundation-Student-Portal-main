<?php
if (!defined('ABSPATH')) {
    exit;
}

class TFSP_Attendance_REST {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        $namespace = 'tfsp/v1';
        
        register_rest_route($namespace, '/attendance/sessions', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_sessions'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route($namespace, '/attendance/grid', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_grid_data'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route($namespace, '/attendance/bulk-upsert', array(
            'methods' => 'POST',
            'callback' => array($this, 'bulk_upsert'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route($namespace, '/student360/(?P<id>\d+)/attendance', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_student_attendance'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route($namespace, '/student360/(?P<id>\d+)/documents', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_student_documents'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route($namespace, '/student360/documents/update', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_document_status'),
            'permission_callback' => array($this, 'check_permissions')
        ));
    }
    
    public function check_permissions() {
        return current_user_can('manage_attendance');
    }
    
    public function get_sessions($request) {
        global $wpdb;

        $class_id = intval($request->get_param('class_id') ?: 1);
        $from = sanitize_text_field($request->get_param('from'));
        $to = sanitize_text_field($request->get_param('to'));
        
        $sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT id, session_date, start_time, end_time, subject, topic, is_postponed
             FROM {$wpdb->prefix}tfsp_sessions
             WHERE class_id = %d AND session_date BETWEEN %s AND %s
             ORDER BY session_date, start_time",
            $class_id, $from, $to
        ));

        // If no sessions are found for the requested week, seed a Thursday session automatically
        if (empty($sessions) && !empty($from)) {
            $week_start = $from;
            // Compute Thursday of this week based on provided week_start (assumed Monday)
            $thursday = date('Y-m-d', strtotime($week_start . ' +3 days'));

            // Ensure only one session per date
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}tfsp_sessions WHERE class_id = %d AND session_date = %s",
                $class_id, $thursday
            ));
            if (!$existing) {
                $wpdb->insert(
                    $wpdb->prefix . 'tfsp_sessions',
                    array(
                        'class_id' => $class_id,
                        'session_date' => $thursday,
                        'start_time' => '16:00:00',
                        'end_time' => '17:00:00',
                        'subject' => 'YCAM Weekly Session',
                        'topic' => 'Program Meeting',
                        'is_postponed' => 0,
                        'created_at' => current_time('mysql')
                    ),
                    array('%d','%s','%s','%s','%s','%s','%d','%s')
                );
            }
            // Re-fetch sessions
            $sessions = $wpdb->get_results($wpdb->prepare(
                "SELECT id, session_date, start_time, end_time, subject, topic, is_postponed
                 FROM {$wpdb->prefix}tfsp_sessions
                 WHERE class_id = %d AND session_date BETWEEN %s AND %s
                 ORDER BY session_date, start_time",
                $class_id, $from, $to
            ));
        }
        
        return rest_ensure_response($sessions);
    }
    
    public function get_grid_data($request) {
        global $wpdb;
        
        $class_id = intval($request->get_param('class_id') ?: 1);
        $week_start = sanitize_text_field($request->get_param('week_start'));
        $week_end = date('Y-m-d', strtotime('+6 days', strtotime($week_start)));
        
        // Get students
        $students = get_users(array(
            'role' => 'subscriber',
            'fields' => array('ID', 'display_name', 'user_email')
        ));
        
        // Get sessions for week
        $sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT id, session_date, start_time, subject
             FROM {$wpdb->prefix}tfsp_sessions
             WHERE class_id = %d AND session_date BETWEEN %s AND %s
             ORDER BY session_date, start_time",
            $class_id, $week_start, $week_end
        ));
        
        // Get attendance records
        $attendance = $wpdb->get_results($wpdb->prepare(
            "SELECT ar.student_id, ar.session_id, ar.status, ar.notes
             FROM {$wpdb->prefix}tfsp_attendance_records ar
             JOIN {$wpdb->prefix}tfsp_sessions s ON s.id = ar.session_id
             WHERE s.class_id = %d AND s.session_date BETWEEN %s AND %s",
            $class_id, $week_start, $week_end
        ));
        
        // Format attendance as sparse matrix
        $attendance_map = array();
        foreach ($attendance as $record) {
            $attendance_map[$record->student_id][$record->session_id] = array(
                'status' => $record->status,
                'notes' => $record->notes
            );
        }
        
        return rest_ensure_response(array(
            'students' => $students,
            'sessions' => $sessions,
            'attendance' => $attendance_map
        ));
    }
    
    public function bulk_upsert($request) {
        global $wpdb;
        
        $records = $request->get_json_params();
        if (!is_array($records)) {
            return new WP_Error('invalid_data', __('Invalid data format', 'tfsp'), array('status' => 400));
        }
        
        $current_user_id = get_current_user_id();
        $success_count = 0;
        
        foreach ($records as $record) {
            $session_id = intval($record['session_id']);
            $student_id = intval($record['student_id']);
            $status = sanitize_text_field($record['status']);
            $notes = sanitize_text_field($record['notes'] ?? '');
            
            if (!in_array($status, array('present', 'excused_absence', 'did_not_attend', 'postponed', 'late', 'remote'))) {
                continue;
            }
            
            $result = $wpdb->replace(
                $wpdb->prefix . 'tfsp_attendance_records',
                array(
                    'session_id' => $session_id,
                    'student_id' => $student_id,
                    'status' => $status,
                    'notes' => $notes,
                    'marked_by' => $current_user_id,
                    'marked_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%d', '%s')
            );
            
            if ($result !== false) {
                $success_count++;
            }
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'updated' => $success_count
        ));
    }
    
    public function get_student_attendance($request) {
        global $wpdb;
        
        $student_id = intval($request['id']);
        $from = sanitize_text_field($request->get_param('from') ?: date('Y-m-d', strtotime('-30 days')));
        $to = sanitize_text_field($request->get_param('to') ?: date('Y-m-d'));
        
        // Get attendance records
        $records = $wpdb->get_results($wpdb->prepare(
            "SELECT s.session_date, s.subject, ar.status, ar.notes
             FROM {$wpdb->prefix}tfsp_attendance_records ar
             JOIN {$wpdb->prefix}tfsp_sessions s ON s.id = ar.session_id
             WHERE ar.student_id = %d AND s.session_date BETWEEN %s AND %s
             ORDER BY s.session_date DESC
             LIMIT 20",
            $student_id, $from, $to
        ));
        
        // Calculate percentage
        $total_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_sessions
             WHERE session_date BETWEEN %s AND %s AND is_postponed = 0",
            $from, $to
        ));
        
        $present_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_attendance_records ar
             JOIN {$wpdb->prefix}tfsp_sessions s ON s.id = ar.session_id
             WHERE ar.student_id = %d AND s.session_date BETWEEN %s AND %s
             AND ar.status IN ('present', 'late', 'remote') AND s.is_postponed = 0",
            $student_id, $from, $to
        ));
        
        $percentage = $total_sessions > 0 ? round(($present_count / $total_sessions) * 100) : 0;
        
        return rest_ensure_response(array(
            'records' => $records,
            'stats' => array(
                'percentage' => $percentage,
                'present' => $present_count,
                'total' => $total_sessions
            )
        ));
    }
    
    public function get_student_documents($request) {
        global $wpdb;
        
        $student_id = intval($request['id']);
        
        $documents = $wpdb->get_results($wpdb->prepare(
            "SELECT id, doc_type, status, notes, updated_at
             FROM {$wpdb->prefix}tfsp_student_documents
             WHERE student_id = %d
             ORDER BY updated_at DESC",
            $student_id
        ));
        
        return rest_ensure_response($documents);
    }
    
    public function update_document_status($request) {
        global $wpdb;
        
        $data = $request->get_json_params();
        $doc_id = intval($data['id']);
        $status = sanitize_text_field($data['status']);
        $notes = sanitize_text_field($data['notes'] ?? '');
        
        if (!in_array($status, array('Submitted', 'Accepted', 'Sent back to student for further development'))) {
            return new WP_Error('invalid_status', __('Invalid status', 'tfsp'), array('status' => 400));
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'tfsp_student_documents',
            array(
                'status' => $status,
                'notes' => $notes
            ),
            array('id' => $doc_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            return rest_ensure_response(array('success' => true));
        }
        
        return new WP_Error('update_failed', __('Failed to update document', 'tfsp'), array('status' => 500));
    }
}
