<?php
if (!defined('ABSPATH')) {
    exit;
}

class TFSP_Security {
    
    /**
     * Validate and sanitize attendance data
     */
    public static function validate_attendance_data($student_id, $session_date, $status) {
        // Validate student ID
        if (!is_numeric($student_id) || $student_id <= 0) {
            return false;
        }
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $session_date)) {
            return false;
        }
        
        // Validate date is not in future (more than 1 day)
        if (strtotime($session_date) > strtotime('+1 day')) {
            return false;
        }
        
        // Validate status
        if (!in_array($status, ['present', 'excused', 'absent', 'postponed'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate message data
     */
    public static function validate_message_data($message_type, $subject, $message) {
        $errors = array();
        
        // Validate message type
        if (!in_array($message_type, ['coach', 'admin'])) {
            $errors[] = 'Invalid message type';
        }
        
        // Validate subject
        if (empty(trim($subject))) {
            $errors[] = 'Subject is required';
        } elseif (strlen($subject) > 255) {
            $errors[] = 'Subject too long (max 255 characters)';
        }
        
        // Validate message
        if (empty(trim($message))) {
            $errors[] = 'Message is required';
        } elseif (strlen($message) > 5000) {
            $errors[] = 'Message too long (max 5000 characters)';
        }
        
        // Check for spam patterns
        if (self::is_spam_content($subject . ' ' . $message)) {
            $errors[] = 'Message appears to be spam';
        }
        
        return $errors;
    }
    
    /**
     * Basic spam detection
     */
    private static function is_spam_content($content) {
        $spam_patterns = array(
            '/\b(viagra|cialis|casino|lottery|winner|congratulations)\b/i',
            '/\$\d+.*\b(million|thousand)\b/i',
            '/\b(click here|act now|limited time)\b/i',
            '/http[s]?:\/\/[^\s]+/i' // URLs in messages
        );
        
        foreach ($spam_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Rate limiting check
     */
    public static function check_rate_limit($user_id, $action, $limit_minutes = 5) {
        global $wpdb;
        
        $transient_key = "tfsp_rate_limit_{$action}_{$user_id}";
        $last_action = get_transient($transient_key);
        
        if ($last_action) {
            return false; // Rate limited
        }
        
        // Set rate limit
        set_transient($transient_key, time(), $limit_minutes * 60);
        return true;
    }
    
    /**
     * Log security events
     */
    public static function log_security_event($event_type, $user_id, $details = '') {
        global $wpdb;
        
        $log_table = $wpdb->prefix . 'tfsp_security_log';
        
        // Create log table if not exists
        $wpdb->query("CREATE TABLE IF NOT EXISTS $log_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            user_id int(11),
            ip_address varchar(45),
            user_agent text,
            details text,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_event_type (event_type),
            INDEX idx_user_id (user_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $wpdb->insert($log_table, array(
            'event_type' => $event_type,
            'user_id' => $user_id,
            'ip_address' => self::get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'details' => $details
        ), array('%s', '%d', '%s', '%s', '%s'));
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
?>
