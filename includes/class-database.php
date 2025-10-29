<?php
/**
 * Database Management Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class TFSP_Database {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Private constructor to prevent direct instantiation
    }
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Students table
        $table_students = $wpdb->prefix . 'tfsp_students';
        $sql_students = "CREATE TABLE $table_students (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            student_id varchar(50) UNIQUE,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20),
            date_of_birth date,
            graduation_year year,
            gpa decimal(3,2),
            school_name varchar(200),
            counselor_name varchar(100),
            counselor_email varchar(100),
            parent_name varchar(100),
            parent_email varchar(100),
            parent_phone varchar(20),
            emergency_contact varchar(100),
            emergency_phone varchar(20),
            status varchar(20) DEFAULT 'active',
            enrollment_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_activity datetime,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY status (status),
            KEY graduation_year (graduation_year)
        ) $charset_collate;";
        
        // College Applications table
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        $sql_applications = "CREATE TABLE $table_applications (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id mediumint(9) NOT NULL,
            application_type varchar(50) NOT NULL,
            title varchar(200) NOT NULL,
            description text,
            college_name varchar(200),
            application_deadline date,
            status varchar(20) DEFAULT 'not_started',
            progress_percentage int(3) DEFAULT 0,
            priority varchar(10) DEFAULT 'medium',
            requirements text,
            notes text,
            started_date datetime,
            completed_date datetime,
            submitted_date datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY student_id (student_id),
            KEY status (status),
            KEY application_type (application_type),
            KEY application_deadline (application_deadline)
        ) $charset_collate;";
        
        // Documents table
        $table_documents = $wpdb->prefix . 'tfsp_documents';
        $sql_documents = "CREATE TABLE $table_documents (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id mediumint(9) NOT NULL,
            application_id mediumint(9),
            document_name varchar(255) NOT NULL,
            original_filename varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size int(11) NOT NULL,
            file_type varchar(10) NOT NULL,
            mime_type varchar(100) NOT NULL,
            document_category varchar(50),
            status varchar(20) DEFAULT 'pending',
            reviewed_by mediumint(9),
            reviewed_date datetime,
            review_notes text,
            version int(3) DEFAULT 1,
            is_latest tinyint(1) DEFAULT 1,
            uploaded_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY student_id (student_id),
            KEY application_id (application_id),
            KEY status (status),
            KEY document_category (document_category)
        ) $charset_collate;";
        
        // Meetings table
        $table_meetings = $wpdb->prefix . 'tfsp_meetings';
        $sql_meetings = "CREATE TABLE $table_meetings (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id mediumint(9) NOT NULL,
            counselor_id mediumint(9),
            meeting_date date NOT NULL,
            meeting_time time NOT NULL,
            duration int(3) DEFAULT 60,
            meeting_type varchar(50) NOT NULL,
            meeting_format varchar(20) DEFAULT 'in_person',
            location varchar(200),
            zoom_link varchar(500),
            status varchar(20) DEFAULT 'scheduled',
            agenda text,
            student_notes text,
            counselor_notes text,
            follow_up_required tinyint(1) DEFAULT 0,
            follow_up_date date,
            reminder_sent tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY student_id (student_id),
            KEY counselor_id (counselor_id),
            KEY meeting_date (meeting_date),
            KEY status (status)
        ) $charset_collate;";
        
        // Progress tracking table
        $table_progress = $wpdb->prefix . 'tfsp_progress';
        $sql_progress = "CREATE TABLE $table_progress (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            application_index int(3) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            completed_date datetime,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_application (user_id, application_index),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Notifications table
        $table_notifications = $wpdb->prefix . 'tfsp_notifications';
        $sql_notifications = "CREATE TABLE $table_notifications (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(200) NOT NULL,
            message text NOT NULL,
            action_url varchar(500),
            is_read tinyint(1) DEFAULT 0,
            priority varchar(10) DEFAULT 'normal',
            expires_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY type (type),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Activity log table
        $table_activity = $wpdb->prefix . 'tfsp_activity';
        $sql_activity = "CREATE TABLE $table_activity (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            action varchar(100) NOT NULL,
            object_type varchar(50),
            object_id mediumint(9),
            description text,
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY object_type (object_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_students);
        dbDelta($sql_applications);
        dbDelta($sql_documents);
        dbDelta($sql_meetings);
        dbDelta($sql_progress);
        dbDelta($sql_notifications);
        dbDelta($sql_activity);
        
        // Insert default application types
        $this->insert_default_applications();
    }
    
    private function insert_default_applications() {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        
        // Check if default applications already exist
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM $table_applications WHERE student_id = 0");
        if ($existing > 0) {
            return; // Already inserted
        }
        
        $default_applications = array(
            array(
                'application_type' => 'academic_resume',
                'title' => 'Academic Resume',
                'description' => 'What is the goal?',
                'requirements' => 'Create a comprehensive academic resume highlighting your achievements',
                'priority' => 'high'
            ),
            array(
                'application_type' => 'personal_essay',
                'title' => 'Personal Essay',
                'description' => 'How do you showcase progress?',
                'requirements' => 'Write a compelling personal essay that showcases your unique perspective',
                'priority' => 'high'
            ),
            array(
                'application_type' => 'recommendation_letters',
                'title' => 'Recommendation Letters',
                'description' => 'How do you achieve this goal?',
                'requirements' => 'Secure recommendation letters from teachers, mentors, or supervisors',
                'priority' => 'high'
            ),
            array(
                'application_type' => 'transcript_request',
                'title' => 'Transcript',
                'description' => 'Why is this goal important to you?',
                'requirements' => 'Request official transcripts from your educational institutions',
                'priority' => 'high'
            ),
            array(
                'application_type' => 'financial_aid',
                'title' => 'Financial Aid',
                'description' => 'What is the goal related to this topic?',
                'requirements' => 'Complete FAFSA and other financial aid applications',
                'priority' => 'high'
            ),
            array(
                'application_type' => 'community_service',
                'title' => 'Community Service',
                'description' => 'What is the goal?',
                'requirements' => 'Document your community service hours and experiences',
                'priority' => 'medium'
            ),
            array(
                'application_type' => 'college_list',
                'title' => 'Create Interest list of Colleges',
                'description' => 'How will you research on colleges?',
                'requirements' => 'Create and research your list of target colleges and universities',
                'priority' => 'high'
            ),
            array(
                'application_type' => 'college_tours',
                'title' => 'College Tours',
                'description' => 'How will you achieve this goal?',
                'requirements' => 'Plan and schedule visits to your target colleges',
                'priority' => 'medium'
            ),
            array(
                'application_type' => 'fafsa',
                'title' => 'FAFSA',
                'description' => 'What is the goal related to this topic?',
                'requirements' => 'Complete the Free Application for Federal Student Aid (FAFSA)',
                'priority' => 'high'
            ),
            array(
                'application_type' => 'admissions_tests',
                'title' => 'College Admissions Tests',
                'description' => 'What is the goal related to this topic?',
                'requirements' => 'Prepare for and take required standardized tests (SAT/ACT)',
                'priority' => 'high'
            )
        );
        
        foreach ($default_applications as $app) {
            $wpdb->insert(
                $table_applications,
                array_merge($app, array(
                    'student_id' => 0, // Template applications
                    'status' => 'template',
                    'created_at' => current_time('mysql')
                ))
            );
        }
    }
    
    public function get_student_applications($student_id) {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_applications 
             WHERE student_id = %d 
             ORDER BY priority DESC, application_deadline ASC",
            $student_id
        ));
    }
    
    public function get_student_documents($student_id, $application_id = null) {
        global $wpdb;
        
        $table_documents = $wpdb->prefix . 'tfsp_documents';
        
        $where_clause = "WHERE student_id = %d";
        $params = array($student_id);
        
        if ($application_id) {
            $where_clause .= " AND application_id = %d";
            $params[] = $application_id;
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_documents 
             $where_clause 
             ORDER BY uploaded_date DESC",
            $params
        ));
    }
    
    public function get_student_meetings($student_id, $status = null) {
        global $wpdb;
        
        $table_meetings = $wpdb->prefix . 'tfsp_meetings';
        
        $where_clause = "WHERE student_id = %d";
        $params = array($student_id);
        
        if ($status) {
            $where_clause .= " AND status = %s";
            $params[] = $status;
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_meetings 
             $where_clause 
             ORDER BY meeting_date ASC, meeting_time ASC",
            $params
        ));
    }
    
    public function log_activity($user_id, $action, $object_type = null, $object_id = null, $description = null) {
        global $wpdb;
        
        $table_activity = $wpdb->prefix . 'tfsp_activity';
        
        return $wpdb->insert(
            $table_activity,
            array(
                'user_id' => $user_id,
                'action' => $action,
                'object_type' => $object_type,
                'object_id' => $object_id,
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'created_at' => current_time('mysql')
            )
        );
    }
    
    public function create_notification($user_id, $type, $title, $message, $action_url = null, $priority = 'normal') {
        global $wpdb;
        
        $table_notifications = $wpdb->prefix . 'tfsp_notifications';
        
        return $wpdb->insert(
            $table_notifications,
            array(
                'user_id' => $user_id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'action_url' => $action_url,
                'priority' => $priority,
                'created_at' => current_time('mysql')
            )
        );
    }
    
    public function get_dashboard_stats() {
        global $wpdb;
        
        $table_students = $wpdb->prefix . 'tfsp_students';
        $table_applications = $wpdb->prefix . 'tfsp_applications';
        $table_documents = $wpdb->prefix . 'tfsp_documents';
        $table_meetings = $wpdb->prefix . 'tfsp_meetings';
        
        $stats = array();
        
        // Total students
        $stats['total_students'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_students WHERE status = 'active'");
        
        // Active applications
        $stats['active_applications'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_applications WHERE status IN ('in_progress', 'review')");
        
        // Completed applications
        $stats['completed_applications'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_applications WHERE status = 'completed'");
        
        // Pending documents
        $stats['pending_documents'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_documents WHERE status = 'pending'");
        
        // Upcoming meetings
        $stats['upcoming_meetings'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_meetings WHERE meeting_date >= %s AND status = 'scheduled'",
            current_time('Y-m-d')
        ));
        
        return $stats;
    }
}
