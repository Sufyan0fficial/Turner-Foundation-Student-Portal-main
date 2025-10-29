<?php
/**
 * Application Management Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class TFSP_Application {
    
    private $db;
    
    public function __construct() {
        $this->db = TFSP_Database::get_instance();
    }
    
    public function create_application($student_id, $data) {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        
        $application_data = array(
            'student_id' => $student_id,
            'application_type' => sanitize_text_field($data['application_type']),
            'title' => sanitize_text_field($data['title']),
            'description' => sanitize_textarea_field($data['description']),
            'college_name' => sanitize_text_field($data['college_name'] ?? ''),
            'application_deadline' => sanitize_text_field($data['application_deadline']),
            'status' => 'not_started',
            'progress_percentage' => 0,
            'priority' => sanitize_text_field($data['priority'] ?? 'medium'),
            'requirements' => json_encode($data['requirements'] ?? array()),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table_applications, $application_data);
        
        if ($result) {
            $application_id = $wpdb->insert_id;
            $this->create_application_milestones($student_id, $application_id, $data['application_type']);
            $this->log_activity($student_id, 'application_created', 'application', $application_id);
            return $application_id;
        }
        
        return false;
    }
    
    public function update_status($user_id, $application_id, $status, $progress = null) {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        
        // Get student ID from user ID
        $student = new TFSP_Student($user_id);
        $student_id = $student->get_student_id();
        
        if (!$student_id) {
            return false;
        }
        
        // Verify the application belongs to this student
        $application = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_applications WHERE id = %d AND student_id = %d",
            $application_id,
            $student_id
        ));
        
        if (!$application) {
            return false;
        }
        
        $update_data = array(
            'status' => $status,
            'updated_at' => current_time('mysql')
        );
        
        if ($progress !== null) {
            $update_data['progress_percentage'] = min(100, max(0, intval($progress)));
        }
        
        // Set completion date if status is completed
        if ($status === 'completed') {
            $update_data['completed_date'] = current_time('mysql');
            $update_data['progress_percentage'] = 100;
        }
        
        // Set started date if status changes from not_started
        if ($application->status === 'not_started' && $status !== 'not_started') {
            $update_data['started_date'] = current_time('mysql');
        }
        
        $result = $wpdb->update(
            $table_applications,
            $update_data,
            array('id' => $application_id)
        );
        
        if ($result !== false) {
            $this->log_activity($user_id, 'application_status_updated', 'application', $application_id, 
                "Status changed to {$status}" . ($progress ? " with {$progress}% progress" : ""));
            
            // Create notification for status change
            $this->create_status_notification($user_id, $application, $status, $progress);
            
            return true;
        }
        
        return false;
    }
    
    public function get_application($application_id, $student_id = null) {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        
        $where_clause = "WHERE id = %d";
        $params = array($application_id);
        
        if ($student_id) {
            $where_clause .= " AND student_id = %d";
            $params[] = $student_id;
        }
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_applications $where_clause",
            $params
        ));
    }
    
    public function get_applications_by_type($student_id, $application_type) {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_applications 
             WHERE student_id = %d AND application_type = %s 
             ORDER BY created_at DESC",
            $student_id,
            $application_type
        ));
    }
    
    public function get_applications_by_status($student_id, $status) {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_applications 
             WHERE student_id = %d AND status = %s 
             ORDER BY application_deadline ASC, priority DESC",
            $student_id,
            $status
        ));
    }
    
    public function get_overdue_applications($student_id) {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_applications 
             WHERE student_id = %d 
             AND status != 'completed' 
             AND application_deadline < CURDATE() 
             ORDER BY application_deadline ASC",
            $student_id
        ));
    }
    
    public function update_application($application_id, $student_id, $data) {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        
        $allowed_fields = array(
            'title', 'description', 'college_name', 'application_deadline',
            'priority', 'requirements', 'notes'
        );
        
        $update_data = array();
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                if ($field === 'requirements') {
                    $update_data[$field] = json_encode($data[$field]);
                } else {
                    $update_data[$field] = sanitize_text_field($data[$field]);
                }
            }
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $update_data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            $table_applications,
            $update_data,
            array('id' => $application_id, 'student_id' => $student_id)
        );
        
        if ($result !== false) {
            $this->log_activity($student_id, 'application_updated', 'application', $application_id);
        }
        
        return $result !== false;
    }
    
    public function delete_application($application_id, $student_id) {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        $table_progress = $wpdb->prefix . 'tfsp_progress';
        $table_documents = $wpdb->prefix . 'tfsp_documents';
        
        // Delete related progress records
        $wpdb->delete($table_progress, array('application_id' => $application_id));
        
        // Update related documents (don't delete, just unlink)
        $wpdb->update(
            $table_documents,
            array('application_id' => null),
            array('application_id' => $application_id)
        );
        
        // Delete the application
        $result = $wpdb->delete(
            $table_applications,
            array('id' => $application_id, 'student_id' => $student_id)
        );
        
        if ($result) {
            $this->log_activity($student_id, 'application_deleted', 'application', $application_id);
        }
        
        return $result !== false;
    }
    
    public function get_application_progress($application_id) {
        global $wpdb;
        
        $table_progress = $wpdb->prefix . 'tfsp_progress';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_progress 
             WHERE application_id = %d 
             ORDER BY created_at ASC",
            $application_id
        ));
    }
    
    public function update_milestone_status($student_id, $application_id, $milestone, $status, $notes = '') {
        global $wpdb;
        
        $table_progress = $wpdb->prefix . 'tfsp_progress';
        
        // Check if milestone exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_progress 
             WHERE student_id = %d AND application_id = %d AND milestone = %s",
            $student_id,
            $application_id,
            $milestone
        ));
        
        if ($existing) {
            // Update existing milestone
            $update_data = array(
                'status' => $status,
                'notes' => $notes,
                'updated_at' => current_time('mysql')
            );
            
            if ($status === 'completed') {
                $update_data['completed_date'] = current_time('mysql');
            }
            
            $result = $wpdb->update(
                $table_progress,
                $update_data,
                array('id' => $existing->id)
            );
        } else {
            // Create new milestone
            $result = $wpdb->insert(
                $table_progress,
                array(
                    'student_id' => $student_id,
                    'application_id' => $application_id,
                    'milestone' => $milestone,
                    'status' => $status,
                    'notes' => $notes,
                    'completed_date' => $status === 'completed' ? current_time('mysql') : null,
                    'created_at' => current_time('mysql')
                )
            );
        }
        
        if ($result !== false) {
            $this->update_application_progress($application_id);
            $this->log_activity($student_id, 'milestone_updated', 'milestone', $application_id, 
                "Milestone '{$milestone}' marked as {$status}");
        }
        
        return $result !== false;
    }
    
    private function update_application_progress($application_id) {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        $table_progress = $wpdb->prefix . 'tfsp_progress';
        
        // Calculate progress based on completed milestones
        $total_milestones = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_progress WHERE application_id = %d",
            $application_id
        ));
        
        $completed_milestones = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_progress 
             WHERE application_id = %d AND status = 'completed'",
            $application_id
        ));
        
        $progress_percentage = $total_milestones > 0 ? 
            round(($completed_milestones / $total_milestones) * 100) : 0;
        
        // Update application progress
        $wpdb->update(
            $table_applications,
            array(
                'progress_percentage' => $progress_percentage,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $application_id)
        );
        
        // Update status based on progress
        if ($progress_percentage === 100) {
            $wpdb->update(
                $table_applications,
                array(
                    'status' => 'completed',
                    'completed_date' => current_time('mysql')
                ),
                array('id' => $application_id)
            );
        } elseif ($progress_percentage > 0) {
            $wpdb->update(
                $table_applications,
                array('status' => 'in_progress'),
                array('id' => $application_id, 'status' => 'not_started')
            );
        }
    }
    
    private function create_application_milestones($student_id, $application_id, $application_type) {
        $milestones = $this->get_default_milestones($application_type);
        
        foreach ($milestones as $milestone) {
            $this->update_milestone_status($student_id, $application_id, $milestone, 'pending');
        }
    }
    
    private function get_default_milestones($application_type) {
        $milestones = array(
            'common_app' => array(
                'Create Account',
                'Complete Personal Information',
                'Add Colleges',
                'Write Personal Essay',
                'Complete Activities Section',
                'Request Recommendations',
                'Submit Application'
            ),
            'fafsa' => array(
                'Gather Required Documents',
                'Create FSA ID',
                'Complete Student Information',
                'Complete Parent Information',
                'Review and Submit',
                'Complete Verification (if required)'
            ),
            'scholarship' => array(
                'Research Scholarships',
                'Gather Required Documents',
                'Write Essays',
                'Complete Applications',
                'Submit Applications',
                'Follow Up'
            ),
            'transcript_request' => array(
                'Contact School Counselor',
                'Complete Request Forms',
                'Pay Required Fees',
                'Verify College List',
                'Confirm Delivery'
            ),
            'test_scores' => array(
                'Register for Tests',
                'Prepare for Tests',
                'Take Tests',
                'Review Scores',
                'Send Scores to Colleges'
            ),
            'recommendation_letters' => array(
                'Identify Recommenders',
                'Request Letters',
                'Provide Supporting Materials',
                'Follow Up',
                'Confirm Submission'
            ),
            'college_essays' => array(
                'Review Essay Prompts',
                'Brainstorm Topics',
                'Write First Draft',
                'Revise and Edit',
                'Get Feedback',
                'Final Review and Submit'
            ),
            'college_visits' => array(
                'Research Colleges',
                'Schedule Visits',
                'Plan Travel',
                'Attend Visits',
                'Take Notes and Photos',
                'Follow Up'
            ),
            'housing_application' => array(
                'Research Housing Options',
                'Complete Applications',
                'Submit Deposits',
                'Complete Roommate Matching',
                'Confirm Housing Assignment'
            )
        );
        
        return isset($milestones[$application_type]) ? 
            $milestones[$application_type] : 
            array('Start Application', 'Complete Requirements', 'Submit Application');
    }
    
    public function get_application_types() {
        return array(
            'common_app' => 'Common Application',
            'fafsa' => 'FAFSA Application',
            'state_aid' => 'State Financial Aid',
            'scholarship' => 'Scholarship Applications',
            'transcript_request' => 'Official Transcripts',
            'test_scores' => 'Standardized Test Scores',
            'recommendation_letters' => 'Letters of Recommendation',
            'college_essays' => 'College Essays',
            'college_visits' => 'College Campus Visits',
            'housing_application' => 'Housing Applications'
        );
    }
    
    public function get_status_options() {
        return array(
            'not_started' => 'Not Started',
            'in_progress' => 'In Progress',
            'review' => 'Under Review',
            'completed' => 'Completed',
            'submitted' => 'Submitted',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'waitlisted' => 'Waitlisted'
        );
    }
    
    public function get_priority_options() {
        return array(
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent'
        );
    }
    
    private function create_status_notification($user_id, $application, $new_status, $progress = null) {
        $title = "Application Status Updated";
        $message = "Your {$application->title} status has been updated to " . ucfirst(str_replace('_', ' ', $new_status));
        
        if ($progress) {
            $message .= " with {$progress}% progress";
        }
        
        $this->db->create_notification($user_id, 'status_update', $title, $message);
    }
    
    private function log_activity($user_id, $action, $object_type = null, $object_id = null, $description = null) {
        $this->db->log_activity($user_id, $action, $object_type, $object_id, $description);
    }
    
    public function get_application_summary($student_id) {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        
        $summary = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'not_started' THEN 1 ELSE 0 END) as not_started,
                SUM(CASE WHEN application_deadline < CURDATE() AND status != 'completed' THEN 1 ELSE 0 END) as overdue,
                AVG(progress_percentage) as avg_progress
             FROM $table_applications 
             WHERE student_id = %d AND status != 'template'",
            $student_id
        ));
        
        return array(
            'total' => intval($summary->total),
            'completed' => intval($summary->completed),
            'in_progress' => intval($summary->in_progress),
            'not_started' => intval($summary->not_started),
            'overdue' => intval($summary->overdue),
            'avg_progress' => round(floatval($summary->avg_progress))
        );
    }
}
