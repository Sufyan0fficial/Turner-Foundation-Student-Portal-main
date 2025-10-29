<?php
if (!defined('ABSPATH')) {
    exit;
}

class TFSP_Complete_Ajax_Handler {
    
    public function __construct() {
        // Student actions
        add_action('wp_ajax_update_roadmap_status', array($this, 'update_roadmap_status'));
        add_action('wp_ajax_upload_document', array($this, 'upload_document'));
        add_action('wp_ajax_send_message', array($this, 'send_message'));
        add_action('wp_ajax_schedule_meeting', array($this, 'schedule_meeting'));
        add_action('wp_ajax_get_student_progress', array($this, 'get_student_progress'));
        
        // Admin actions
        add_action('wp_ajax_update_attendance', array($this, 'update_attendance'));
        add_action('wp_ajax_export_attendance', array($this, 'export_attendance'));
        add_action('wp_ajax_update_document_status', array($this, 'update_document_status'));
        add_action('wp_ajax_create_challenge', array($this, 'create_challenge'));
        add_action('wp_ajax_update_challenge', array($this, 'update_challenge'));
        add_action('wp_ajax_delete_challenge', array($this, 'delete_challenge'));
        add_action('wp_ajax_get_student_details', array($this, 'get_student_details'));
        add_action('wp_ajax_mark_message_read', array($this, 'mark_message_read'));
        add_action('wp_ajax_update_student_progress_admin', array($this, 'update_student_progress_admin'));
        add_action('wp_ajax_schedule_meeting_admin', array($this, 'schedule_meeting_admin'));
        add_action('wp_ajax_update_meeting_status', array($this, 'update_meeting_status'));
        add_action('wp_ajax_delete_meeting', array($this, 'delete_meeting'));
    }
    
    // STUDENT ACTIONS
    
    public function update_roadmap_status() {
        if (!wp_verify_nonce($_POST['nonce'], 'roadmap_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $user_id = get_current_user_id();
        $step_key = sanitize_text_field($_POST['step_key']);
        $status = sanitize_text_field($_POST['status']);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        global $wpdb;
        $result = $wpdb->replace($wpdb->prefix . 'tfsp_student_progress', array(
            'student_id' => $user_id,
            'step_key' => $step_key,
            'status' => $status,
            'notes' => $notes,
            'updated_at' => current_time('mysql')
        ));
        
        if ($result !== false) {
            // Calculate new progress percentage
            $total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d", $user_id));
            $completed = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d AND status = 'completed'", $user_id));
            $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
            
            wp_send_json_success(array(
                'message' => 'Progress updated successfully!',
                'progress' => $progress
            ));
        } else {
            wp_send_json_error('Failed to update progress');
        }
    }
    
    public function upload_document() {
        if (!wp_verify_nonce($_POST['nonce'], 'upload_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        if (empty($_FILES['document'])) {
            wp_send_json_error('No file uploaded');
        }
        
        $user_id = get_current_user_id();
        $document_type = sanitize_text_field($_POST['document_type']);
        
        // Handle file upload
        $uploaded_file = wp_handle_upload($_FILES['document'], array('test_form' => false));
        
        if (isset($uploaded_file['error'])) {
            wp_send_json_error($uploaded_file['error']);
        }
        
        // Save to database
        global $wpdb;
        $result = $wpdb->insert($wpdb->prefix . 'tfsp_documents', array(
            'user_id' => $user_id,
            'document_type' => $document_type,
            'file_name' => basename($uploaded_file['file']),
            'file_path' => $uploaded_file['url'],
            'file_url' => $uploaded_file['url'],
            'status' => 'submitted',
            'upload_date' => current_time('mysql')
        ));
        
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Document uploaded successfully!',
                'file_name' => basename($uploaded_file['file']),
                'file_url' => $uploaded_file['url']
            ));
        } else {
            wp_send_json_error('Failed to save document');
        }
    }
    
