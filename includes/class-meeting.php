<?php
/**
 * Meeting Management Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class TFSP_Meeting {
    
    private $db;
    
    public function __construct() {
        $this->db = TFSP_Database::get_instance();
    }
    
    public function schedule($meeting_data) {
        global $wpdb;
        
        $table_meetings = $wpdb->prefix . 'tfsp_meetings';
        
        // Get student ID from user ID
        $student = new TFSP_Student($meeting_data['student_id']);
        $student_id = $student->get_student_id();
        
        if (!$student_id) {
            return false;
        }
        
        // Validate meeting data
        if (!$this->validate_meeting_data($meeting_data)) {
            return false;
        }
        
        // Check for conflicts
        if ($this->has_scheduling_conflict($meeting_data['meeting_date'], $meeting_data['meeting_time'])) {
            return false;
        }
        
        $meeting_record = array(
            'student_id' => $student_id,
            'meeting_date' => $meeting_data['meeting_date'],
            'meeting_time' => $meeting_data['meeting_time'],
            'duration' => isset($meeting_data['duration']) ? intval($meeting_data['duration']) : 60,
            'meeting_type' => sanitize_text_field($meeting_data['meeting_type']),
            'meeting_format' => sanitize_text_field($meeting_data['meeting_format'] ?? 'in_person'),
            'location' => sanitize_text_field($meeting_data['location'] ?? ''),
            'zoom_link' => esc_url_raw($meeting_data['zoom_link'] ?? ''),
            'status' => 'scheduled',
            'agenda' => sanitize_textarea_field($meeting_data['notes'] ?? ''),
            'student_notes' => sanitize_textarea_field($meeting_data['notes'] ?? ''),
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table_meetings, $meeting_record);
        
        if ($result) {
            $meeting_id = $wpdb->insert_id;
            
            // Log activity
            $student->log_activity('meeting_scheduled', 
                "Scheduled {$meeting_data['meeting_type']} meeting for {$meeting_data['meeting_date']}", 
                'meeting', $meeting_id);
            
            // Create notification
            $this->create_meeting_notification($student_id, $meeting_id, 'scheduled');
            
            // Send email notification (if enabled)
            $this->send_meeting_email($meeting_id, 'scheduled');
            
            return $meeting_id;
        }
        
        return false;
    }
    
    public function update_meeting($meeting_id, $data, $user_id = null) {
        global $wpdb;
        
        $table_meetings = $wpdb->prefix . 'tfsp_meetings';
        
        // Get existing meeting
        $meeting = $this->get_meeting($meeting_id);
        if (!$meeting) {
            return false;
        }
        
        // If user_id provided, verify ownership
        if ($user_id) {
            $student = new TFSP_Student($user_id);
            $student_id = $student->get_student_id();
            
            if ($meeting->student_id != $student_id) {
                return false;
            }
        }
        
        $allowed_fields = array(
            'meeting_date', 'meeting_time', 'duration', 'meeting_type',
            'meeting_format', 'location', 'zoom_link', 'status',
            'agenda', 'student_notes', 'counselor_notes'
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
        
        // Check for conflicts if date/time changed
        if (isset($update_data['meeting_date']) || isset($update_data['meeting_time'])) {
            $new_date = $update_data['meeting_date'] ?? $meeting->meeting_date;
            $new_time = $update_data['meeting_time'] ?? $meeting->meeting_time;
            
            if ($this->has_scheduling_conflict($new_date, $new_time, $meeting_id)) {
                return false;
            }
        }
        
        $update_data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            $table_meetings,
            $update_data,
            array('id' => $meeting_id)
        );
        
        if ($result !== false) {
            // Log activity
            if ($user_id) {
                $student = new TFSP_Student($user_id);
                $student->log_activity('meeting_updated', 'Meeting updated', 'meeting', $meeting_id);
            }
            
            // Send notification if status changed
            if (isset($update_data['status'])) {
                $this->create_meeting_notification($meeting->student_id, $meeting_id, $update_data['status']);
                $this->send_meeting_email($meeting_id, $update_data['status']);
            }
        }
        
        return $result !== false;
    }
    
    public function cancel_meeting($meeting_id, $user_id, $reason = '') {
        return $this->update_meeting($meeting_id, array(
            'status' => 'cancelled',
            'counselor_notes' => $reason
        ), $user_id);
    }
    
    public function complete_meeting($meeting_id, $notes = '', $follow_up_required = false, $follow_up_date = null) {
        $update_data = array(
            'status' => 'completed',
            'counselor_notes' => $notes,
            'follow_up_required' => $follow_up_required ? 1 : 0
        );
        
        if ($follow_up_date) {
            $update_data['follow_up_date'] = $follow_up_date;
        }
        
        return $this->update_meeting($meeting_id, $update_data);
    }
    
    public function get_meeting($meeting_id) {
        global $wpdb;
        
        $table_meetings = $wpdb->prefix . 'tfsp_meetings';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_meetings WHERE id = %d",
            $meeting_id
        ));
    }
    
    public function get_student_meetings($student_id, $status = null, $limit = null) {
        global $wpdb;
        
        $table_meetings = $wpdb->prefix . 'tfsp_meetings';
        
        $where_clause = "WHERE student_id = %d";
        $params = array($student_id);
        
        if ($status) {
            $where_clause .= " AND status = %s";
            $params[] = $status;
        }
        
        $limit_clause = $limit ? "LIMIT " . intval($limit) : "";
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_meetings 
             $where_clause 
             ORDER BY meeting_date ASC, meeting_time ASC 
             $limit_clause",
            $params
        ));
    }
    
    public function get_upcoming_meetings($student_id = null, $days = 7) {
        global $wpdb;
        
        $table_meetings = $wpdb->prefix . 'tfsp_meetings';
        
        $where_clause = "WHERE meeting_date >= CURDATE() 
                        AND meeting_date <= DATE_ADD(CURDATE(), INTERVAL %d DAY) 
                        AND status = 'scheduled'";
        $params = array($days);
        
        if ($student_id) {
            $where_clause .= " AND student_id = %d";
            $params[] = $student_id;
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_meetings 
             $where_clause 
             ORDER BY meeting_date ASC, meeting_time ASC",
            $params
        ));
    }
    
    public function get_meetings_by_date($date, $counselor_id = null) {
        global $wpdb;
        
        $table_meetings = $wpdb->prefix . 'tfsp_meetings';
        
        $where_clause = "WHERE meeting_date = %s";
        $params = array($date);
        
        if ($counselor_id) {
            $where_clause .= " AND counselor_id = %d";
            $params[] = $counselor_id;
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_meetings 
             $where_clause 
             ORDER BY meeting_time ASC",
            $params
        ));
    }
    
    public function get_available_time_slots($date, $duration = 60, $counselor_id = null) {
        // Define working hours (9 AM to 5 PM)
        $start_hour = 9;
        $end_hour = 17;
        
        // Get existing meetings for the date
        $existing_meetings = $this->get_meetings_by_date($date, $counselor_id);
        
        $available_slots = array();
        $current_time = $start_hour * 60; // Convert to minutes
        $end_time = $end_hour * 60;
        
        while ($current_time + $duration <= $end_time) {
            $slot_start = $current_time;
            $slot_end = $current_time + $duration;
            
            $is_available = true;
            
            // Check against existing meetings
            foreach ($existing_meetings as $meeting) {
                $meeting_start = $this->time_to_minutes($meeting->meeting_time);
                $meeting_end = $meeting_start + $meeting->duration;
                
                // Check for overlap
                if (($slot_start < $meeting_end) && ($slot_end > $meeting_start)) {
                    $is_available = false;
                    break;
                }
            }
            
            if ($is_available) {
                $available_slots[] = $this->minutes_to_time($current_time);
            }
            
            $current_time += 30; // 30-minute intervals
        }
        
        return $available_slots;
    }
    
    public function get_meeting_types() {
        return array(
            'college_planning' => 'College Planning',
            'application_review' => 'Application Review',
            'essay_feedback' => 'Essay Feedback',
            'financial_aid' => 'Financial Aid Discussion',
            'scholarship_guidance' => 'Scholarship Guidance',
            'career_counseling' => 'Career Counseling',
            'test_preparation' => 'Test Preparation',
            'general_guidance' => 'General Guidance',
            'follow_up' => 'Follow-up Meeting'
        );
    }
    
    public function get_meeting_formats() {
        return array(
            'in_person' => 'In Person',
            'video_call' => 'Video Call',
            'phone_call' => 'Phone Call'
        );
    }
    
    public function get_meeting_stats($student_id = null, $counselor_id = null) {
        global $wpdb;
        
        $table_meetings = $wpdb->prefix . 'tfsp_meetings';
        
        $where_conditions = array();
        $params = array();
        
        if ($student_id) {
            $where_conditions[] = "student_id = %d";
            $params[] = $student_id;
        }
        
        if ($counselor_id) {
            $where_conditions[] = "counselor_id = %d";
            $params[] = $counselor_id;
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN meeting_date >= CURDATE() AND status = 'scheduled' THEN 1 ELSE 0 END) as upcoming
             FROM $table_meetings 
             $where_clause",
            $params
        ));
        
        return array(
            'total' => intval($stats->total),
            'scheduled' => intval($stats->scheduled),
            'completed' => intval($stats->completed),
            'cancelled' => intval($stats->cancelled),
            'upcoming' => intval($stats->upcoming)
        );
    }
    
    private function validate_meeting_data($data) {
        // Required fields
        $required_fields = array('student_id', 'meeting_date', 'meeting_time', 'meeting_type');
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        
        // Validate date (must be in the future)
        $meeting_datetime = $data['meeting_date'] . ' ' . $data['meeting_time'];
        if (strtotime($meeting_datetime) <= time()) {
            return false;
        }
        
        // Validate meeting type
        $valid_types = array_keys($this->get_meeting_types());
        if (!in_array($data['meeting_type'], $valid_types)) {
            return false;
        }
        
        return true;
    }
    
    private function has_scheduling_conflict($date, $time, $exclude_meeting_id = null) {
        global $wpdb;
        
        $table_meetings = $wpdb->prefix . 'tfsp_meetings';
        
        $where_clause = "WHERE meeting_date = %s AND meeting_time = %s AND status = 'scheduled'";
        $params = array($date, $time);
        
        if ($exclude_meeting_id) {
            $where_clause .= " AND id != %d";
            $params[] = $exclude_meeting_id;
        }
        
        $conflict_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_meetings $where_clause",
            $params
        ));
        
        return $conflict_count > 0;
    }
    
    private function create_meeting_notification($student_id, $meeting_id, $status) {
        $meeting = $this->get_meeting($meeting_id);
        if (!$meeting) return;
        
        $titles = array(
            'scheduled' => 'Meeting Scheduled',
            'confirmed' => 'Meeting Confirmed',
            'cancelled' => 'Meeting Cancelled',
            'completed' => 'Meeting Completed',
            'rescheduled' => 'Meeting Rescheduled'
        );
        
        $messages = array(
            'scheduled' => "Your {$meeting->meeting_type} meeting has been scheduled for " . 
                          date('M j, Y', strtotime($meeting->meeting_date)) . " at " . 
                          date('g:i A', strtotime($meeting->meeting_time)),
            'confirmed' => "Your meeting has been confirmed",
            'cancelled' => "Your meeting has been cancelled",
            'completed' => "Your meeting has been completed",
            'rescheduled' => "Your meeting has been rescheduled"
        );
        
        $title = isset($titles[$status]) ? $titles[$status] : 'Meeting Update';
        $message = isset($messages[$status]) ? $messages[$status] : 'Your meeting status has been updated';
        
        $this->db->create_notification($student_id, 'meeting_update', $title, $message);
    }
    
    private function send_meeting_email($meeting_id, $status) {
        // Email functionality would be implemented here
        // This could integrate with WordPress mail functions or external email services
        
        $meeting = $this->get_meeting($meeting_id);
        if (!$meeting) return;
        
        // Get student email
        $student = $this->db->get_student_by_id($meeting->student_id);
        if (!$student || !$student->email) return;
        
        // Email templates and sending logic would go here
        // For now, we'll just log that an email should be sent
        error_log("TFSP: Email notification should be sent to {$student->email} for meeting {$meeting_id} status: {$status}");
    }
    
    private function time_to_minutes($time) {
        $parts = explode(':', $time);
        return intval($parts[0]) * 60 + intval($parts[1]);
    }
    
    private function minutes_to_time($minutes) {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }
    
    public function send_meeting_reminders($hours_before = 24) {
        global $wpdb;
        
        $table_meetings = $wpdb->prefix . 'tfsp_meetings';
        
        // Get meetings that need reminders
        $meetings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_meetings 
             WHERE status = 'scheduled' 
             AND reminder_sent = 0 
             AND CONCAT(meeting_date, ' ', meeting_time) <= DATE_ADD(NOW(), INTERVAL %d HOUR) 
             AND CONCAT(meeting_date, ' ', meeting_time) > NOW()",
            $hours_before
        ));
        
        $reminder_count = 0;
        
        foreach ($meetings as $meeting) {
            // Send reminder notification
            $this->create_meeting_notification($meeting->student_id, $meeting->id, 'reminder');
            
            // Mark reminder as sent
            $wpdb->update(
                $table_meetings,
                array('reminder_sent' => 1),
                array('id' => $meeting->id)
            );
            
            $reminder_count++;
        }
        
        return $reminder_count;
    }
}
