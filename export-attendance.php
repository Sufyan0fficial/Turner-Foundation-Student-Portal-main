<?php
// Load WordPress
require_once('../../../wp-load.php');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if external admin is logged in
if (!isset($_SESSION['tfsp_admin_id'])) {
    wp_die('Unauthorized access. Please login to the admin portal.');
}

global $wpdb;

$export_type = isset($_GET['export']) ? sanitize_text_field($_GET['export']) : 'day';
$date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=attendance-' . $export_type . '-' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Determine date range
switch ($export_type) {
    case 'day':
        $start_date = $date;
        $end_date = $date;
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'month':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
    case 'year':
        $start_date = date('Y-01-01');
        $end_date = date('Y-12-31');
        break;
    default:
        $start_date = $date;
        $end_date = $date;
}

// Get all students
$students = get_users(array('role' => 'subscriber'));

// Write CSV header
fputcsv($output, array('Student ID', 'Student Name', 'Date', 'Status'));

// Get attendance records
foreach ($students as $student) {
    $records = $wpdb->get_results($wpdb->prepare(
        "SELECT session_date, status 
         FROM {$wpdb->prefix}tfsp_attendance_records 
         WHERE student_id = %d 
         AND session_date BETWEEN %s AND %s
         ORDER BY session_date ASC",
        $student->ID, $start_date, $end_date
    ));
    
    if (!empty($records)) {
        foreach ($records as $record) {
            fputcsv($output, array(
                $student->ID,
                $student->display_name,
                $record->session_date,
                ucfirst($record->status)
            ));
        }
    }
}

fclose($output);
exit;