    public function send_message() {
        if (!wp_verify_nonce($_POST['nonce'], 'message_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $user_id = get_current_user_id();
        $message_type = sanitize_text_field($_POST['message_type']); // 'coach' or 'admin'
        $subject = sanitize_text_field($_POST['subject']);
        $message = sanitize_textarea_field($_POST['message']);
        
        global $wpdb;
        $result = $wpdb->insert($wpdb->prefix . 'tfsp_messages', array(
            'student_id' => $user_id,
            'message_type' => $message_type,
            'subject' => $subject,
            'message' => $message,
            'status' => 'unread',
            'created_at' => current_time('mysql')
        ));
        
        if ($result) {
            // Send email notification to admin
            $admin_email = get_option('admin_email');
            $user = get_userdata($user_id);
            
            wp_mail(
                $admin_email,
                "New message from {$user->display_name}: {$subject}",
                "Student: {$user->display_name}\nType: {$message_type}\nSubject: {$subject}\n\nMessage:\n{$message}"
            );
            
            wp_send_json_success('Message sent successfully!');
        } else {
            wp_send_json_error('Failed to send message');
        }
    }
    
    public function schedule_meeting() {
        if (!wp_verify_nonce($_POST['nonce'], 'meeting_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $user_id = get_current_user_id();
        $meeting_date = sanitize_text_field($_POST['meeting_date']);
        $meeting_time = sanitize_text_field($_POST['meeting_time']);
        $meeting_type = sanitize_text_field($_POST['meeting_type']);
        
        global $wpdb;
        $result = $wpdb->insert($wpdb->prefix . 'tfsp_meetings', array(
            'user_id' => $user_id,
            'meeting_date' => $meeting_date,
            'meeting_time' => $meeting_time,
            'meeting_type' => $meeting_type,
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ));
        
        if ($result) {
            wp_send_json_success('Meeting scheduled successfully!');
        } else {
            wp_send_json_error('Failed to schedule meeting');
        }
    }
    
    public function get_student_progress() {
        $user_id = get_current_user_id();
        
        global $wpdb;
        $progress_data = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}tfsp_student_progress 
            WHERE student_id = %d 
            ORDER BY updated_at DESC
        ", $user_id));
        
        $total = count($progress_data);
        $completed = count(array_filter($progress_data, function($item) {
            return $item->status === 'completed';
        }));
        
        $progress_percentage = $total > 0 ? round(($completed / $total) * 100) : 0;
        
        wp_send_json_success(array(
            'progress_data' => $progress_data,
            'total' => $total,
            'completed' => $completed,
            'percentage' => $progress_percentage
        ));
    }
    
    // ADMIN ACTIONS
    
    public function update_attendance() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'attendance_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        global $wpdb;
        
        // Check if record exists
        $existing = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}tfsp_attendance 
            WHERE student_id = %d AND date = %s
        ", intval($_POST['student_id']), sanitize_text_field($_POST['date'])));
        
        if ($existing) {
            // Update existing record
            $result = $wpdb->update(
                $wpdb->prefix . 'tfsp_attendance',
                array(
                    'status' => sanitize_text_field($_POST['status']),
                    'notes' => sanitize_textarea_field($_POST['notes'] ?? '')
                ),
                array('id' => $existing)
            );
        } else {
            // Insert new record
            $result = $wpdb->insert(
                $wpdb->prefix . 'tfsp_attendance',
                array(
                    'student_id' => intval($_POST['student_id']),
                    'date' => sanitize_text_field($_POST['date']),
                    'status' => sanitize_text_field($_POST['status']),
                    'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
                    'created_at' => current_time('mysql')
                )
            );
        }
        
        if ($result !== false) {
            wp_send_json_success('Attendance updated successfully');
        } else {
            wp_send_json_error('Failed to update attendance: ' . $wpdb->last_error);
        }
    }
    
    public function export_attendance() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_GET['nonce'], 'export_nonce')) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        $results = $wpdb->get_results("
            SELECT u.display_name, a.date, a.status, a.notes
            FROM {$wpdb->prefix}tfsp_attendance a 
            JOIN {$wpdb->users} u ON a.student_id = u.ID 
            ORDER BY u.display_name, a.date
        ");
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="attendance-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Student', 'Date', 'Status', 'Notes'));
        
        foreach ($results as $row) {
            fputcsv($output, array($row->display_name, $row->date, $row->status, $row->notes));
        }
        
        fclose($output);
        exit;
    }
    
    public function update_document_status() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'doc_status_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'tfsp_documents',
            array('status' => sanitize_text_field($_POST['status'])),
            array('id' => intval($_POST['doc_id']))
        );
        
        if ($result !== false) {
            // Get student info and send notification
            $doc = $wpdb->get_row($wpdb->prepare("
                SELECT d.*, u.user_email, u.display_name 
                FROM {$wpdb->prefix}tfsp_documents d 
                JOIN {$wpdb->users} u ON d.user_id = u.ID 
                WHERE d.id = %d
            ", intval($_POST['doc_id'])));
            
            if ($doc) {
                $status_messages = array(
                    'accepted' => 'Your document has been accepted!',
                    'needs_revision' => 'Your document needs revision. Please check the feedback and resubmit.',
                    'submitted' => 'Your document status has been updated to submitted.'
                );
                
                wp_mail(
                    $doc->user_email,
                    "Document Status Update: {$doc->file_name}",
                    "Hello {$doc->display_name},\n\n{$status_messages[$_POST['status']]}\n\nDocument: {$doc->file_name}\nType: {$doc->document_type}"
                );
            }
            
            wp_send_json_success('Document status updated successfully');
        } else {
            wp_send_json_error('Failed to update document status');
        }
    }
    
    public function create_challenge() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'challenge_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        global $wpdb;
        $result = $wpdb->insert($wpdb->prefix . 'tfsp_challenges', array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'target_year' => sanitize_text_field($_POST['target_year']),
            'difficulty' => intval($_POST['difficulty']),
            'status' => 'active',
            'created_at' => current_time('mysql')
        ));
        
        if ($result) {
            wp_send_json_success('Challenge created successfully');
        } else {
            wp_send_json_error('Failed to create challenge');
        }
    }
    
    public function update_challenge() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'challenge_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'tfsp_challenges',
            array(
                'title' => sanitize_text_field($_POST['title']),
                'description' => sanitize_textarea_field($_POST['description']),
                'target_year' => sanitize_text_field($_POST['target_year']),
                'difficulty' => intval($_POST['difficulty'])
            ),
            array('id' => intval($_POST['challenge_id']))
        );
        
        if ($result !== false) {
            wp_send_json_success('Challenge updated successfully');
        } else {
            wp_send_json_error('Failed to update challenge');
        }
    }
    
    public function delete_challenge() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'challenge_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        global $wpdb;
        $result = $wpdb->delete(
            $wpdb->prefix . 'tfsp_challenges',
            array('id' => intval($_POST['challenge_id']))
        );
        
        if ($result) {
            wp_send_json_success('Challenge deleted successfully');
        } else {
            wp_send_json_error('Failed to delete challenge');
        }
    }
    
    public function get_student_details() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $student_id = intval($_POST['student_id']);
        
        global $wpdb;
        
        // Get student info
        $student = get_userdata($student_id);
        if (!$student) {
            wp_send_json_error('Student not found');
        }
        
        // Get progress data
        $progress = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}tfsp_student_progress 
            WHERE student_id = %d 
            ORDER BY updated_at DESC
        ", $student_id));
        
        // Get documents
        $documents = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}tfsp_documents 
            WHERE user_id = %d 
            ORDER BY upload_date DESC
        ", $student_id));
        
        // Get messages
        $messages = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}tfsp_messages 
            WHERE student_id = %d 
            ORDER BY created_at DESC
        ", $student_id));
        
        // Get attendance
        $attendance = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}tfsp_attendance 
            WHERE student_id = %d 
            ORDER BY date DESC
        ", $student_id));
        
        wp_send_json_success(array(
            'student' => array(
                'id' => $student->ID,
                'name' => $student->display_name,
                'email' => $student->user_email,
                'registered' => $student->user_registered
            ),
            'progress' => $progress,
            'documents' => $documents,
            'messages' => $messages,
            'attendance' => $attendance
        ));
    }
    
    public function mark_message_read() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'tfsp_messages',
            array('status' => 'read'),
            array('id' => intval($_POST['message_id']))
        );
        
        if ($result !== false) {
            wp_send_json_success('Message marked as read');
        } else {
            wp_send_json_error('Failed to update message status');
        }
    }
    
    public function update_student_progress_admin() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'admin_progress_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        global $wpdb;
        $result = $wpdb->replace($wpdb->prefix . 'tfsp_student_progress', array(
            'student_id' => intval($_POST['student_id']),
            'step_key' => sanitize_text_field($_POST['step_key']),
            'status' => sanitize_text_field($_POST['status']),
            'updated_at' => current_time('mysql')
        ));
        
        if ($result !== false) {
            wp_send_json_success('Student progress updated successfully');
        } else {
            wp_send_json_error('Failed to update student progress');
        }
    }
    
    public function schedule_meeting_admin() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'meeting_admin_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        global $wpdb;
        $result = $wpdb->insert($wpdb->prefix . 'tfsp_meetings', array(
            'user_id' => intval($_POST['student_id']),
            'meeting_date' => sanitize_text_field($_POST['meeting_date']),
            'meeting_time' => sanitize_text_field($_POST['meeting_time']),
            'meeting_type' => sanitize_text_field($_POST['meeting_type']),
            'notes' => sanitize_textarea_field($_POST['notes']),
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ));
        
        if ($result) {
            wp_send_json_success('Meeting scheduled successfully');
        } else {
            wp_send_json_error('Failed to schedule meeting');
        }
    }
    
    public function update_meeting_status() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'meeting_status_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'tfsp_meetings',
            array('status' => sanitize_text_field($_POST['status'])),
            array('id' => intval($_POST['meeting_id']))
        );
        
        if ($result !== false) {
            wp_send_json_success('Meeting status updated');
        } else {
            wp_send_json_error('Failed to update meeting status');
        }
    }
    
    public function delete_meeting() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'delete_meeting_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        global $wpdb;
        $result = $wpdb->delete(
            $wpdb->prefix . 'tfsp_meetings',
            array('id' => intval($_POST['meeting_id']))
        );
        
        if ($result) {
            wp_send_json_success('Meeting deleted successfully');
        } else {
            wp_send_json_error('Failed to delete meeting');
        }
    }
}

new TFSP_Complete_Ajax_Handler();
?>
