<?php
/**
 * Student Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class TFSP_Student {
    
    private $user_id;
    private $student_data;
    private $db;
    
    public function __construct($user_id) {
        $this->user_id = $user_id;
        $this->db = TFSP_Database::get_instance();
        $this->load_student_data();
    }
    
    private function load_student_data() {
        global $wpdb;
        
        $table_students = $wpdb->prefix . 'tfsp_students';
        
        $this->student_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_students WHERE user_id = %d",
            $this->user_id
        ));
        
        // If student doesn't exist, create a basic record
        if (!$this->student_data) {
            $this->create_student_record();
        }
    }
    
    private function create_student_record() {
        global $wpdb;
        
        $user = get_user_by('id', $this->user_id);
        if (!$user) return false;
        
        $table_students = $wpdb->prefix . 'tfsp_students';
        
        $student_id = 'TFSP' . str_pad($this->user_id, 6, '0', STR_PAD_LEFT);
        
        $result = $wpdb->insert(
            $table_students,
            array(
                'user_id' => $this->user_id,
                'student_id' => $student_id,
                'first_name' => $user->first_name ?: $user->display_name,
                'last_name' => $user->last_name ?: '',
                'email' => $user->user_email,
                'status' => 'active',
                'enrollment_date' => current_time('mysql'),
                'created_at' => current_time('mysql')
            )
        );
        
        if ($result) {
            $this->load_student_data();
            $this->initialize_student_applications();
        }
        
        return $result;
    }
    
    private function initialize_student_applications() {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        
        // Get template applications
        $templates = $wpdb->get_results(
            "SELECT * FROM $table_applications WHERE student_id = 0 AND status = 'template'"
        );
        
        foreach ($templates as $template) {
            // Create application for this student
            $wpdb->insert(
                $table_applications,
                array(
                    'student_id' => $this->student_data->id,
                    'application_type' => $template->application_type,
                    'title' => $template->title,
                    'description' => $template->description,
                    'requirements' => $template->requirements,
                    'priority' => $template->priority,
                    'status' => 'not_started',
                    'progress_percentage' => 0,
                    'application_deadline' => date('Y-m-d', strtotime('+90 days')), // Default 90 days
                    'created_at' => current_time('mysql')
                )
            );
        }
    }
    
    public function get_dashboard_data() {
        $data = array(
            'student_info' => $this->get_student_info(),
            'overall_progress' => $this->calculate_overall_progress(),
            'total_applications' => $this->get_total_applications(),
            'completed_applications' => $this->get_completed_applications_count(),
            'in_progress_applications' => $this->get_in_progress_applications_count(),
            'upcoming_deadlines' => $this->get_upcoming_deadlines(),
            'recent_activity' => $this->get_recent_activity(),
            'recent_documents' => $this->get_recent_documents(),
            'upcoming_meetings' => $this->get_upcoming_meetings()
        );
        
        return $data;
    }
    
    public function get_student_info() {
        if (!$this->student_data) return null;
        
        return array(
            'id' => $this->student_data->id,
            'student_id' => $this->student_data->student_id,
            'first_name' => $this->student_data->first_name,
            'last_name' => $this->student_data->last_name,
            'email' => $this->student_data->email,
            'phone' => $this->student_data->phone,
            'graduation_year' => $this->student_data->graduation_year,
            'gpa' => $this->student_data->gpa,
            'school_name' => $this->student_data->school_name
        );
    }
    
    public function calculate_overall_progress() {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        
        $avg_progress = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(progress_percentage) FROM $table_applications 
             WHERE student_id = %d AND status != 'template'",
            $this->student_data->id
        ));
        
        return round($avg_progress ?: 0);
    }
    
    public function get_total_applications() {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_applications 
             WHERE student_id = %d AND status != 'template'",
            $this->student_data->id
        ));
    }
    
    public function get_completed_applications_count() {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_applications 
             WHERE student_id = %d AND status = 'completed'",
            $this->student_data->id
        ));
    }
    
    public function get_in_progress_applications_count() {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_applications 
             WHERE student_id = %d AND status IN ('in_progress', 'review')",
            $this->student_data->id
        ));
    }
    
    public function get_upcoming_deadlines($limit = 5) {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        
        $deadlines = $wpdb->get_results($wpdb->prepare(
            "SELECT id, title, description, application_deadline as due_date, status, priority
             FROM $table_applications 
             WHERE student_id = %d 
             AND status != 'completed' 
             AND application_deadline >= CURDATE()
             ORDER BY application_deadline ASC, priority DESC
             LIMIT %d",
            $this->student_data->id,
            $limit
        ));
        
        return $deadlines ?: array();
    }
    
    public function get_recent_activity($limit = 10) {
        global $wpdb;
        
        $table_activity = $wpdb->prefix . 'tfsp_activity';
        
        $activities = $wpdb->get_results($wpdb->prepare(
            "SELECT action, description, created_at
             FROM $table_activity 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d",
            $this->user_id,
            $limit
        ));
        
        $formatted_activities = array();
        
        foreach ($activities as $activity) {
            $formatted_activities[] = array(
                'icon' => $this->get_activity_icon($activity->action),
                'title' => $this->format_activity_title($activity->action),
                'description' => $activity->description,
                'time_ago' => $this->time_ago($activity->created_at)
            );
        }
        
        // Add some default activities if none exist
        if (empty($formatted_activities)) {
            $formatted_activities = array(
                array(
                    'icon' => '🎯',
                    'title' => 'Welcome to Turner Foundation Student Portal',
                    'description' => 'Your college application journey starts here',
                    'time_ago' => 'Just now'
                )
            );
        }
        
        return $formatted_activities;
    }
    
    public function get_recent_documents($limit = 5) {
        return $this->db->get_student_documents($this->student_data->id);
    }
    
    public function get_upcoming_meetings($limit = 5) {
        return $this->db->get_student_meetings($this->student_data->id, 'scheduled');
    }
    
    public function get_applications() {
        return $this->db->get_student_applications($this->student_data->id);
    }
    
    public function update_profile($data) {
        global $wpdb;
        
        $table_students = $wpdb->prefix . 'tfsp_students';
        
        $allowed_fields = array(
            'first_name', 'last_name', 'phone', 'date_of_birth', 
            'graduation_year', 'gpa', 'school_name', 'counselor_name', 
            'counselor_email', 'parent_name', 'parent_email', 'parent_phone',
            'emergency_contact', 'emergency_phone', 'notes'
        );
        
        $update_data = array();
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = sanitize_text_field($data[$field]);
            }
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $update_data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            $table_students,
            $update_data,
            array('id' => $this->student_data->id)
        );
        
        if ($result !== false) {
            $this->load_student_data();
            $this->log_activity('profile_updated', 'Student profile updated');
        }
        
        return $result !== false;
    }
    
    public function get_progress_data() {
        $applications = $this->get_applications();
        $progress_data = array();
        
        foreach ($applications as $app) {
            $progress_data[] = array(
                'id' => $app->id,
                'title' => $app->title,
                'type' => $app->application_type,
                'status' => $app->status,
                'progress' => $app->progress_percentage,
                'deadline' => $app->application_deadline,
                'priority' => $app->priority,
                'college_name' => $app->college_name,
                'requirements' => $app->requirements ? json_decode($app->requirements, true) : array(),
                'started_date' => $app->started_date,
                'completed_date' => $app->completed_date
            );
        }
        
        return $progress_data;
    }
    
    public function log_activity($action, $description = null, $object_type = null, $object_id = null) {
        return $this->db->log_activity($this->user_id, $action, $object_type, $object_id, $description);
    }
    
    public function update_last_activity() {
        global $wpdb;
        
        $table_students = $wpdb->prefix . 'tfsp_students';
        
        $wpdb->update(
            $table_students,
            array('last_activity' => current_time('mysql')),
            array('id' => $this->student_data->id)
        );
    }
    
    private function get_activity_icon($action) {
        $icons = array(
            'profile_updated' => '👤',
            'document_uploaded' => '📤',
            'application_started' => '🚀',
            'application_completed' => '✅',
            'meeting_scheduled' => '📅',
            'progress_updated' => '📈',
            'login' => '🔐',
            'default' => '📝'
        );
        
        return isset($icons[$action]) ? $icons[$action] : $icons['default'];
    }
    
    private function format_activity_title($action) {
        $titles = array(
            'profile_updated' => 'Profile Updated',
            'document_uploaded' => 'Document Uploaded',
            'application_started' => 'Application Started',
            'application_completed' => 'Application Completed',
            'meeting_scheduled' => 'Meeting Scheduled',
            'progress_updated' => 'Progress Updated',
            'login' => 'Logged In',
            'default' => 'Activity'
        );
        
        return isset($titles[$action]) ? $titles[$action] : $titles['default'];
    }
    
    private function time_ago($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'Just now';
        if ($time < 3600) return floor($time/60) . ' minutes ago';
        if ($time < 86400) return floor($time/3600) . ' hours ago';
        if ($time < 2592000) return floor($time/86400) . ' days ago';
        if ($time < 31536000) return floor($time/2592000) . ' months ago';
        
        return floor($time/31536000) . ' years ago';
    }
    
    public function get_student_id() {
        return $this->student_data ? $this->student_data->id : null;
    }
    
    public function get_user_id() {
        return $this->user_id;
    }
    
    public function is_active() {
        return $this->student_data && $this->student_data->status === 'active';
    }
    
    public function get_completion_rate() {
        $total = $this->get_total_applications();
        $completed = $this->get_completed_applications_count();
        
        return $total > 0 ? round(($completed / $total) * 100) : 0;
    }
    
    public function get_next_deadline() {
        $deadlines = $this->get_upcoming_deadlines(1);
        return !empty($deadlines) ? $deadlines[0] : null;
    }
    
    public function has_overdue_applications() {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        
        $overdue_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_applications 
             WHERE student_id = %d 
             AND status != 'completed' 
             AND application_deadline < CURDATE()",
            $this->student_data->id
        ));
        
        return $overdue_count > 0;
    }
    
    public function get_application_stats() {
        return array(
            'total' => $this->get_total_applications(),
            'completed' => $this->get_completed_applications_count(),
            'in_progress' => $this->get_in_progress_applications_count(),
            'not_started' => $this->get_not_started_applications_count(),
            'overdue' => $this->get_overdue_applications_count(),
            'completion_rate' => $this->get_completion_rate(),
            'overall_progress' => $this->calculate_overall_progress()
        );
    }
    
    private function get_not_started_applications_count() {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_applications 
             WHERE student_id = %d AND status = 'not_started'",
            $this->student_data->id
        ));
    }
    
    private function get_overdue_applications_count() {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_applications 
             WHERE student_id = %d 
             AND status != 'completed' 
             AND application_deadline < CURDATE()",
            $this->student_data->id
        ));
    }
}
