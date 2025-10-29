<?php
/**
 * YCAM Mentorship Program - External Admin Dashboard
 */

if (!defined('ABSPATH')) {
    require_once(dirname(__FILE__, 4) . '/wp-load.php');
}

// Check portal admin session
session_start();
if (!isset($_SESSION['tfsp_admin_id']) || !isset($_SESSION['tfsp_admin_username'])) {
    wp_redirect(home_url('/portal-admin/login/'));
    exit;
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    wp_redirect(home_url('/portal-admin/login/'));
    exit;
}

$view = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'dashboard';
$allowed_views = array('dashboard', 'attendance', 'coach-sessions', 'students', 'challenges', 'recommendations', 'advisor', 'documents', 'messages', 'calendly');
if (!in_array($view, $allowed_views)) {
    $view = 'dashboard';
}

global $wpdb;

function get_student_progress_percentage($student_id) {
    global $wpdb;
    $completed = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d AND status = 'completed'", $student_id));
    $total_steps = 10; // Total roadmap steps
    return round(($completed / $total_steps) * 100);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YCAM Mentorship Program Participant Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php // Load WordPress Dashicons for consistent icons ?>
    <link rel="stylesheet" href="<?php echo esc_url( includes_url('css/dashicons.min.css') ); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background: #f8fafc; 
            color: #1f2937;
        }
        
        /* Header */
        .header { 
            background: linear-gradient(135deg, #3f5340 0%, #8ebb79 100%); 
            color: white; 
            padding: 18px 24px; 
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header h1 { 
            font-size: 20px; 
            font-weight: 600;
            margin-left: 35px;
            flex: 1;
        }
        
        .header-user {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-left: auto;
        }
        
        .header-user span {
            color: white;
            font-size: 14px;
        }
        
        .header-user a {
            background: #ef4444;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            white-space: nowrap;
        }
        
        .hamburger {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            font-size: 22px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: none;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        
        .hamburger:hover { background: rgba(255,255,255,0.2); }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 70px;
            width: 260px;
            height: calc(100vh - 70px);
            background: white;
            box-shadow: 2px 0 8px rgba(0,0,0,0.05);
            overflow-y: auto;
            z-index: 999;
        }
        
        .sidebar a {
            display: flex;
            align-items: center;
            padding: 14px 24px;
            color: #4b5563;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.2s;
            font-size: 15px;
            gap: 10px;
        }
        
        .sidebar a:hover { 
            background: #f9fafb; 
            border-left-color: #8ebb79;
            color: #1f2937;
        }
        
        .sidebar a.active { 
            background: #f0fdf4; 
            border-left-color: #8ebb79; 
            color: #3f5340; 
            font-weight: 600; 
        }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            margin-top: 70px;
            padding: 32px;
            min-height: calc(100vh - 70px);
        }
        
        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            transition: all 0.2s;
        }
        
        .card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        
        .card h3 { 
            font-size: 13px; 
            color: #6b7280; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            font-weight: 600;
        }
        
        .card .number { 
            font-size: 36px; 
            font-weight: 700; 
            color: #8ebb79;
            line-height: 1;
        }
        
        .card small {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 4px;
            display: block;
        }
        
        .card.coach-sessions .number { color: #f59e0b; }
        .card.attendance .number { color: #3b82f6; }
        
        /* Dashboard Overview Sections */
        .coach-sessions-summary, .attendance-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .session-stats, .attendance-stats {
            display: flex;
            gap: 30px;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            min-width: 120px;
            transition: all 0.2s;
        }
        
        .stat-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        
        .stat-number {
            display: block;
            font-size: 36px;
            font-weight: 700;
            color: #8ebb79;
            margin-bottom: 8px;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 13px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .view-all-btn {
            background: #8ebb79;
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(139, 195, 74, 0.2);
        }
        
        .view-all-btn:hover {
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            filter: brightness(0.9);
        }
        
        /* Mobile responsiveness for new sections */
        @media (max-width: 768px) {
            .coach-sessions-summary, .attendance-summary {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            
            .session-stats, .attendance-stats {
                gap: 15px;
                width: 100%;
                justify-content: space-between;
            }
            
            .stat-item {
                padding: 15px;
                min-width: 100px;
                flex: 1;
            }
            
            .stat-number {
                font-size: 28px;
            }
            
            .view-all-btn {
                width: 100%;
                text-align: center;
                padding: 12px 20px;
                font-size: 14px;
            }
        }
        
        @media (max-width: 480px) {
            .session-stats, .attendance-stats {
                flex-direction: column;
                gap: 12px;
            }
            
            .stat-item {
                width: 100%;
                min-width: auto;
            }
            
            .stat-number {
                font-size: 24px;
            }
        }
        
        /* Section */
        .section {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .section h2 { 
            margin: 0 0 24px; 
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
        }
        
        /* Student Grid */
        .student-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .student-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .student-card:hover { 
            box-shadow: 0 4px 16px rgba(0,0,0,0.1); 
            transform: translateY(-3px);
            border-color: #8ebb79;
        }
        
        .student-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .student-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .student-details h4 { 
            margin: 0 0 4px; 
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .student-details p { 
            margin: 0; 
            font-size: 13px; 
            color: #6b7280; 
        }
        
        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin: 12px 0;
        }
        
        .progress-fill { 
            height: 100%; 
            background: linear-gradient(90deg, #8ebb79 0%, #7CB342 100%);
            transition: width 0.3s ease;
        }
        
        .progress-text { 
            font-size: 13px; 
            color: #6b7280;
            font-weight: 500;
        }
        
        /* Buttons */
        .btn {
            background: #8ebb79;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        
        .btn:hover {
            background: #7CB342;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(139, 195, 121, 0.3);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }
        
        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: #6b7280;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .hamburger { display: flex; }
            
            .sidebar { 
                left: -260px; 
                transition: left 0.3s ease;
                top: 70px;
            }
            
            .sidebar.open { left: 0; }
            
            .main-content { 
                margin-left: 0; 
                padding: 20px 16px;
            }
            
            .dashboard-cards { 
                grid-template-columns: repeat(2, 1fr); 
                gap: 16px;
            }
            
            .student-grid { 
                grid-template-columns: 1fr; 
            }
            
            .section {
                padding: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .dashboard-cards { 
                grid-template-columns: 1fr; 
            }
            
            .card {
                padding: 20px;
            }
        }
        
        /* Global Styles for All Views */
        .controls {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .controls select,
        .controls input[type="text"],
        .controls input[type="search"] {
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            transition: all 0.2s;
        }
        
        .controls select:focus,
        .controls input:focus {
            outline: none;
            border-color: #8ebb79;
            box-shadow: 0 0 0 3px rgba(142, 187, 121, 0.1);
        }
        
        .controls input[type="text"] {
            flex: 1;
            min-width: 200px;
        }
        
        /* Tables */
        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        .data-table thead {
            background: #f9fafb;
        }
        
        .data-table th {
            padding: 12px 16px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            color: #1f2937;
        }
        
        .data-table tbody tr:hover {
            background: #f9fafb;
        }
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-sent_back {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-present {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-absent {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-excused {
            background: #dbeafe;
            color: #1e40af;
        }
        
        /* Action Buttons */
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            margin-right: 6px;
        }
        
        .action-btn-primary {
            background: #8ebb79;
            color: white;
        }
        
        .action-btn-primary:hover {
            background: #7CB342;
        }
        
        .action-btn-secondary {
            background: #e5e7eb;
            color: #374151;
        }
        
        .action-btn-secondary:hover {
            background: #d1d5db;
        }
        
        .action-btn-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .action-btn-danger:hover {
            background: #fecaca;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #8ebb79;
            box-shadow: 0 0 0 3px rgba(142, 187, 121, 0.1);
        }
        
        /* Notices */
        .notice {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .notice-success {
            background: #d1fae5;
            border-left-color: #10b981;
            color: #065f46;
        }
        
        .notice-error {
            background: #fee2e2;
            border-left-color: #ef4444;
            color: #991b1b;
        }
        
        .notice-info {
            background: #dbeafe;
            border-left-color: #3b82f6;
            color: #1e40af;
        }
        
        /* Grid Layouts */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        /* Message Items */
        .message-item {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 12px;
            transition: all 0.2s;
        }
        
        .message-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .message-header strong {
            font-size: 15px;
            color: #1f2937;
        }
        
        .message-date {
            font-size: 12px;
            color: #6b7280;
        }
        
        .message-body {
            color: #4b5563;
            font-size: 14px;
            line-height: 1.5;
        }
        
        /* Challenge Items */
        .challenge-item {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 16px;
        }
        
        .challenge-item h4 {
            margin: 0 0 8px;
            font-size: 17px;
            color: #1f2937;
        }
        
        .challenge-item p {
            margin: 0 0 12px;
            color: #6b7280;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .challenge-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-year {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-difficulty {
            background: #fef3c7;
            color: #92400e;
        }
        
        /* Attendance Grid */
        .attendance-grid {
            overflow-x: auto;
        }
        
        .attendance-table {
            min-width: 800px;
        }
        
        /* Loading State */
        .loading {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
        }
        
        /* Responsive Tables */
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .data-table {
                font-size: 13px;
            }
            
            /* Convert table to cards on mobile */
            .data-table thead {
                display: none;
            }
            
            .data-table tbody,
            .data-table tr {
                display: block;
            }
            
            .data-table tr {
                margin-bottom: 15px;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 15px;
                background: white;
            }
            
            .data-table td {
                display: block;
                text-align: left;
                padding: 8px 0;
                border: none;
            }
            
            .data-table td:before {
                content: attr(data-label);
                font-weight: 600;
                display: block;
                margin-bottom: 4px;
                color: #6b7280;
                font-size: 12px;
            }
            
            .data-table td button {
                margin-right: 8px;
                margin-top: 5px;
            }
            
            .data-table th,
            .data-table td {
                padding: 10px 12px;
            }
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header {
                padding: 12px 15px;
            }
            
            .header h1 {
                font-size: 16px;
                margin-left: 45px;
            }
            
            .header-user span {
                display: none;
            }
            
            .header-user a {
                padding: 6px 12px;
                font-size: 13px;
            }
        }
        
        @media (max-width: 480px) {
            .header h1 {
                font-size: 14px;
                margin-left: 40px;
            }
            
            .header-user {
                gap: 8px;
            }
            
            .header-user a {
                padding: 6px 10px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

<div class="header">
    <button class="hamburger" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
    <h1>YCAM Mentorship Program Participant Portal</h1>
    <div class="header-user">
        <span>👤 <?php echo esc_html($_SESSION['tfsp_admin_name'] ?? $_SESSION['tfsp_admin_username']); ?></span>
        <a href="?action=logout">Logout</a>
    </div>
</div>

<div class="sidebar" id="sidebar">
    <a href="?view=dashboard" class="<?php echo $view === 'dashboard' ? 'active' : ''; ?>">
        <span class="dashicons dashicons-dashboard"></span> Dashboard
    </a>
    <a href="?view=attendance" class="<?php echo $view === 'attendance' ? 'active' : ''; ?>">
        <span class="dashicons dashicons-yes"></span> Attendance
    </a>
    <a href="?view=coach-sessions" class="<?php echo $view === 'coach-sessions' ? 'active' : ''; ?>">
        <span class="dashicons dashicons-groups"></span> Coach Sessions
    </a>
    <a href="?view=students" class="<?php echo $view === 'students' ? 'active' : ''; ?>">
        <span class="dashicons dashicons-admin-users"></span> Students
    </a>
    <a href="?view=challenges" class="<?php echo $view === 'challenges' ? 'active' : ''; ?>">
        <span class="dashicons dashicons-awards"></span> Challenges
    </a>
    <a href="?view=recommendations" class="<?php echo $view === 'recommendations' ? 'active' : ''; ?>">
        <span class="dashicons dashicons-email-alt"></span> Recommendations
    </a>
    <a href="?view=advisor" class="<?php echo $view === 'advisor' ? 'active' : ''; ?>">
        <span class="dashicons dashicons-businesswoman"></span> College and Career Coach settings
    </a>
    <a href="?view=documents" class="<?php echo $view === 'documents' ? 'active' : ''; ?>">
        <span class="dashicons dashicons-media-document"></span> Documents
    </a>
    <a href="?view=calendly" class="<?php echo $view === 'calendly' ? 'active' : ''; ?>">
        <span class="dashicons dashicons-calendar-alt"></span> Calendly Settings
    </a>
    <a href="?view=messages" class="<?php echo $view === 'messages' ? 'active' : ''; ?>">
        <span class="dashicons dashicons-email-alt2"></span> Messages
    </a>
</div>

<div class="main-content">
    <?php
    if ($view === 'dashboard') {
        $students = get_users(array('role' => 'subscriber'));
        $total_students = count($students);
        $total_docs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_documents");
        $pending_docs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_documents WHERE status = 'pending'");
        $unread_messages = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_messages WHERE status = 'unread'");
        
        // Coach Sessions Stats
        $total_coach_sessions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_coach_sessions");
        $attended_sessions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_coach_sessions WHERE status = 'attended'");
        $scheduled_sessions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_coach_sessions WHERE status = 'scheduled'");
        
        // Attendance Stats (current week)
        $current_week = date('Y-m-d', strtotime('monday this week'));
        $week_end = date('Y-m-d', strtotime('+6 days', strtotime($current_week)));
        
        // Get total students present this week
        $week_attendance = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT ar.student_id) FROM {$wpdb->prefix}tfsp_attendance_records ar
             JOIN {$wpdb->prefix}tfsp_sessions s ON s.id = ar.session_id
             WHERE s.session_date BETWEEN %s AND %s AND ar.status IN ('present', 'excused_absence')",
            $current_week, $week_end
        ));
        
        // If no data this week, get latest week with data
        if ($week_attendance == 0) {
            $latest_week = $wpdb->get_var(
                "SELECT MAX(s.session_date) FROM {$wpdb->prefix}tfsp_attendance_records ar
                 JOIN {$wpdb->prefix}tfsp_sessions s ON s.id = ar.session_id
                 WHERE ar.status IN ('present', 'excused_absence')"
            );
            
            if ($latest_week) {
                $week_attendance = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT ar.student_id) FROM {$wpdb->prefix}tfsp_attendance_records ar
                     JOIN {$wpdb->prefix}tfsp_sessions s ON s.id = ar.session_id
                     WHERE s.session_date = %s AND ar.status IN ('present', 'excused_absence')",
                    $latest_week
                ));
            }
        }
        ?>
        
        <div class="dashboard-cards">
            <div class="card">
                <h3>Total Students</h3>
                <div class="number"><?php echo $total_students; ?></div>
            </div>
            <div class="card">
                <h3>Total Documents</h3>
                <div class="number"><?php echo $total_docs; ?></div>
            </div>
            <div class="card">
                <h3>Pending Documents</h3>
                <div class="number"><?php echo $pending_docs; ?></div>
            </div>
            <div class="card">
                <h3>Unread Messages</h3>
                <div class="number"><?php echo $unread_messages; ?></div>
            </div>
            <div class="card coach-sessions">
                <h3>Coach Sessions</h3>
                <div class="number"><?php echo $attended_sessions; ?>/<?php echo $total_coach_sessions; ?></div>
                <small>Attended/Total</small>
            </div>
            <div class="card attendance">
                <h3>Week Attendance</h3>
                <div class="number"><?php echo $week_attendance ?: '0'; ?></div>
                <small>Students Present</small>
            </div>
        </div>
        
        <!-- Coach Sessions Overview -->
        <div class="section">
            <h2>Coach Sessions Overview</h2>
            <div class="coach-sessions-summary">
                <div class="session-stats">
                    <div class="stat-item">
                        <span class="stat-number" style="color: #f59e0b;"><?php echo $scheduled_sessions; ?></span>
                        <span class="stat-label">Scheduled</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" style="color: #f59e0b;"><?php echo $attended_sessions; ?></span>
                        <span class="stat-label">Attended</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" style="color: #f59e0b;"><?php echo $total_coach_sessions > 0 ? round(($attended_sessions / $total_coach_sessions) * 100) : 0; ?>%</span>
                        <span class="stat-label">Completion Rate</span>
                    </div>
                </div>
                <a href="?view=coach-sessions" class="view-all-btn" style="background: #f59e0b;">View All Sessions <span class="dashicons dashicons-arrow-right-alt2"></span></a>
            </div>
        </div>
        
        <!-- Group Attendance Overview -->
        <div class="section">
            <h2>Group Attendance Overview</h2>
            <div class="attendance-summary">
                <div class="attendance-stats">
                    <div class="stat-item">
                        <span class="stat-number" style="color: #3b82f6;"><?php echo $week_attendance ?: '0'; ?></span>
                        <span class="stat-label">This Week</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" style="color: #3b82f6;"><?php echo $total_students > 0 ? round(($week_attendance / $total_students) * 100) : 0; ?>%</span>
                        <span class="stat-label">Attendance Rate</span>
                    </div>
                </div>
                <a href="?view=attendance" class="view-all-btn" style="background: #3b82f6;">View Full Attendance <span class="dashicons dashicons-arrow-right-alt2"></span></a>
            </div>
        </div>
        
        <div class="section">
            <h2>Students Overview</h2>
            <?php if (empty($students)): ?>
                <div class="empty-state">
                    <h3>No Students Yet</h3>
                    <p>Students will appear here once they register</p>
                </div>
            <?php else: ?>
                <div class="student-grid">
                    <?php foreach ($students as $student): 
                        $progress = get_student_progress_percentage($student->ID);
                    ?>
                    <div class="student-card" onclick="window.location.href='?view=students&student_id=<?php echo $student->ID; ?>'">
                        <div class="student-info">
                            <div class="student-avatar"><span class="dashicons dashicons-admin-users"></span></div>
                            <div class="student-details">
                                <h4><?php echo esc_html($student->display_name); ?></h4>
                                <p><?php echo esc_html($student->user_email); ?></p>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <div class="progress-text">Progress: <?php echo $progress; ?>%</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
    <?php } else {
        // Load view files
        $view_files = array(
            'attendance' => '/admin/views/external-attendance-grid.php',
            'coach-sessions' => '/admin/views/page-coach-sessions.php',
            'students' => isset($_GET['student_id']) ? '/admin/components/student-drill-down.php' : '/admin/views/page-students.php',
            'challenges' => '/admin/views/page-challenges.php',
            'recommendations' => '/admin/views/page-recommendations.php',
            'advisor' => '/admin/views/page-advisor-settings.php',
            'documents' => '/admin/views/page-documents.php',
            'calendly' => '/admin/views/page-calendly-settings.php',
            'messages' => '/admin/views/page-messages.php'
        );
        
        $file = dirname(__FILE__) . ($view_files[$view] ?? '');
        
        if (file_exists($file)) {
            include $file;
            if ($view === 'students' && isset($_GET['student_id']) && function_exists('render_student_drill_down')) {
                render_student_drill_down(intval($_GET['student_id']));
            }
        } else {
            echo '<div class="section">';
            echo '<h2>' . ucfirst($view) . '</h2>';
            echo '<div class="empty-state">';
            echo '<h3>Coming Soon</h3>';
            echo '<p>This section is under development</p>';
            echo '</div>';
            echo '</div>';
        }
    }
    ?>
</div>

<script>
// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
    if (window.innerWidth <= 768) {
        const sidebar = document.getElementById('sidebar');
        const hamburger = document.querySelector('.hamburger');
        if (!sidebar.contains(e.target) && !hamburger.contains(e.target) && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
        }
    }
});
</script>

<script>
(function(){
  try {
    var el = document.querySelector('.header-user span');
    if (el) {
      var name = el.textContent || '';
      // Remove any leading non-printable/broken glyphs
      name = name.replace(/^\s*[^A-Za-z0-9@\(\)\[\]\{\}\._\-\s]+\s*/, '');
      el.innerHTML = '<span class="dashicons dashicons-admin-users"></span> ' + name;
    }
    var hb = document.querySelector('.hamburger');
    if (hb && hb.textContent && hb.textContent.trim().length > 0) {
      hb.innerHTML = '<span class="dashicons dashicons-menu"></span>';
    }
  } catch(e) {}
})();
</script>
</body>
</html>

