<?php
/**
 * Document Management Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class TFSP_Document {
    
    private $db;
    private $upload_dir;
    private $allowed_types;
    private $max_file_size;
    
    public function __construct() {
        $this->db = TFSP_Database::get_instance();
        $this->setup_upload_directory();
        $this->allowed_types = array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png');
        $this->max_file_size = 10 * 1024 * 1024; // 10MB
    }
    
    private function setup_upload_directory() {
        $upload_dir = wp_upload_dir();
        $this->upload_dir = $upload_dir['basedir'] . '/tfsp-documents';
        
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
            
            // Create .htaccess for security
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<Files *.php>\n";
            $htaccess_content .= "deny from all\n";
            $htaccess_content .= "</Files>\n";
            file_put_contents($this->upload_dir . '/.htaccess', $htaccess_content);
        }
    }
    
    public function handle_upload($user_id) {
        if (!isset($_FILES['documents']) || empty($_FILES['documents'])) {
            return array('success' => false, 'message' => 'No files uploaded');
        }
        
        $student = new TFSP_Student($user_id);
        $student_id = $student->get_student_id();
        
        if (!$student_id) {
            return array('success' => false, 'message' => 'Invalid student');
        }
        
        $files = $_FILES['documents'];
        $uploaded_files = array();
        $errors = array();
        
        // Handle multiple files
        if (is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                $file = array(
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                );
                
                $result = $this->process_single_file($file, $student_id);
                
                if ($result['success']) {
                    $uploaded_files[] = $result['data'];
                } else {
                    $errors[] = $result['message'];
                }
            }
        } else {
            // Single file
            $result = $this->process_single_file($files, $student_id);
            
            if ($result['success']) {
                $uploaded_files[] = $result['data'];
            } else {
                $errors[] = $result['message'];
            }
        }
        
        if (!empty($uploaded_files)) {
            $student->log_activity('document_uploaded', count($uploaded_files) . ' document(s) uploaded');
            
            return array(
                'success' => true,
                'data' => array(
                    'uploaded' => $uploaded_files,
                    'errors' => $errors
                )
            );
        }
        
        return array(
            'success' => false,
            'message' => 'No files were uploaded successfully. Errors: ' . implode(', ', $errors)
        );
    }
    
    private function process_single_file($file, $student_id) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array('success' => false, 'message' => 'Upload error: ' . $this->get_upload_error_message($file['error']));
        }
        
        // Validate file type
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $this->allowed_types)) {
            return array('success' => false, 'message' => 'File type not allowed: ' . $file_extension);
        }
        
        // Validate file size
        if ($file['size'] > $this->max_file_size) {
            return array('success' => false, 'message' => 'File too large: ' . size_format($file['size']));
        }
        
        // Validate MIME type
        $allowed_mimes = array(
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
        );
        
        if (!isset($allowed_mimes[$file_extension]) || $file['type'] !== $allowed_mimes[$file_extension]) {
            return array('success' => false, 'message' => 'Invalid MIME type');
        }
        
        // Generate unique filename
        $original_filename = sanitize_file_name($file['name']);
        $filename = $this->generate_unique_filename($original_filename, $student_id);
        $file_path = $this->upload_dir . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            return array('success' => false, 'message' => 'Failed to move uploaded file');
        }
        
        // Save to database
        $document_id = $this->save_document_record($student_id, $original_filename, $filename, $file);
        
        if (!$document_id) {
            // Clean up file if database save failed
            unlink($file_path);
            return array('success' => false, 'message' => 'Failed to save document record');
        }
        
        return array(
            'success' => true,
            'data' => array(
                'id' => $document_id,
                'filename' => $original_filename,
                'size' => $file['size'],
                'type' => $file_extension
            )
        );
    }
    
    private function save_document_record($student_id, $original_filename, $filename, $file) {
        global $wpdb;
        
        $table_documents = $wpdb->prefix . 'tfsp_documents';
        
        $document_data = array(
            'student_id' => $student_id,
            'document_name' => $original_filename,
            'original_filename' => $original_filename,
            'file_path' => $filename,
            'file_size' => $file['size'],
            'file_type' => strtolower(pathinfo($original_filename, PATHINFO_EXTENSION)),
            'mime_type' => $file['type'],
            'document_category' => $this->determine_document_category($original_filename),
            'status' => 'pending',
            'version' => 1,
            'is_latest' => 1,
            'uploaded_date' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table_documents, $document_data);
        
        return $result ? $wpdb->insert_id : false;
    }
    
    private function generate_unique_filename($original_filename, $student_id) {
        $file_info = pathinfo($original_filename);
        $extension = isset($file_info['extension']) ? '.' . $file_info['extension'] : '';
        $basename = sanitize_file_name($file_info['filename']);
        
        // Create filename with student ID and timestamp
        $timestamp = time();
        $filename = "student_{$student_id}_{$timestamp}_{$basename}{$extension}";
        
        // Ensure uniqueness
        $counter = 1;
        while (file_exists($this->upload_dir . '/' . $filename)) {
            $filename = "student_{$student_id}_{$timestamp}_{$basename}_{$counter}{$extension}";
            $counter++;
        }
        
        return $filename;
    }
    
    private function determine_document_category($filename) {
        $filename_lower = strtolower($filename);
        
        $categories = array(
            'transcript' => array('transcript', 'grade', 'academic'),
            'essay' => array('essay', 'personal', 'statement', 'writing'),
            'recommendation' => array('recommendation', 'reference', 'letter'),
            'resume' => array('resume', 'cv', 'curriculum'),
            'application' => array('application', 'form'),
            'other' => array()
        );
        
        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($filename_lower, $keyword) !== false) {
                    return $category;
                }
            }
        }
        
        return 'other';
    }
    
    public function get_document($document_id, $student_id = null) {
        global $wpdb;
        
        $table_documents = $wpdb->prefix . 'tfsp_documents';
        
        $where_clause = "WHERE id = %d";
        $params = array($document_id);
        
        if ($student_id) {
            $where_clause .= " AND student_id = %d";
            $params[] = $student_id;
        }
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_documents $where_clause",
            $params
        ));
    }
    
    public function download_document($document_id, $user_id) {
        $student = new TFSP_Student($user_id);
        $student_id = $student->get_student_id();
        
        if (!$student_id) {
            return false;
        }
        
        $document = $this->get_document($document_id, $student_id);
        
        if (!$document) {
            return false;
        }
        
        $file_path = $this->upload_dir . '/' . $document->file_path;
        
        if (!file_exists($file_path)) {
            return false;
        }
        
        // Log download activity
        $student->log_activity('document_downloaded', 'Downloaded: ' . $document->document_name, 'document', $document_id);
        
        // Set headers for download
        header('Content-Type: ' . $document->mime_type);
        header('Content-Disposition: attachment; filename="' . $document->original_filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: private');
        
        // Output file
        readfile($file_path);
        exit;
    }
    
    public function delete_document($document_id, $user_id) {
        $student = new TFSP_Student($user_id);
        $student_id = $student->get_student_id();
        
        if (!$student_id) {
            return false;
        }
        
        $document = $this->get_document($document_id, $student_id);
        
        if (!$document) {
            return false;
        }
        
        global $wpdb;
        $table_documents = $wpdb->prefix . 'tfsp_documents';
        
        // Delete file from filesystem
        $file_path = $this->upload_dir . '/' . $document->file_path;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete database record
        $result = $wpdb->delete(
            $table_documents,
            array('id' => $document_id, 'student_id' => $student_id)
        );
        
        if ($result) {
            $student->log_activity('document_deleted', 'Deleted: ' . $document->document_name, 'document', $document_id);
        }
        
        return $result !== false;
    }
    
    public function update_document_status($document_id, $status, $review_notes = '', $reviewer_id = null) {
        global $wpdb;
        
        $table_documents = $wpdb->prefix . 'tfsp_documents';
        
        $update_data = array(
            'status' => $status,
            'review_notes' => $review_notes,
            'reviewed_date' => current_time('mysql')
        );
        
        if ($reviewer_id) {
            $update_data['reviewed_by'] = $reviewer_id;
        }
        
        $result = $wpdb->update(
            $table_documents,
            $update_data,
            array('id' => $document_id)
        );
        
        if ($result !== false) {
            // Get document info for notification
            $document = $this->get_document($document_id);
            if ($document) {
                // Create notification for student
                $this->db->create_notification(
                    $document->student_id,
                    'document_review',
                    'Document Review Complete',
                    "Your document '{$document->document_name}' has been reviewed and marked as {$status}."
                );
            }
        }
        
        return $result !== false;
    }
    
    public function get_documents_by_category($student_id, $category) {
        global $wpdb;
        
        $table_documents = $wpdb->prefix . 'tfsp_documents';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_documents 
             WHERE student_id = %d AND document_category = %s 
             ORDER BY uploaded_date DESC",
            $student_id,
            $category
        ));
    }
    
    public function get_documents_by_status($student_id, $status) {
        global $wpdb;
        
        $table_documents = $wpdb->prefix . 'tfsp_documents';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_documents 
             WHERE student_id = %d AND status = %s 
             ORDER BY uploaded_date DESC",
            $student_id,
            $status
        ));
    }
    
    public function get_document_categories() {
        return array(
            'transcript' => 'Academic Transcripts',
            'essay' => 'Essays & Personal Statements',
            'recommendation' => 'Recommendation Letters',
            'resume' => 'Resume & CV',
            'application' => 'Application Forms',
            'other' => 'Other Documents'
        );
    }
    
    public function get_document_stats($student_id) {
        global $wpdb;
        
        $table_documents = $wpdb->prefix . 'tfsp_documents';
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(file_size) as total_size
             FROM $table_documents 
             WHERE student_id = %d",
            $student_id
        ));
        
        return array(
            'total' => intval($stats->total),
            'approved' => intval($stats->approved),
            'pending' => intval($stats->pending),
            'rejected' => intval($stats->rejected),
            'total_size' => intval($stats->total_size)
        );
    }
    
    private function get_upload_error_message($error_code) {
        $errors = array(
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        );
        
        return isset($errors[$error_code]) ? $errors[$error_code] : 'Unknown upload error';
    }
    
    public function cleanup_old_files($days = 30) {
        global $wpdb;
        
        $table_documents = $wpdb->prefix . 'tfsp_documents';
        
        // Get documents older than specified days that are marked for deletion
        $old_documents = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_documents 
             WHERE status = 'deleted' 
             AND uploaded_date < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        $cleaned_count = 0;
        
        foreach ($old_documents as $document) {
            $file_path = $this->upload_dir . '/' . $document->file_path;
            
            // Delete file from filesystem
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Delete database record
            $wpdb->delete($table_documents, array('id' => $document->id));
            $cleaned_count++;
        }
        
        return $cleaned_count;
    }
}
