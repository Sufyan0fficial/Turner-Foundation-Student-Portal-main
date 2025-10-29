<?php
/**
 * YCAM Portal - Database Management Class v3.0
 * Handles all database table creation and updates
 */

if (!defined('ABSPATH')) {
    exit;
}

class TFSP_Database_V3 {
    
    /**
     * Create all required database tables
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // 1. Students Table (Enhanced)
        self::create_students_table($charset_collate);
        
        // 2. Applications Table
        self::create_applications_table($charset_collate);
        
        // 3. Documents Table (Enhanced with approval)
        self::create_documents_table($charset_collate);
        
        // 4. Meetings Table
        self::create_meetings_table($charset_collate);
        
        // 5. Challenges Table
        self::create_challenges_table($charset_collate);
        
        // 6. Student Progress Table
        self::create_progress_table($charset_collate);
        
        // 7. Messages Table (Enhanced)
        self::create_messages_table($charset_collate);
        
        // 8. Attendance Table (Enhanced)
        self::create_attendance_table($charset_collate);
        
        // 9. Coach Sessions Table (NEW)
        self::create_coach_sessions_table($charset_collate);
        
        // 10. Settings Table (NEW)
        self::create_settings_table($charset_collate);
        
        // 11. Document Approvals Log (NEW)
        self::create_document_approvals_table($charset_collate);
        
        // Insert default settings
        self::insert_default_settings();
    }
    
    private static function create_students_table($charset_collate) {
        global $wpdb;
        $table = $wpdb->prefix . 'tfsp_students';
        
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            student_id varchar(20) NOT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            parent_name varchar(100) NULL,
            email varchar(100) NOT NULL,
            parent_email varchar(100) NULL,
            parent_phone varchar(20) NULL,
            student_phone varchar(20) NULL,
            classification varchar(20) NULL,
            shirt_size varchar(10) NULL,
            blazer_size varchar(10) NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY email (email),
            KEY status (status),
            KEY classification (classification)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    private static function create_applications_table($charset_collate) {
        global $wpdb;
        $table = $wpdb->prefix . 'tfsp_applications';
        
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            college_name varchar(200) NOT NULL,
            application_type varchar(50) NOT NULL,
            deadline date,
            status varchar(20) DEFAULT 'not_started',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    private static function create_documents_table($charset_collate) {
        global $wpdb;
        $table = $wpdb->prefix . 'tfsp_documents';
        
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            document_type varchar(50) NOT NULL,
            document_category varchar(50) DEFAULT 'general',
            is_community_service_letter tinyint(1) DEFAULT 0,
            file_name varchar(255) NOT NULL,
            file_path varchar(255) NOT NULL,
            upload_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'pending',
            approval_status varchar(20) DEFAULT 'pending',
            approved_by bigint(20) NULL,
            approved_at datetime NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY approval_status (approval_status)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    private static function create_meetings_table($charset_collate) {
        global $wpdb;
        $table = $wpdb->prefix . 'tfsp_meetings';
        
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            meeting_date date NOT NULL,
            meeting_time time NOT NULL,
            meeting_type varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    private static function create_challenges_table($charset_collate) {
        global $wpdb;
        $table = $wpdb->prefix . 'tfsp_challenges';
        
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            type varchar(20) DEFAULT 'standard',
            linked_steps text NULL,
            difficulty varchar(20) DEFAULT 'medium',
            target_classification varchar(20) NULL,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY active (active)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    private static function create_progress_table($charset_collate) {
        global $wpdb;
        $table = $wpdb->prefix . 'tfsp_student_progress';
        
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            step varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            notes text,
            completed_at datetime NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY student_id (student_id),
            KEY step (step)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    private static function create_messages_table($charset_collate) {
        global $wpdb;
        $table = $wpdb->prefix . 'tfsp_messages';
        
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            recipient_id bigint(20) NULL,
            sender_id bigint(20) NULL,
            parent_message_id mediumint(9) NULL,
            message_type varchar(20) DEFAULT 'admin',
            subject varchar(255),
            message text,
            status varchar(20) DEFAULT 'unread',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY student_id (student_id),
            KEY recipient_id (recipient_id),
            KEY sender_id (sender_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    private static function create_attendance_table($charset_collate) {
        global $wpdb;
        $table = $wpdb->prefix . 'tfsp_attendance';
        
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            date date NOT NULL,
            session_week int NULL,
            session_type varchar(50) DEFAULT 'weekly_program',
            status varchar(20) DEFAULT 'present',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY student_id (student_id),
            KEY date (date),
            KEY student_date (student_id, date)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    private static function create_coach_sessions_table($charset_collate) {
        global $wpdb;
        $table = $wpdb->prefix . 'tfsp_coach_sessions';
        
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            session_date date NOT NULL,
            session_time time NULL,
            status varchar(20) DEFAULT 'scheduled',
            session_type varchar(50) DEFAULT 'one_on_one',
            notes text,
            coach_notes text,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY student_id (student_id),
            KEY session_date (session_date),
            KEY status (status)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    private static function create_settings_table($charset_collate) {
        global $wpdb;
        $table = $wpdb->prefix . 'tfsp_settings';
        
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL UNIQUE,
            setting_value text,
            setting_type varchar(20) DEFAULT 'text',
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY setting_key (setting_key)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    private static function create_document_approvals_table($charset_collate) {
        global $wpdb;
        $table = $wpdb->prefix . 'tfsp_document_approvals';
        
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            document_id mediumint(9) NOT NULL,
            action varchar(20) NOT NULL,
            action_by bigint(20) NOT NULL,
            action_notes text,
            action_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY document_id (document_id),
            KEY action_by (action_by)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    private static function insert_default_settings() {
        global $wpdb;
        $table = $wpdb->prefix . 'tfsp_settings';
        
        $default_settings = array(
            array('portal_name', 'YCAM Mentorship Program Student Portal', 'text'),
            array('admin_portal_name', 'YCAM Mentorship Program Participant Portal', 'text'),
            array('attendance_view_title', 'YCAM Mentorship Program Weekly Attendance', 'text'),
            array('coach_title', 'College and Career Coach', 'text'),
            array('schedule_button_text', 'Schedule Your One-on-One Session', 'text'),
            array('program_start_date', '2025-11-01', 'date'),
            array('program_weeks', '14', 'number'),
            array('calendly_embed_code', '', 'textarea'),
            array('enable_coach_sessions', '1', 'boolean'),
            array('enable_document_approval', '1', 'boolean')
        );
        
        foreach ($default_settings as $setting) {
            $wpdb->query($wpdb->prepare(
                "INSERT INTO $table (setting_key, setting_value, setting_type) 
                VALUES (%s, %s, %s) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                $setting[0], $setting[1], $setting[2]
            ));
        }
    }
    
    /**
     * Get a setting value
     */
    public static function get_setting($key, $default = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'tfsp_settings';
        
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM $table WHERE setting_key = %s",
            $key
        ));
        
        return $value !== null ? $value : $default;
    }
    
    /**
     * Update a setting value
     */
    public static function update_setting($key, $value) {
        global $wpdb;
        $table = $wpdb->prefix . 'tfsp_settings';
        
        return $wpdb->query($wpdb->prepare(
            "INSERT INTO $table (setting_key, setting_value) 
            VALUES (%s, %s) 
            ON DUPLICATE KEY UPDATE setting_value = %s, updated_at = CURRENT_TIMESTAMP",
            $key, $value, $value
        ));
    }
}
