<?php
if (!defined('ABSPATH')) {
    exit;
}

class TFSP_Admin_Menu {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_attendance_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    public function add_attendance_menu() {
        add_submenu_page(
            'tfsp-dashboard',
            __('Attendance Grid', 'tfsp'),
            __('Attendance Grid', 'tfsp'),
            'manage_attendance',
            'tfsp-attendance-grid',
            array($this, 'render_attendance_grid')
        );
    }
    
    public function render_attendance_grid() {
        if (!current_user_can('manage_attendance')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'tfsp'));
        }
        
        include TFSP_PLUGIN_PATH . 'admin/views/page-attendance-grid.php';
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'tfsp-attendance-grid') === false) {
            return;
        }
        
        wp_enqueue_style(
            'tfsp-attendance-admin',
            TFSP_PLUGIN_URL . 'admin/css/attendance-admin.css',
            array(),
            TFSP_VERSION
        );
        
        wp_enqueue_script(
            'tfsp-attendance-grid',
            TFSP_PLUGIN_URL . 'admin/js/attendance-grid.js',
            array('jquery', 'wp-api'),
            TFSP_VERSION,
            true
        );
        
        wp_localize_script('tfsp-attendance-grid', 'tfspAttendance', array(
            'nonce' => wp_create_nonce('wp_rest'),
            'apiUrl' => rest_url('tfsp/v1/'),
            'currentWeek' => date('Y-m-d', strtotime('monday this week')),
            'strings' => array(
                'present' => __('Present', 'tfsp'),
                'excused_absence' => __('Excused Absence', 'tfsp'),
                'did_not_attend' => __('Did Not Attend', 'tfsp'),
                'late' => __('Late', 'tfsp'),
                'remote' => __('Remote', 'tfsp'),
                'postponed' => __('Postponed', 'tfsp')
            )
        ));
    }
}
