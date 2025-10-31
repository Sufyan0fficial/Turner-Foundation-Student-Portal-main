<?php
if (!defined('ABSPATH')) exit;

class TFSP_Database_Activator {
    
    public static function activate() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();

        // Students table
        $sql = "CREATE TABLE {$wpdb->prefix}tfsp_students (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            student_id varchar(50) UNIQUE,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            student_phone varchar(20) DEFAULT NULL,
            phone varchar(20) DEFAULT NULL,
            date_of_birth date DEFAULT NULL,
            graduation_year year DEFAULT NULL,
            gpa decimal(3,2) DEFAULT NULL,
            school_name varchar(200) DEFAULT NULL,
            counselor_name varchar(100) DEFAULT NULL,
            counselor_email varchar(100) DEFAULT NULL,
            parent_name varchar(100) DEFAULT NULL,
            parent_email varchar(100) DEFAULT NULL,
            parent_phone varchar(20) DEFAULT NULL,
            emergency_contact varchar(100) DEFAULT NULL,
            emergency_phone varchar(20) DEFAULT NULL,
            classification varchar(20) DEFAULT NULL,
            cohort_year varchar(50) DEFAULT NULL,
            shirt_size varchar(10) DEFAULT NULL,
            blazer_size varchar(10) DEFAULT NULL,
            waiver_status varchar(20) DEFAULT 'pending',
            status varchar(20) DEFAULT 'active',
            enrollment_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_activity datetime DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY status (status),
            KEY graduation_year (graduation_year)
        ) $charset_collate;";
        dbDelta($sql);

        // Applications table
        $sql = "CREATE TABLE {$wpdb->prefix}tfsp_applications (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id mediumint(9) DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            application_type varchar(50) NOT NULL DEFAULT 'regular',
            title varchar(200) DEFAULT NULL,
            description text DEFAULT NULL,
            college_name varchar(200) DEFAULT NULL,
            application_deadline date DEFAULT NULL,
            deadline date DEFAULT NULL,
            status varchar(20) DEFAULT 'not_started',
            progress_percentage int(3) DEFAULT 0,
            priority varchar(10) DEFAULT 'medium',
            requirements text DEFAULT NULL,
            notes text DEFAULT NULL,
            started_date datetime DEFAULT NULL,
            completed_date datetime DEFAULT NULL,
            submitted_date datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY student_id (student_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY application_type (application_type),
            KEY application_deadline (application_deadline),
            KEY deadline (deadline)
        ) $charset_collate;";
        dbDelta($sql);

        // Documents table
        $sql = "CREATE TABLE {$wpdb->prefix}tfsp_documents (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            student_id mediumint(9) DEFAULT NULL,
            application_id mediumint(9) DEFAULT NULL,
            document_type varchar(50) NOT NULL,
            document_name varchar(255) DEFAULT NULL,
            file_name varchar(255) NOT NULL,
            original_filename varchar(255) DEFAULT NULL,
            file_path varchar(500) NOT NULL,
            file_url varchar(255) DEFAULT NULL,
            file_size varchar(50) DEFAULT NULL,
            file_type varchar(10) DEFAULT NULL,
            mime_type varchar(100) DEFAULT NULL,
            document_category varchar(50) DEFAULT NULL,
            upload_date datetime DEFAULT CURRENT_TIMESTAMP,
            uploaded_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'submitted',
            approval_status varchar(20) DEFAULT 'submitted',
            approved_by bigint(20) DEFAULT NULL,
            approved_at datetime DEFAULT NULL,
            reviewed_by mediumint(9) DEFAULT NULL,
            reviewed_date datetime DEFAULT NULL,
            review_notes text DEFAULT NULL,
            version int(3) DEFAULT 1,
            is_latest tinyint(1) DEFAULT 1,
            is_community_service_letter tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY student_id (student_id),
            KEY application_id (application_id),
            KEY status (status),
            KEY document_category (document_category)
        ) $charset_collate;";
        dbDelta($sql);

        // Student Progress table
        $sql = "CREATE TABLE {$wpdb->prefix}tfsp_student_progress (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            step_key varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            notes text DEFAULT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY student_id (student_id),
            KEY step_key (step_key)
        ) $charset_collate;";
        dbDelta($sql);

        // Attendance Sessions table (new unified sessions calendar)
        $sql = "CREATE TABLE {$wpdb->prefix}tfsp_sessions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            class_id mediumint(9) DEFAULT 1,
            session_date date NOT NULL,
            start_time time DEFAULT NULL,
            end_time time DEFAULT NULL,
            subject varchar(100) DEFAULT NULL,
            topic varchar(255) DEFAULT NULL,
            is_postponed tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY class_id (class_id),
            KEY session_date (session_date)
        ) $charset_collate;";
        dbDelta($sql);

        // Attendance Records table (add session_id + audit fields to align with new system)
        $sql = "CREATE TABLE {$wpdb->prefix}tfsp_attendance_records (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            session_id mediumint(9) DEFAULT NULL,
            session_date date NOT NULL,
            status varchar(20) DEFAULT 'present',
            notes text DEFAULT NULL,
            marked_by bigint(20) DEFAULT NULL,
            marked_at datetime DEFAULT NULL,
            check_in_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY student_session (student_id, session_id),
            UNIQUE KEY student_date (student_id, session_date),
            KEY student_id (student_id),
            KEY session_id (session_id),
            KEY session_date (session_date)
        ) $charset_collate;";
        dbDelta($sql);

        // General Attendance table
        $sql = "CREATE TABLE {$wpdb->prefix}tfsp_attendance (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            date date NOT NULL,
            status varchar(20) DEFAULT 'present',
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY student_date (student_id, date),
            KEY student_id (student_id),
            KEY date (date)
        ) $charset_collate;";
        dbDelta($sql);

        // Coach Sessions table
        $sql = "CREATE TABLE {$wpdb->prefix}tfsp_coach_sessions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            session_date date NOT NULL,
            session_time time DEFAULT NULL,
            status varchar(20) DEFAULT 'scheduled',
            session_type varchar(50) DEFAULT NULL,
            notes text DEFAULT NULL,
            coach_notes text DEFAULT NULL,
            created_by bigint(20) DEFAULT NULL,
            calendly_event_id varchar(255) DEFAULT NULL,
            calendly_uri varchar(500) DEFAULT NULL,
            auto_created tinyint(1) DEFAULT 0,
            webhook_data text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY calendly_event_id (calendly_event_id),
            KEY student_id (student_id),
            KEY session_date (session_date)
        ) $charset_collate;";
        dbDelta($sql);

        // Calendly Settings table
        $sql = "CREATE TABLE {$wpdb->prefix}tfsp_calendly_settings (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";
        dbDelta($sql);

        // Messages table
        $sql = "CREATE TABLE {$wpdb->prefix}tfsp_messages (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) NOT NULL,
            recipient_id bigint(20) DEFAULT NULL,
            student_id bigint(20) DEFAULT NULL,
            message_type varchar(20) DEFAULT NULL,
            subject varchar(255) NOT NULL,
            message text NOT NULL,
            priority varchar(20) DEFAULT 'normal',
            status varchar(20) DEFAULT 'unread',
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY sender_id (sender_id),
            KEY recipient_id (recipient_id),
            KEY student_id (student_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Challenges table
        $sql = "CREATE TABLE {$wpdb->prefix}tfsp_challenges (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text DEFAULT NULL,
            difficulty varchar(20) DEFAULT NULL,
            grade_level varchar(20) DEFAULT NULL,
            category varchar(50) DEFAULT NULL,
            points int DEFAULT 0,
            roadmap_step varchar(50) DEFAULT NULL,
            target_percentage int DEFAULT 0,
            target_year varchar(20) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql);

        // Recommendations table
        $sql = "CREATE TABLE {$wpdb->prefix}tfsp_recommendations (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            recommender_name varchar(100) NOT NULL,
            recommender_email varchar(100) DEFAULT NULL,
            recommender_title varchar(100) DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            submitted_date datetime DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY student_id (student_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Upcoming Sessions table
        $sql = "CREATE TABLE {$wpdb->prefix}tfsp_upcoming_sessions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            date_time datetime NOT NULL,
            description text DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql);

        // Resources table
        $sql = "CREATE TABLE {$wpdb->prefix}tfsp_resources (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            description text DEFAULT NULL,
            file_path varchar(255) DEFAULT NULL,
            url varchar(255) DEFAULT NULL,
            uploaded_by bigint(20) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql);

        // Settings table
        $sql = "CREATE TABLE {$wpdb->prefix}tfsp_settings (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value text NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";
        dbDelta($sql);

        // Portal Admins table (separate from WordPress users)
        $sql = "CREATE TABLE {$wpdb->prefix}tfsp_portal_admins (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            username varchar(60) NOT NULL,
            password varchar(255) NOT NULL,
            email varchar(100) NOT NULL,
            full_name varchar(100) DEFAULT NULL,
            role varchar(20) DEFAULT 'admin',
            is_active tinyint(1) DEFAULT 1,
            last_login datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY username (username)
        ) $charset_collate;";
        dbDelta($sql);

        // Insert default settings
        self::insert_default_settings();
        
        // Insert default advisor settings
        self::insert_default_advisor_settings();
        
        // Insert default attendance policy
        self::insert_default_attendance_policy();
        
        // Create default portal admin
        self::create_default_admin();
        
        // Create upload directory
        self::create_upload_directory();

        // Finalize: ensure schema matches latest (safe no-ops if already applied)
        self::maybe_migrate_attendance_schema();
        
        // Add waiver status field if it doesn't exist
        self::maybe_add_waiver_status_field();
    }
    
    private static function insert_default_advisor_settings() {
        global $wpdb;
        
        $default_advisor_settings = array(
            array('setting_key' => 'advisor_title', 'setting_value' => 'College and Career Coach'),
            array('setting_key' => 'advisor_tagline', 'setting_value' => 'Dedicated to your success'),
            array('setting_key' => 'stat1_number', 'setting_value' => '500+'),
            array('setting_key' => 'stat1_label', 'setting_value' => 'Students Helped'),
            array('setting_key' => 'stat2_number', 'setting_value' => '95%'),
            array('setting_key' => 'stat2_label', 'setting_value' => 'Success Rate'),
            array('setting_key' => 'meeting_link', 'setting_value' => '#'),
            array('setting_key' => 'recommendation_link', 'setting_value' => ''),
            array('setting_key' => 'recommendation_email', 'setting_value' => ''),
            array('setting_key' => 'coach_email', 'setting_value' => '')
        );

        foreach ($default_advisor_settings as $setting) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}tfsp_advisor_settings WHERE setting_key = %s",
                $setting['setting_key']
            ));
            
            if (!$exists) {
                $wpdb->insert($wpdb->prefix . 'tfsp_advisor_settings', $setting);
            }
        }
    }

    /**
     * Ensure attendance tables have required columns/constraints for the unified system.
     * Safe to run multiple times.
     */
    public static function maybe_migrate_attendance_schema() {
        global $wpdb;

        // Ensure tfsp_sessions exists (dbDelta above should have created it)
        $sessions_table = $wpdb->prefix . 'tfsp_sessions';
        if (!$wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $sessions_table))) {
            // Create minimal table if for some reason dbDelta was skipped
            $wpdb->query("CREATE TABLE $sessions_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                class_id mediumint(9) DEFAULT 1,
                session_date date NOT NULL,
                start_time time DEFAULT NULL,
                end_time time DEFAULT NULL,
                subject varchar(100) DEFAULT NULL,
                topic varchar(255) DEFAULT NULL,
                is_postponed tinyint(1) DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id), KEY session_date (session_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }

        // Ensure tfsp_attendance_records has required columns
        $records_table = $wpdb->prefix . 'tfsp_attendance_records';
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $records_table))) {
            $columns = $wpdb->get_col("SHOW COLUMNS FROM $records_table", 0);
            $alter = [];
            if (!in_array('session_id', $columns)) {
                $alter[] = "ADD COLUMN session_id mediumint(9) NULL AFTER student_id";
            }
            if (!in_array('marked_by', $columns)) {
                $alter[] = "ADD COLUMN marked_by bigint(20) NULL AFTER notes";
            }
            if (!in_array('marked_at', $columns)) {
                $alter[] = "ADD COLUMN marked_at datetime NULL AFTER marked_by";
            }
            if (!in_array('check_in_at', $columns)) {
                $alter[] = "ADD COLUMN check_in_at datetime NULL AFTER marked_at";
            }
            if (!empty($alter)) {
                $wpdb->query("ALTER TABLE $records_table " . implode(', ', $alter));
            }

            // Ensure indexes/unique keys
            $indexes = $wpdb->get_results("SHOW INDEX FROM $records_table", ARRAY_A);
            $index_names = array_map(function($r){ return $r['Key_name']; }, $indexes);
            if (!in_array('student_session', $index_names)) {
                // Drop conflicting unique on student_date if present and session_id is available
                if (in_array('student_date', $index_names)) {
                    $wpdb->query("ALTER TABLE $records_table DROP INDEX student_date");
                }
                $wpdb->query("ALTER TABLE $records_table ADD UNIQUE KEY student_session (student_id, session_id)");
            }
        }
    }
    
    private static function insert_default_attendance_policy() {
        global $wpdb;
        
        $exists = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_attendance_policy");
        if ($exists == 0) {
            $wpdb->insert($wpdb->prefix . 'tfsp_attendance_policy', array('id' => 1));
        }
    }
    
    private static function create_default_admin() {
        global $wpdb;
        
        // Check if default admin exists
        $exists = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}tfsp_portal_admins WHERE username = 'admin'");
        
        if (!$exists) {
            // Create default admin with password: YCAMAdmin2024!
            $wpdb->insert(
                $wpdb->prefix . 'tfsp_portal_admins',
                array(
                    'username' => 'admin',
                    'password' => password_hash('YCAMAdmin2024!', PASSWORD_DEFAULT),
                    'email' => 'admin@ycam.org',
                    'full_name' => 'YCAM Administrator',
                    'role' => 'admin',
                    'is_active' => 1
                )
            );
        }
    }
    
    private static function insert_default_settings() {
        global $wpdb;
        
        $default_settings = array(
            array('setting_key' => 'advisor_title', 'setting_value' => 'College and Career Coach'),
            array('setting_key' => 'advisor_tagline', 'setting_value' => 'Dedicated to your success'),
            array('setting_key' => 'stat1_number', 'setting_value' => '500+'),
            array('setting_key' => 'stat1_label', 'setting_value' => 'Students Helped'),
            array('setting_key' => 'stat2_number', 'setting_value' => '95%'),
            array('setting_key' => 'stat2_label', 'setting_value' => 'Success Rate'),
            array('setting_key' => 'meeting_link', 'setting_value' => '#'),
            array('setting_key' => 'recommendation_link', 'setting_value' => '#'),
            array('setting_key' => 'recommendation_email', 'setting_value' => 'recommendation@example.com')
        );

        foreach ($default_settings as $setting) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}tfsp_settings WHERE setting_key = %s",
                $setting['setting_key']
            ));
            
            if (!$exists) {
                $wpdb->insert($wpdb->prefix . 'tfsp_settings', $setting);
            }
        }
    }
    
    private static function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $tfsp_upload_dir = $upload_dir['basedir'] . '/tfsp-documents';
        
        if (!file_exists($tfsp_upload_dir)) {
            wp_mkdir_p($tfsp_upload_dir);
            
            // Create .htaccess for security
            $htaccess_content = "Options -Indexes\n<Files *.php>\nDeny from all\n</Files>";
            file_put_contents($tfsp_upload_dir . '/.htaccess', $htaccess_content);
        }
    }
    
    /**
     * Add cohort_year field to students table if it doesn't exist.
     * Safe to run multiple times.
     */
    public static function maybe_add_cohort_year_field() {
        global $wpdb;
        
        $students_table = $wpdb->prefix . 'tfsp_students';
        
        // Check if cohort_year column exists
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM $students_table LIKE %s",
            'cohort_year'
        ));
        
        if (empty($column_exists)) {
            // Add the cohort_year column
            $wpdb->query("ALTER TABLE $students_table ADD COLUMN cohort_year varchar(50) DEFAULT NULL AFTER classification");
        }
    }
}
