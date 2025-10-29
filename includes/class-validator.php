<?php
if (!defined('ABSPATH')) {
    exit;
}

class TFSP_Validator {
    
    private static $errors = array();
    
    /**
     * Validate student registration data
     */
    public static function validate_student_registration($data) {
        self::$errors = array();
        
        // Validate first name
        if (empty(trim($data['first_name']))) {
            self::$errors[] = 'First name is required';
        } elseif (strlen($data['first_name']) > 50) {
            self::$errors[] = 'First name must be less than 50 characters';
        } elseif (!preg_match('/^[a-zA-Z\s\-\']+$/', $data['first_name'])) {
            self::$errors[] = 'First name contains invalid characters';
        }
        
        // Validate last name
        if (empty(trim($data['last_name']))) {
            self::$errors[] = 'Last name is required';
        } elseif (strlen($data['last_name']) > 50) {
            self::$errors[] = 'Last name must be less than 50 characters';
        } elseif (!preg_match('/^[a-zA-Z\s\-\']+$/', $data['last_name'])) {
            self::$errors[] = 'Last name contains invalid characters';
        }
        
        // Validate email
        if (empty(trim($data['email']))) {
            self::$errors[] = 'Email is required';
        } elseif (!is_email($data['email'])) {
            self::$errors[] = 'Please enter a valid email address';
        } elseif (email_exists($data['email'])) {
            self::$errors[] = 'This email is already registered';
        }
        
        // Validate phone
        if (!empty($data['phone'])) {
            $phone = preg_replace('/[^0-9]/', '', $data['phone']);
            if (strlen($phone) < 10 || strlen($phone) > 15) {
                self::$errors[] = 'Please enter a valid phone number';
            }
        }
        
        // Validate grade level
        if (empty($data['grade_level'])) {
            self::$errors[] = 'Grade level is required';
        } elseif (!in_array($data['grade_level'], ['9', '10', '11', '12'])) {
            self::$errors[] = 'Invalid grade level selected';
        }
        
        // Validate school
        if (empty(trim($data['school']))) {
            self::$errors[] = 'School name is required';
        } elseif (strlen($data['school']) > 100) {
            self::$errors[] = 'School name must be less than 100 characters';
        }
        
        return self::$errors;
    }
    
    /**
     * Validate document upload
     */
    public static function validate_document_upload($file, $document_type) {
        self::$errors = array();
        
        // Check if file was uploaded
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            self::$errors[] = 'Please select a file to upload';
            return self::$errors;
        }
        
        // Validate file size (10MB max)
        $max_size = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $max_size) {
            self::$errors[] = 'File size must be less than 10MB';
        }
        
        // Validate file type
        $allowed_types = array(
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg',
            'image/png',
            'image/gif'
        );
        
        $file_type = mime_content_type($file['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            self::$errors[] = 'Invalid file type. Please upload PDF, Word document, or image files only';
        }
        
        // Validate file extension
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif');
        
        if (!in_array($file_extension, $allowed_extensions)) {
            self::$errors[] = 'Invalid file extension';
        }
        
        // Validate document type
        $valid_document_types = array(
            'academic_resume', 'personal_essay', 'recommendation_letters',
            'transcript', 'financial_aid', 'community_service',
            'college_list', 'college_tours', 'fafsa', 'college_admissions_tests'
        );
        
        if (!in_array($document_type, $valid_document_types)) {
            self::$errors[] = 'Invalid document type';
        }
        
        // Check for malicious files
        if (self::is_malicious_file($file)) {
            self::$errors[] = 'File appears to be malicious and cannot be uploaded';
        }
        
        return self::$errors;
    }
    
    /**
     * Validate attendance data
     */
    public static function validate_attendance($student_id, $session_date, $status) {
        self::$errors = array();
        
        // Validate student ID
        if (!is_numeric($student_id) || $student_id <= 0) {
            self::$errors[] = 'Invalid student ID';
        }
        
        // Validate date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $session_date)) {
            self::$errors[] = 'Invalid date format';
        } else {
            $date = DateTime::createFromFormat('Y-m-d', $session_date);
            if (!$date || $date->format('Y-m-d') !== $session_date) {
                self::$errors[] = 'Invalid date';
            } elseif ($date > new DateTime('+1 day')) {
                self::$errors[] = 'Cannot mark attendance for future dates';
            } elseif ($date < new DateTime('-1 year')) {
                self::$errors[] = 'Date is too far in the past';
            }
        }
        
        // Validate status
        if (!in_array($status, ['present', 'excused', 'absent', 'postponed'])) {
            self::$errors[] = 'Invalid attendance status';
        }
        
        return self::$errors;
    }
    
    /**
     * Check for malicious files
     */
    private static function is_malicious_file($file) {
        // Check file content for suspicious patterns
        $content = file_get_contents($file['tmp_name'], false, null, 0, 1024); // Read first 1KB
        
        $malicious_patterns = array(
            '/<\?php/i',
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i'
        );
        
        foreach ($malicious_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Sanitize and validate message content
     */
    public static function validate_message($subject, $message, $type) {
        self::$errors = array();
        
        // Validate subject
        if (empty(trim($subject))) {
            self::$errors[] = 'Subject is required';
        } elseif (strlen($subject) > 255) {
            self::$errors[] = 'Subject must be less than 255 characters';
        }
        
        // Validate message
        if (empty(trim($message))) {
            self::$errors[] = 'Message content is required';
        } elseif (strlen($message) > 5000) {
            self::$errors[] = 'Message must be less than 5000 characters';
        }
        
        // Validate type
        if (!in_array($type, ['coach', 'admin'])) {
            self::$errors[] = 'Invalid message type';
        }
        
        // Check for spam
        if (self::contains_spam($subject . ' ' . $message)) {
            self::$errors[] = 'Message appears to contain spam content';
        }
        
        return self::$errors;
    }
    
    /**
     * Enhanced spam detection
     */
    private static function contains_spam($content) {
        $spam_indicators = 0;
        
        // Check for excessive caps
        if (preg_match_all('/[A-Z]/', $content) > strlen($content) * 0.5) {
            $spam_indicators++;
        }
        
        // Check for excessive punctuation
        if (preg_match_all('/[!?]{3,}/', $content)) {
            $spam_indicators++;
        }
        
        // Check for suspicious URLs
        if (preg_match_all('/http[s]?:\/\/[^\s]+/', $content) > 2) {
            $spam_indicators++;
        }
        
        // Check for spam keywords
        $spam_words = array('viagra', 'casino', 'lottery', 'winner', 'congratulations', 'urgent', 'act now');
        foreach ($spam_words as $word) {
            if (stripos($content, $word) !== false) {
                $spam_indicators++;
            }
        }
        
        return $spam_indicators >= 2;
    }
    
    /**
     * Get validation errors
     */
    public static function get_errors() {
        return self::$errors;
    }
    
    /**
     * Check if validation passed
     */
    public static function is_valid() {
        return empty(self::$errors);
    }
}
?>
