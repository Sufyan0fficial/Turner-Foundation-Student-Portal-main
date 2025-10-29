<?php
if (!defined('ABSPATH')) {
    exit;
}

class TFSP_Attendance_Activator {
    
    public static function activate() {
        self::create_tables();
        self::add_capabilities();
        self::insert_default_policy();
    }
    
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'tfsp_';
        
        // Sessions table
        $sql_sessions = "CREATE TABLE {$prefix}sessions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            class_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
            session_date DATE NOT NULL,
            start_time TIME NULL,
            end_time TIME NULL,
            subject VARCHAR(120) NULL,
            topic VARCHAR(255) NULL,
            is_postponed TINYINT(1) DEFAULT 0,
            rescheduled_to_session_id BIGINT UNSIGNED NULL,
            PRIMARY KEY (id),
            KEY class_date (class_id, session_date),
            KEY resched (rescheduled_to_session_id)
        ) $charset_collate;";
        
        // Attendance records
        $sql_attendance = "CREATE TABLE {$prefix}attendance_records (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id BIGINT UNSIGNED NOT NULL,
            student_id BIGINT UNSIGNED NOT NULL,
            status ENUM('present','excused_absence','did_not_attend','postponed','late','remote') NOT NULL,
            check_in_at DATETIME NULL,
            check_out_at DATETIME NULL,
            notes VARCHAR(255) NULL,
            marked_by BIGINT UNSIGNED NULL,
            marked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_session_student (session_id, student_id),
            KEY idx_student (student_id, id),
            KEY idx_session (session_id)
        ) $charset_collate;";
        
        // Attendance policy
        $sql_policy = "CREATE TABLE {$prefix}attendance_policy (
            id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
            present_weight DECIMAL(3,2) DEFAULT 1.00,
            excused_absence_weight DECIMAL(3,2) DEFAULT 1.00,
            did_not_attend_weight DECIMAL(3,2) DEFAULT 0.00,
            late_weight DECIMAL(3,2) DEFAULT 1.00,
            remote_weight DECIMAL(3,2) DEFAULT 1.00,
            exclude_postponed_from_denominator TINYINT(1) DEFAULT 1
        ) $charset_collate;";
        
        // Student documents (enhanced)
        $sql_documents = "CREATE TABLE {$prefix}student_documents (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            student_id BIGINT UNSIGNED NOT NULL,
            doc_type VARCHAR(120) NOT NULL,
            status ENUM('Submitted','Accepted','Sent back to student for further development') NOT NULL DEFAULT 'Submitted',
            notes VARCHAR(255) NULL,
            file_path VARCHAR(500) NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY student_doc (student_id, doc_type)
        ) $charset_collate;";
        
        // Roadmap items
        $sql_roadmap = "CREATE TABLE {$prefix}roadmap_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(160) NOT NULL,
            description VARCHAR(500),
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Challenges
        $sql_challenges = "CREATE TABLE {$prefix}challenges (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            roadmap_item_id BIGINT UNSIGNED NOT NULL,
            program_year SMALLINT NOT NULL,
            title VARCHAR(180) NOT NULL,
            difficulty ENUM('easy','medium','hard') NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY year_item (program_year, roadmap_item_id)
        ) $charset_collate;";
        
        // Student challenges
        $sql_student_challenges = "CREATE TABLE {$prefix}student_challenges (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            student_id BIGINT UNSIGNED NOT NULL,
            challenge_id BIGINT UNSIGNED NOT NULL,
            status ENUM('not_started','in_progress','completed','needs_revision') DEFAULT 'not_started',
            progress_percent TINYINT DEFAULT 0,
            due_date DATE NULL,
            coach_notes VARCHAR(500),
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_student_ch (student_id, challenge_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_sessions);
        dbDelta($sql_attendance);
        dbDelta($sql_policy);
        dbDelta($sql_documents);
        dbDelta($sql_roadmap);
        dbDelta($sql_challenges);
        dbDelta($sql_student_challenges);
    }
    
    private static function add_capabilities() {
        $admin = get_role('administrator');
        $editor = get_role('editor');
        
        $caps = array(
            'manage_attendance',
            'view_student_360',
            'manage_documents',
            'manage_challenges',
            'export_attendance'
        );
        
        foreach ($caps as $cap) {
            if ($admin) $admin->add_cap($cap);
            if ($editor) $editor->add_cap($cap);
        }
    }
    
    private static function insert_default_policy() {
        global $wpdb;
        $table = $wpdb->prefix . 'tfsp_attendance_policy';
        
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($existing == 0) {
            $wpdb->insert($table, array('id' => 1));
        }
    }
}
