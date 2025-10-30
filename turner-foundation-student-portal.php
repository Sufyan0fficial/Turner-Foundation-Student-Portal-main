<?php
/*
Plugin Name: YCAM Mentorship Program Student Portal
Description: Comprehensive student college application tracking and document management system
Version: 3.0.0
Author: Turner Foundation
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TFSP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TFSP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('TFSP_VERSION', '3.2.0');

// Main plugin class
class TurnerFoundationStudentPortal {

    public function __construct() {
        add_action('init', array($this, 'init'));
        // Register pretty routes early
        add_action('init', array($this, 'register_portal_routes'));
        add_filter('query_vars', array($this, 'register_portal_query_vars'));
        add_action('template_redirect', array($this, 'handle_portal_routes'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        // AJAX Handlers
        add_action('wp_ajax_tfsp_update_progress', array($this, 'handle_update_progress'));
        add_action('wp_ajax_tfsp_send_message', array($this, 'handle_send_message'));
        add_action('wp_ajax_tfsp_create_challenge', array($this, 'handle_create_challenge'));
        add_action('wp_ajax_tfsp_update_document_status', array($this, 'handle_update_document_status'));
    
    }

    public function init() {
        // Load AJAX handlers
        $this->load_ajax_handlers();

        // Load Calendly webhook handler
        require_once TFSP_PLUGIN_PATH . 'includes/calendly-webhook-handler.php';

        // Load Calendly manual sync (for Basic plan)
        require_once TFSP_PLUGIN_PATH . 'includes/calendly-manual-sync.php';

        // Load attendance system (REST, export, helpers, admin menu)
        if (file_exists(TFSP_PLUGIN_PATH . 'includes/attendance-integration.php')) {
            require_once TFSP_PLUGIN_PATH . 'includes/attendance-integration.php';
        }

        // Ensure DB schema is up-to-date for attendance (sessions + records columns)
        if (class_exists('TFSP_Database_Activator') && method_exists('TFSP_Database_Activator', 'maybe_migrate_attendance_schema')) {
            TFSP_Database_Activator::maybe_migrate_attendance_schema();
        }
        
        // Enqueue styles and scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Register shortcodes
        add_shortcode('student_registration', array($this, 'student_registration_shortcode'));
        add_shortcode('student_login', array($this, 'student_login_shortcode'));
        add_shortcode('tfsp_student_dashboard', array($this, 'student_dashboard_shortcode'));
        add_shortcode('student_dashboard', array($this, 'student_dashboard_shortcode'));
        add_shortcode('tfsp_admin_dashboard', array($this, 'admin_dashboard_shortcode'));
        add_shortcode('admin_dashboard', array($this, 'admin_dashboard_shortcode'));
        add_shortcode('college_applications', array($this, 'college_tracker_shortcode'));

        // Do not add WordPress admin menus (external-only admin)
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Hide admin for subscribers
        add_action('admin_init', array($this, 'redirect_subscribers_from_admin'));
        add_action('wp_before_admin_bar_render', array($this, 'hide_admin_bar_for_subscribers'));
    }

    // Pretty URLs for external admin portal
    public function register_portal_routes() {
        add_rewrite_rule('^portal-admin/?$', 'index.php?tfsp_portal=dashboard', 'top');
        add_rewrite_rule('^portal-admin/login/?$', 'index.php?tfsp_portal=login', 'top');
    }

    public function register_portal_query_vars($vars) {
        $vars[] = 'tfsp_portal';
        return $vars;
    }

    public function handle_portal_routes() {
        $portal = get_query_var('tfsp_portal');
        if (!$portal) return;
        // Serve our standalone templates
        if ($portal === 'dashboard') {
            include TFSP_PLUGIN_PATH . 'external-admin-dashboard.php';
            exit;
        }
        if ($portal === 'login') {
            include TFSP_PLUGIN_PATH . 'external-admin-login.php';
            exit;
        }
    }
    
    private function load_ajax_handlers() {
        // Ensure AJAX handlers are registered in this same request
        // (avoid deferring to another init pass)
        $handler_path = TFSP_PLUGIN_PATH . 'includes/class-ajax-handler-consolidated.php';
        if (file_exists($handler_path)) {
            require_once $handler_path;
            if (class_exists('TFSP_Ajax_Handler')) {
                // Instantiate registers all wp_ajax hooks
                new TFSP_Ajax_Handler();

        // Include complete AJAX handler for student-admin functionality
        $complete_handler_path = TFSP_PLUGIN_PATH . 'includes/class-ajax-handler-complete.php';
        if (file_exists($complete_handler_path)) {
            require_once $complete_handler_path;
        }
            }
        }

        // Back-compat: also load loader (no-op if already registered)
        $loader_path = TFSP_PLUGIN_PATH . 'includes/class-ajax-loader.php';
        if (file_exists($loader_path)) {
            require_once $loader_path;
        }

        // Direct registration for inline handlers in this class
        add_action('wp_ajax_tfsp_update_checklist_status', array($this, 'handle_update_checklist_status'));
        add_action('wp_ajax_tfsp_update_additional_field', array($this, 'handle_update_additional_field'));
    }

    public function redirect_subscribers_from_admin() {
        if (current_user_can('subscriber') && is_admin() && !wp_doing_ajax()) {
            wp_redirect(home_url('/student-dashboard/'));
            exit;
        }
    }

    public function hide_admin_bar_for_subscribers() {
        if (current_user_can('subscriber')) {
            show_admin_bar(false);
        }
    }

    public function enqueue_assets() {
        // Enqueue Turner Foundation branding styles first
        wp_enqueue_style('tfsp-turner-branding', TFSP_PLUGIN_URL . 'assets/css/turner-branding.css', array(), TFSP_VERSION . '.' . time());
        
        // Enqueue styles for dashboard pages
        if (is_page(array('student-dashboard', 'admin-dashboard'))) {
            wp_enqueue_style('tfsp-ca-dashboard', TFSP_PLUGIN_URL . 'assets/css/ca-dashboard.css', array('tfsp-turner-branding'), TFSP_VERSION . '.' . time());
        }
        
        // Enqueue general frontend styles
        wp_enqueue_style('tfsp-style', TFSP_PLUGIN_URL . 'assets/css/style.css', array('tfsp-turner-branding'), TFSP_VERSION);
        
        // Enqueue admin styles
        if (is_admin()) {
            wp_enqueue_style('tfsp-admin', TFSP_PLUGIN_URL . 'assets/css/admin.css', array('tfsp-turner-branding'), TFSP_VERSION);
        }
        if (is_page('student-dashboard')) {
            wp_enqueue_script('tfsp-student-js', TFSP_PLUGIN_URL . 'assets/js/student-dashboard.js', array(), TFSP_VERSION, true);
            wp_localize_script('tfsp-student-js', 'tfspCfg', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('tfsp_nonce'),
                'checklist_nonce' => wp_create_nonce('tfsp_checklist_nonce')
            ));
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            'Student Portal',
            'Student Portal',
            'manage_options',
            'tfsp-dashboard',
            array($this, 'admin_dashboard_page'),
            'dashicons-graduation-cap',
            30
        );

        add_submenu_page('tfsp-dashboard', 'Students', 'Students', 'manage_options', 'tfsp-students', array($this, 'admin_students_page'));
        add_submenu_page(null, 'Student Detail', 'Student Detail', 'manage_options', 'tfsp-student-detail', array($this, 'admin_student_detail_page'));
        add_submenu_page('tfsp-dashboard', 'Attendance', 'Attendance', 'manage_options', 'tfsp-attendance', array($this, 'admin_attendance_page'));
        // Coach Sessions tracking view
        add_submenu_page('tfsp-dashboard', 'Coach Sessions', 'Coach Sessions', 'manage_options', 'tfsp-coach-sessions', function() {
            include TFSP_PLUGIN_PATH . 'admin/views/page-coach-sessions.php';
        });
        add_submenu_page('tfsp-dashboard', 'Challenges', 'Challenges', 'manage_options', 'tfsp-challenges', array($this, 'admin_challenges_page'));
        add_submenu_page('tfsp-dashboard', 'Documents', 'Documents', 'manage_options', 'tfsp-documents', array($this, 'admin_documents_page'));
        add_submenu_page('tfsp-dashboard', 'Messages', 'Messages', 'manage_options', 'tfsp-messages', array($this, 'admin_messages_page'));
        add_submenu_page('tfsp-dashboard', 'Meetings', 'Meetings', 'manage_options', 'tfsp-meetings', array($this, 'admin_meetings_page'));
        add_submenu_page('tfsp-dashboard', 'Settings', 'Settings', 'manage_options', 'tfsp-settings', array($this, 'admin_settings_page'));
    }

    public function admin_student_detail_page() {
        include TFSP_PLUGIN_PATH . 'admin/student-detail.php';
    }

    public function admin_challenges_page() {
        include TFSP_PLUGIN_PATH . 'admin/challenges.php';
    }

    public function admin_attendance_page() {
        include TFSP_PLUGIN_PATH . 'admin/attendance.php';
    }

    public function admin_messages_page() {
        include TFSP_PLUGIN_PATH . 'admin/messages.php';
    }

    public function admin_dashboard_page() {
        // Include enhanced admin dashboard with all requirements
        require_once(TFSP_PLUGIN_PATH . 'admin/enhanced-admin-dashboard.php');
        $enhanced_dashboard = new TFSP_Enhanced_Admin_Dashboard();
        $enhanced_dashboard->render_dashboard();
    }

    public function enqueue_admin_assets() {
        // Admin CSS/JS sanitization to clean bad glyphs
        wp_enqueue_style('tfsp-admin', TFSP_PLUGIN_URL . 'assets/css/admin.css', array(), TFSP_VERSION);
        wp_enqueue_script('tfsp-admin-js', TFSP_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), TFSP_VERSION, true);
    }

    public function admin_students_page() {
        if (file_exists(TFSP_PLUGIN_PATH . 'admin/students.php')) {
            include TFSP_PLUGIN_PATH . 'admin/students.php';
        } else {
            global $wpdb;
            $students = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tfsp_students ORDER BY created_at DESC");
            
            echo '<div class="wrap">
                <h1>Students Management 
                    <button id="add-student-btn" class="page-title-action">Add New Student</button>
                </h1>
                
                <!-- Add Student Modal -->
                <div id="add-student-modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
                    <div style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 500px; border-radius: 8px;">
                        <span id="close-modal" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
                        <h2>Add New Student</h2>
                        <form id="add-student-form">
                            <table class="form-table">
                                <tr>
                                    <th><label>First Name</label></th>
                                    <td><input type="text" name="first_name" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th><label>Last Name</label></th>
                                    <td><input type="text" name="last_name" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th><label>Email</label></th>
                                    <td><input type="email" name="email" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th><label>Password</label></th>
                                    <td><input type="password" name="password" class="regular-text" required minlength="6"></td>
                                </tr>
                            </table>
                            <p class="submit">
                                <button type="submit" class="button-primary">Add Student</button>
                                <button type="button" id="cancel-add" class="button">Cancel</button>
                            </p>
                        </form>
                        <div id="add-student-message" style="margin-top: 15px; padding: 10px; border-radius: 4px; display: none;"></div>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($students as $student) {
                echo '<tr>
                    <td>' . esc_html($student->student_id) . '</td>
                    <td>' . esc_html($student->first_name . ' ' . $student->last_name) . '</td>
                    <td>' . esc_html($student->email) . '</td>
                    <td>' . esc_html($student->status) . '</td>
                    <td>' . date('M j, Y', strtotime($student->created_at)) . '</td>
                </tr>';
            }
            
            echo '</tbody></table>
            
            <script>
            document.addEventListener("DOMContentLoaded", function() {
                const modal = document.getElementById("add-student-modal");
                const btn = document.getElementById("add-student-btn");
                const span = document.getElementById("close-modal");
                const cancel = document.getElementById("cancel-add");
                const form = document.getElementById("add-student-form");
                const message = document.getElementById("add-student-message");
                
                btn.onclick = function() { modal.style.display = "block"; }
                span.onclick = function() { modal.style.display = "none"; }
                cancel.onclick = function() { modal.style.display = "none"; }
                
                form.addEventListener("submit", function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    formData.append("action", "tfsp_add_student");
                    formData.append("nonce", "' . wp_create_nonce('tfsp_add_student_nonce') . '");
                    
                    fetch(ajaxurl, {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        message.style.display = "block";
                        if (data.success) {
                            message.style.background = "#d4edda";
                            message.style.color = "#155724";
                            message.style.border = "1px solid #c3e6cb";
                            message.textContent = data.data;
                            form.reset();
                            setTimeout(() => {
                                modal.style.display = "none";
                                location.reload();
                            }, 1500);
                        } else {
                            message.style.background = "#f8d7da";
                            message.style.color = "#721c24";
                            message.style.border = "1px solid #f5c6cb";
                            message.textContent = data.data;
                        }
                    });
                });
            });
            </script>
            
            </div>';
        }
    }

    public function admin_documents_page() {
        if (file_exists(TFSP_PLUGIN_PATH . 'admin/documents.php')) {
            include TFSP_PLUGIN_PATH . 'admin/documents.php';
        } else {
            global $wpdb;
            
            // Get counts by status
            $total_docs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_documents");
            $pending_docs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_documents WHERE status = 'pending'");
            $approved_docs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_documents WHERE status = 'approved'");
            $rejected_docs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_documents WHERE status = 'sent_back'");
            
            $documents = $wpdb->get_results("
                SELECT d.*, u.display_name 
                FROM {$wpdb->prefix}tfsp_documents d 
                LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID 
                ORDER BY d.upload_date DESC
            ");
            
            echo '<div class="wrap">
                <h1>Documents Management</h1>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0;">
                    <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h4>Total Documents</h4>
                        <p style="font-size: 20px; font-weight: bold; color: #0073aa;">' . $total_docs . '</p>
                    </div>
                    <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h4>Pending</h4>
                        <p style="font-size: 20px; font-weight: bold; color: #f56e28;">' . $pending_docs . '</p>
                    </div>
                    <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h4>Approved</h4>
                        <p style="font-size: 20px; font-weight: bold; color: #00a32a;">' . $approved_docs . '</p>
                    </div>
                    <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h4>Sent Back</h4>
                        <p style="font-size: 20px; font-weight: bold; color: #d63638;">' . $rejected_docs . '</p>
                    </div>
                </div>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Document Type</th>
                            <th>File Name</th>
                            <th>Upload Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            if (empty($documents)) {
                echo '<tr><td colspan="6" style="text-align: center; padding: 20px;">No documents uploaded yet.</td></tr>';
            } else {
                foreach ($documents as $doc) {
                    echo '<tr>
                        <td>' . esc_html($doc->display_name) . '</td>
                        <td>' . esc_html(ucwords(str_replace('_', ' ', $doc->document_type))) . '</td>
                        <td>' . esc_html($doc->file_name) . '</td>
                        <td>' . date('M j, Y', strtotime($doc->upload_date)) . '</td>
                        <td>' . esc_html($doc->status) . '</td>
                        <td><a href="' . wp_upload_dir()['baseurl'] . '/tfsp-documents/' . $doc->file_path . '" target="_blank">Download</a></td>
                    </tr>';
                }
            }
            
            echo '</tbody></table></div>';
        }
    }

    public function admin_meetings_page() {
        if (file_exists(TFSP_PLUGIN_PATH . 'admin/meetings.php')) {
            include TFSP_PLUGIN_PATH . 'admin/meetings.php';
        } else {
            global $wpdb;
            $meetings = $wpdb->get_results("
                SELECT m.*, u.display_name 
                FROM {$wpdb->prefix}tfsp_meetings m 
                LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID 
                ORDER BY m.meeting_date DESC
            ");
            
            echo '<div class="wrap">
                <h1>Meetings Management</h1>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Requested</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            if (empty($meetings)) {
                echo '<tr><td colspan="6" style="text-align: center; padding: 20px;">No meetings scheduled yet.</td></tr>';
            } else {
                foreach ($meetings as $meeting) {
                    echo '<tr>
                        <td>' . esc_html($meeting->display_name) . '</td>
                        <td>' . date('M j, Y', strtotime($meeting->meeting_date)) . '</td>
                        <td>' . date('g:i A', strtotime($meeting->meeting_time)) . '</td>
                        <td>' . esc_html(ucwords(str_replace('_', ' ', $meeting->meeting_type))) . '</td>
                        <td>' . esc_html($meeting->status) . '</td>
                        <td>' . date('M j, Y', strtotime($meeting->created_at)) . '</td>
                    </tr>';
                }
            }
            
            echo '</tbody></table></div>';
        }
    }

    public function admin_applications_page() {
        if (file_exists(TFSP_PLUGIN_PATH . 'admin/applications.php')) {
            include TFSP_PLUGIN_PATH . 'admin/applications.php';
        } else {
            global $wpdb;
            $applications = $wpdb->get_results("
                SELECT a.*, u.display_name 
                FROM {$wpdb->prefix}tfsp_applications a 
                LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
                ORDER BY a.deadline ASC
            ");
            
            echo '<div class="wrap">
                <h1>Applications Management</h1>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>College</th>
                            <th>Application Type</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            if (empty($applications)) {
                echo '<tr><td colspan="6" style="text-align: center; padding: 20px;">No applications added yet.</td></tr>';
            } else {
                foreach ($applications as $app) {
                    echo '<tr>
                        <td>' . esc_html($app->display_name) . '</td>
                        <td>' . esc_html($app->college_name) . '</td>
                        <td>' . esc_html(ucwords(str_replace('_', ' ', $app->application_type))) . '</td>
                        <td>' . date('M j, Y', strtotime($app->deadline)) . '</td>
                        <td>' . esc_html(ucwords(str_replace('_', ' ', $app->status))) . '</td>
                        <td>' . date('M j, Y', strtotime($app->created_at)) . '</td>
                    </tr>';
                }
            }
            
            echo '</tbody></table></div>';
        }
    }

    public function admin_settings_page() {
        include TFSP_PLUGIN_PATH . 'admin/settings.php';
    }

    public function student_registration_shortcode($atts) {
        ob_start();
        include TFSP_PLUGIN_PATH . 'templates/student-registration-form.php';
        return ob_get_clean();
    }

    public function student_login_shortcode($atts) {
        if (is_user_logged_in()) {
            return '<div style="max-width: 500px; margin: 40px auto; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; font-family: Roboto, -apple-system, BlinkMacSystemFont, \'Segoe UI\', Helvetica, Arial, sans-serif;">
                <h3 style="color: #3f5340; margin: 0 0 16px 0;">Welcome Back!</h3>
                <p style="margin: 0 0 20px 0; color: #666;">You are already logged in to the student portal.</p>
                <a href="' . home_url('/student-dashboard/') . '" style="display: inline-block; background: #8ebb79; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: 500;">Go to Dashboard</a>
            </div>';
        }

        ob_start();
        ?>
        <style>
        .tfsp-login-container {
            max-width: 450px;
            margin: 40px auto;
            padding: 0;
            font-family: Roboto, -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
        }
        .tfsp-login-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .tfsp-login-header {
            background: #3f5340;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .tfsp-login-header h2 {
            margin: 0 0 8px 0;
            font-size: 24px;
            font-weight: 600;
            color: white;
        }
        .tfsp-login-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .tfsp-login-form {
            padding: 30px;
        }
        .tfsp-form-group {
            margin-bottom: 20px;
        }
        .tfsp-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #333333;
            font-size: 14px;
        }
        .tfsp-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            font-family: inherit;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        .tfsp-input:focus {
            outline: none;
            border-color: #8ebb79;
            box-shadow: 0 0 0 2px rgba(142, 187, 121, 0.1);
        }
        .tfsp-submit {
            width: 100%;
            background: #8ebb79;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .tfsp-submit:hover {
            background: #3a543f;
        }
        .tfsp-message {
            margin-top: 16px;
            padding: 12px;
            border-radius: 4px;
            display: none;
            font-size: 14px;
        }
        .tfsp-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .tfsp-register-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .tfsp-register-link a {
            color: #8ebb79;
            text-decoration: none;
            font-weight: 500;
        }
        </style>

        <div class="tfsp-login-container">
            <div class="tfsp-login-card">
                <div class="tfsp-login-header">
                    <h2 style="color: white !important;">Turner Foundation YCAM Portal</h2>
                    <p style="color: white !important;">Student Login - Access your college application portal</p>
                </div>
                
                <div class="tfsp-login-form">
                    <form method="post" action="<?php echo wp_login_url(); ?>">
                        <div class="tfsp-form-group">
                            <label class="tfsp-label">Email Address</label>
                            <input type="email" name="log" class="tfsp-input" required>
                        </div>

                        <div class="tfsp-form-group">
                            <label class="tfsp-label">Password</label>
                            <input type="password" name="pwd" class="tfsp-input" required>
                        </div>

                        <input type="hidden" name="redirect_to" value="<?php echo home_url('/student-dashboard/'); ?>">
                        
                        <button type="submit" class="tfsp-submit">Login</button>
                    </form>
                    
                    <div class="tfsp-register-link">
                        Don't have an account? <a href="<?php echo home_url('/student-registration/'); ?>">Register here</a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function student_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            echo '<script>window.location.href = "' . home_url('/student-login/') . '";</script>';
            return;
        }

        // Enqueue the CSS for student dashboard
        wp_enqueue_style('tfsp-ca-dashboard', TFSP_PLUGIN_URL . 'assets/css/ca-dashboard.css', array(), TFSP_VERSION);
        
        if (file_exists(TFSP_PLUGIN_PATH . 'templates/student-dashboard-exact.php')) {
            ob_start();
            include TFSP_PLUGIN_PATH . 'templates/student-dashboard-exact.php';
            return ob_get_clean();
        } else {
            return '<div style="padding: 20px; background: #d4edda; border-radius: 8px; text-align: center;"><h2>Student Dashboard</h2><p>Welcome to your student portal!</p></div>';
        }
    }

    public function admin_dashboard_shortcode($atts) {
        // Deprecated: redirect to external admin portal
        $url = home_url('/portal-admin/');
        return '<div style="padding:16px; background:#e5f3ff; border-radius:8px; text-align:center;">
            <h3 style="margin:0 0 8px;color:#1f2937;">Admin portal moved</h3>
            <p style="margin:0 0 12px;color:#374151;">Use the external admin portal.</p>
            <a href="'.esc_url($url).'" style="display:inline-block;background:#2563eb;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;">Open Admin Portal</a>
        </div>';
    }

    public function college_tracker_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div style="padding: 20px; background: #fff3cd; border-radius: 8px; text-align: center;"><p>Please <a href="' . wp_login_url() . '">login</a> to access the college tracker.</p></div>';
        }

        // Enqueue the CSS for college applications
        wp_enqueue_style('tfsp-ca-dashboard', TFSP_PLUGIN_URL . 'assets/css/ca-dashboard.css', array(), TFSP_VERSION);
        
        if (file_exists(TFSP_PLUGIN_PATH . 'templates/college-applications.php')) {
            ob_start();
            include TFSP_PLUGIN_PATH . 'templates/college-applications.php';
            return ob_get_clean();
        } else {
            return '<div style="padding: 20px; background: #d1ecf1; border-radius: 8px; text-align: center;"><h2>College Tracker</h2><p>Track your college applications here!</p></div>';
        }
    }







    public function activate() {
        require_once TFSP_PLUGIN_PATH . 'includes/class-database-activator.php';
        TFSP_Database_Activator::activate();
        $this->create_plugin_pages();
        flush_rewrite_rules();
    }

    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();

        // Students table
        $students_table = $wpdb->prefix . 'tfsp_students';
        $students_sql = "CREATE TABLE $students_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            student_id varchar(20) NOT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Applications table
        $applications_table = $wpdb->prefix . 'tfsp_applications';
        $applications_sql = "CREATE TABLE $applications_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            college_name varchar(200) NOT NULL,
            application_type varchar(50) NOT NULL,
            deadline date,
            status varchar(20) DEFAULT 'not_started',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Documents table - FIX COLUMN NAME
        $documents_table = $wpdb->prefix . 'tfsp_documents';
        $documents_sql = "CREATE TABLE $documents_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            document_type varchar(50) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_path varchar(255) NOT NULL,
            upload_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'pending',
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Meetings table - FIX COLUMN NAME
        $meetings_table = $wpdb->prefix . 'tfsp_meetings';
        $meetings_sql = "CREATE TABLE $meetings_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            meeting_date date NOT NULL,
            meeting_time time NOT NULL,
            meeting_type varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($students_sql);
        dbDelta($applications_sql);
        dbDelta($documents_sql);
        dbDelta($meetings_sql);

        // Challenges table
        $challenges_table = $wpdb->prefix . 'tfsp_challenges';
        $challenges_sql = "CREATE TABLE $challenges_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            type varchar(20) DEFAULT 'standard',
            linked_steps text NULL,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($challenges_sql);

        // Student Progress table
        $progress_table = $wpdb->prefix . 'tfsp_student_progress';
        $progress_sql = "CREATE TABLE $progress_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            step varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($progress_sql);

        // Messages table
        $messages_table = $wpdb->prefix . 'tfsp_messages';
        $messages_sql = "CREATE TABLE $messages_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            message_type varchar(20) DEFAULT 'admin',
            subject varchar(255),
            message text,
            status varchar(20) DEFAULT 'unread',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($messages_sql);

        // Attendance table
        $attendance_table = $wpdb->prefix . 'tfsp_attendance';
        $attendance_sql = "CREATE TABLE $attendance_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            date date NOT NULL,
            status varchar(20) DEFAULT 'present',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($attendance_sql);
    }

    private function create_plugin_pages() {
        $pages = array(
            'student-registration' => array(
                'title' => 'Student Registration',
                'content' => '[student_registration]',
                'slug' => 'student-registration'
            ),
            'student-login' => array(
                'title' => 'Turner Foundation YCAM Portal',
                'content' => '[student_login]',
                'slug' => 'student-login'
            ),
            'student-dashboard' => array(
                'title' => 'Student Dashboard',
                'content' => '[tfsp_student_dashboard]',
                'slug' => 'student-dashboard'
            )
        );

        foreach ($pages as $page_data) {
            // Check if page already exists
            $existing_page = get_page_by_path($page_data['slug']);

            if (!$existing_page) {
                $page_id = wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_name' => $page_data['slug'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => 1
                ));

                // Set Elementor Canvas template
                if ($page_id && !is_wp_error($page_id)) {
                    update_post_meta($page_id, '_wp_page_template', 'elementor_canvas');
                }
            }
        }
    }

    public function handle_add_application() {
        error_log('Add application handler called');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Please login to add applications.');
        }

        $user_id = get_current_user_id();
        $college_name = sanitize_text_field($_POST['college_name']);
        $deadline = sanitize_text_field($_POST['deadline']);
        $application_type = sanitize_text_field($_POST['application_type']);
        $status = sanitize_text_field($_POST['status']);
        
        error_log("Application data - User: $user_id, College: $college_name, Deadline: $deadline, Type: $application_type, Status: $status");
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'tfsp_applications';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            $this->create_database_tables();
        }
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'college_name' => $college_name,
                'deadline' => $deadline,
                'application_type' => $application_type,
                'status' => $status,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        error_log('Application insert result: ' . ($result ? 'Success' : 'Failed'));
        error_log('Application last error: ' . $wpdb->last_error);
        
        if ($result) {
            wp_send_json_success('Application added successfully!');
        } else {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        }
    }

    public function handle_update_application_status() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Please login to update applications.');
        }

        $application_id = intval($_POST['application_id']);
        $status = sanitize_text_field($_POST['status']);
        
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'tfsp_applications',
            array('status' => $status),
            array('id' => $application_id, 'user_id' => get_current_user_id()),
            array('%s'),
            array('%d', '%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('Status updated successfully!');
        } else {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        }
    }

    public function handle_add_student() {
        // Check admin permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions.');
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tfsp_add_student_nonce')) {
            wp_send_json_error('Security check failed.');
        }

        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];

        // Validate email
        if (!is_email($email)) {
            wp_send_json_error('Please enter a valid email address.');
        }

        // Check if user already exists
        if (email_exists($email)) {
            wp_send_json_error('An account with this email already exists.');
        }

        // Create WordPress user
        $user_id = wp_create_user($email, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error('User creation failed: ' . $user_id->get_error_message());
        }

        // Update user meta
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name
        ));

        // Set user role to subscriber (student)
        $user = new WP_User($user_id);
        $user->set_role('subscriber');

        // Create student record in database
        global $wpdb;
        $student_id = 'STU' . str_pad($user_id, 6, '0', STR_PAD_LEFT);

        $result = $wpdb->insert(
            $wpdb->prefix . 'tfsp_students',
            array(
                'user_id' => $user_id,
                'student_id' => $student_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'status' => 'active',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result) {
            wp_send_json_success('Student added successfully!');
        } else {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        }
    }

    public function handle_update_progress() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Please login to update progress.');
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tfsp_student_progress_nonce')) {
            wp_send_json_error('Security check failed.');
        }

        $user_id = get_current_user_id();
        $step = sanitize_text_field($_POST['step']);
        $status = sanitize_text_field($_POST['status']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'tfsp_student_progress';
        
        // Check if table exists, create if not
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            $this->create_progress_table();
        }
        
        // Check if progress record exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE student_id = %d AND step_key = %s",
            $user_id, $step
        ));
        
        if ($existing) {
            // Update existing record
            $result = $wpdb->update(
                $table_name,
                array(
                    'status' => $status,
                    'updated_at' => current_time('mysql')
                ),
                array(
                    'user_id' => $user_id,
                    'step_key' => $step
                ),
                array('%s', '%s'),
                array('%d', '%s')
            );
        } else {
            // Insert new record
            $result = $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'step_key' => $step,
                    'status' => $status,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s', '%s')
            );
        }
        
        if ($result !== false) {
            wp_send_json_success('Progress updated successfully!');
        } else {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        }
    }

    private function create_progress_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tfsp_student_progress';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            step_key varchar(50) NOT NULL,
            status varchar(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_step (student_id = %d AND step_key)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function handle_update_document_status() {
        // Check admin permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions.');
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tfsp_admin_nonce')) {
            wp_send_json_error('Security check failed.');
        }

        $document_id = intval($_POST['document_id']);
        $status = sanitize_text_field($_POST['status']);
        
        // Validate status
        if (!in_array($status, array('submitted', 'accepted', 'sent_back'))) {
            wp_send_json_error('Invalid status.');
        }
        
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'tfsp_documents',
            array('status' => $status),
            array('id' => $document_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('Document status updated successfully!');
        } else {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        }
    }

    public function handle_send_message() {
        if (!is_user_logged_in()) {
            wp_send_json_error("Please login to send messages.");
        }
        if (!wp_verify_nonce($_POST["nonce"], "tfsp_message_nonce")) {
            wp_send_json_error("Security check failed.");
        }
        $subject = sanitize_text_field($_POST["subject"]);
        $message = sanitize_textarea_field($_POST["message"]);
        $message_type = sanitize_text_field($_POST["message_type"] ?? "admin");
        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . "tfsp_messages",
            array(
                "sender_id" => get_current_user_id(),
                "message_type" => $message_type,
                "subject" => $subject,
                "message" => $message,
                "status" => "unread",
                "created_at" => current_time("mysql")
            ),
            array("%d", "%s", "%s", "%s", "%s", "%s")
        );
        if ($result) {
            wp_send_json_success("Message sent successfully!");
        } else {
            wp_send_json_error("Database error: " . $wpdb->last_error);
        }
    }

    public function handle_create_challenge() {
        if (!current_user_can("manage_options")) {
            wp_send_json_error("Insufficient permissions.");
        }
        if (!wp_verify_nonce($_POST["nonce"], "tfsp_admin_nonce")) {
            wp_send_json_error("Security check failed.");
        }
        $title = sanitize_text_field($_POST["title"]);
        $description = sanitize_textarea_field($_POST["description"] ?? "");
        $difficulty = sanitize_text_field($_POST["difficulty"] ?? "medium");
        $roadmap_step = sanitize_text_field($_POST["roadmap_step"] ?? "");
        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . "tfsp_challenges",
            array(
                "title" => $title,
                "description" => $description,
                "difficulty" => $difficulty,
                "roadmap_step" => $roadmap_step,
                "is_active" => 1,
                "created_at" => current_time("mysql")
            ),
            array("%s", "%s", "%s", "%s", "%d", "%s")
        );
        if ($result) {
            wp_send_json_success("Challenge created successfully!");
        } else {
            wp_send_json_error("Database error: " . $wpdb->last_error);
        }
    }

    public function handle_recreate_tables() {
        // Check admin permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions.');
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tfsp_admin_nonce')) {
            wp_send_json_error('Security check failed.');
        }

        // Recreate database tables
        $this->create_database_tables();
        $this->create_progress_table();

        wp_send_json_success('Database tables recreated successfully!');
    }

    public function handle_update_checklist_status() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Please login to update checklist.');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'tfsp_checklist_nonce')) {
            wp_send_json_error('Security check failed.');
        }

        $user_id = get_current_user_id();
        $item_key = sanitize_text_field($_POST['item_key']);
        $status = sanitize_text_field($_POST['status']);
        
        if (!in_array($status, array('Yes', 'No'))) {
            wp_send_json_error('Invalid status.');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'tfsp_checklist_progress';
        
        // Check if record exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND item_key = %s",
            $user_id, $item_key
        ));
        
        if ($existing) {
            $result = $wpdb->update(
                $table_name,
                array('status' => $status),
                array('user_id' => $user_id, 'item_key' => $item_key),
                array('%s'),
                array('%d', '%s')
            );
        } else {
            $result = $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'item_key' => $item_key,
                    'status' => $status
                ),
                array('%d', '%s', '%s')
            );
        }
        
        if ($result !== false) {
            wp_send_json_success('Status updated successfully!');
        } else {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        }
    }

    public function handle_update_additional_field() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Please login to update fields.');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'tfsp_checklist_nonce')) {
            wp_send_json_error('Security check failed.');
        }

        $user_id = get_current_user_id();
        $item_key = sanitize_text_field($_POST['item_key']);
        $field_key = sanitize_text_field($_POST['field_key']);
        $field_value = sanitize_text_field($_POST['field_value']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'tfsp_checklist_progress';
        
        // Get existing record
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND item_key = %s",
            $user_id, $item_key
        ));
        
        $additional_data = array();
        if ($existing && $existing->additional_data) {
            $additional_data = json_decode($existing->additional_data, true);
        }
        
        $additional_data[$field_key] = $field_value;
        $json_data = json_encode($additional_data);
        
        if ($existing) {
            $result = $wpdb->update(
                $table_name,
                array('additional_data' => $json_data),
                array('user_id' => $user_id, 'item_key' => $item_key),
                array('%s'),
                array('%d', '%s')
            );
        } else {
            $result = $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'item_key' => $item_key,
                    'status' => 'No',
                    'additional_data' => $json_data
                ),
                array('%d', '%s', '%s', '%s')
            );
        }
        
        if ($result !== false) {
            wp_send_json_success('Field updated successfully!');
        } else {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        }
    }

    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize the plugin
new TurnerFoundationStudentPortal();

// AJAX handler for saving roadmap field data
add_action('wp_ajax_save_roadmap_field', 'handle_save_roadmap_field');
function handle_save_roadmap_field() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'save_roadmap_field')) {
        wp_die('Security check failed');
    }
    
    // Get current user
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
    }
    
    // Get form data
    $step = sanitize_text_field($_POST['step']);
    $field = sanitize_text_field($_POST['field']);
    $value = sanitize_text_field($_POST['value']);
    
    // Save to database
    global $wpdb;
    $table = $wpdb->prefix . 'tfsp_student_progress';
    
    // Add field_data column if it doesn't exist
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'field_data'");
    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE $table ADD COLUMN field_data TEXT");
    }
    
    // Check if record exists
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE student_id = %d AND step_key = %s",
        $user_id, $step
    ));
    
    if ($existing) {
        // Update existing record with field data
        $field_data = json_decode($existing->field_data, true) ?: array();
        $field_data[$field] = $value;
        
        $wpdb->update(
            $table,
            array('field_data' => json_encode($field_data)),
            array('student_id' => $user_id, 'step_key' => $step)
        );
    } else {
        // Create new record
        $field_data = array($field => $value);
        
        $wpdb->insert($table, array(
            'student_id' => $user_id,
            'step_key' => $step,
            'status' => 'in_progress',
            'field_data' => json_encode($field_data)
        ));
    }
    
    wp_send_json_success('Field saved');

// AJAX handler for progress updates (working version)
add_action('wp_ajax_tfsp_student_progress_update', 'handle_tfsp_student_progress_update');
function handle_tfsp_student_progress_update() {
    global $wpdb;
    $student_id = get_current_user_id();
    $step_order = intval($_POST['step_order']);
    $status = sanitize_text_field($_POST['status']);
    
    // Map step order to step key
    $step_keys = array(
        1 => 'academic_resume',
        2 => 'personal_essay', 
        3 => 'recommendation',
        4 => 'transcript',
        5 => 'financial_aid',
        6 => 'community_service',
        7 => 'college_list',
        8 => 'college_tours',
        9 => 'fafsa',
        10 => 'college_admissions_tests'
    );
    
    $step_key = $step_keys[$step_order] ?? null;
    if (!$step_key) {
        wp_send_json_error(array('message' => 'Invalid step order'));
    }
    
    $result = $wpdb->update(
        $wpdb->prefix . 'tfsp_student_progress',
        array('status' => $status, 'updated_at' => current_time('mysql')),
        array('student_id' => $student_id, 'step_key' => $step_key),
        array('%s', '%s'),
        array('%d', '%s')
    );
    
    if ($result !== false) {
        wp_send_json_success(array('message' => 'Progress updated successfully', 'updated_rows' => $result));
    } else {
        wp_send_json_error(array('message' => 'Failed to update progress', 'error' => $wpdb->last_error));
    }
}
}

// Load AJAX handlers
require_once TFSP_PLUGIN_PATH . 'includes/ajax-handlers.php';
