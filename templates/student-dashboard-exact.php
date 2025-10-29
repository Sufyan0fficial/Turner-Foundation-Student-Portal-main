<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle coach message form submission (non-AJAX)
if (isset($_POST['send_coach_message']) && wp_verify_nonce($_POST['coach_nonce'], 'coach_message')) {
    $subject = sanitize_text_field($_POST['coach_subject']);
    $message = sanitize_textarea_field($_POST['coach_message']);
    $user = wp_get_current_user();
    
    // Save to database
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'tfsp_messages',
        array(
            'sender_id' => $user->ID,
            'message_type' => 'coach',
            'subject' => '[Coach] ' . $subject,
            'message' => $message,
            'priority' => 'high',
            'status' => 'unread',
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
    );
    
    // Try to send email (optional)
    // Get coach email from advisor settings table
    $advisor_settings = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}tfsp_advisor_settings", OBJECT_K);
    
    // Try both coach_email and recommendation_email fields
    $coach_email = '';
    if (isset($advisor_settings['coach_email']) && !empty($advisor_settings['coach_email']->setting_value)) {
        $coach_email = $advisor_settings['coach_email']->setting_value;
    } elseif (isset($advisor_settings['recommendation_email']) && !empty($advisor_settings['recommendation_email']->setting_value)) {
        $coach_email = $advisor_settings['recommendation_email']->setting_value;
    } else {
        $coach_email = get_option('admin_email');
    }
    
    $email_subject = '[Student Portal - Coach] ' . $subject;
    $email_body = "Message from: {$user->display_name} ({$user->user_email})\n\n{$message}";
    
    // Debug: Log email attempt
    error_log("TFSP: Attempting to send coach email to: " . $coach_email);
    
    $mail_sent = wp_mail($coach_email, $email_subject, $email_body);
    
    // Debug: Log result
    error_log("TFSP: Email sent result: " . ($mail_sent ? 'SUCCESS' : 'FAILED'));
    
    // Use JavaScript redirect to prevent form resubmission
    echo '<script>window.location.href = "' . add_query_arg('msg_sent', '1', remove_query_arg('msg_sent')) . '";</script>';
    exit;
}

// Show success message if redirected
if (isset($_GET['msg_sent']) && $_GET['msg_sent'] == '1') {
    echo '<div style="position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 15px 20px; border-radius: 8px; z-index: 9999;">✓ Message sent to coach successfully!</div>';
    echo '<script>setTimeout(function(){ window.history.replaceState({}, "", "' . remove_query_arg('msg_sent') . '"); }, 3000);</script>';
}

// Get current user
$current_user = wp_get_current_user();
if (!$current_user->ID) {
    wp_redirect(home_url('/student-login/'));
    exit;
}

// Include Thursday attendance component
require_once(TFSP_PLUGIN_PATH . 'templates/components/student-thursday-attendance.php');

// Get student data
global $wpdb;
$student_progress = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d", $current_user->ID));
$student_documents = $wpdb->get_results($wpdb->prepare("
    SELECT * FROM {$wpdb->prefix}tfsp_documents 
    WHERE user_id = %d 
    AND (is_community_service_letter = 0 OR (is_community_service_letter = 1 AND approval_status = 'approved'))
    ORDER BY upload_date DESC
", $current_user->ID));

// Get CS letter status for prominent display
$cs_letter_status = $wpdb->get_row($wpdb->prepare("
    SELECT approval_status, approved_at, file_url 
    FROM {$wpdb->prefix}tfsp_documents 
    WHERE user_id = %d AND is_community_service_letter = 1 
    ORDER BY upload_date DESC LIMIT 1
", $current_user->ID));

// Get attendance data from new tables - current week only
$attendance_percentage = 0;
$attendance_stats = array('present' => 0, 'total' => 0);

if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}tfsp_attendance_records'")) {
    $current_week = date('Y-m-d', strtotime('monday this week'));
    $week_end = date('Y-m-d', strtotime('+6 days', strtotime($current_week)));
    
    $total_sessions = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_sessions WHERE session_date BETWEEN %s AND %s AND is_postponed = 0",
        $current_week, $week_end
    ));
    
    $present_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_attendance_records ar
         JOIN {$wpdb->prefix}tfsp_sessions s ON s.id = ar.session_id
         WHERE ar.student_id = %d AND s.session_date BETWEEN %s AND %s
         AND ar.status IN ('present', 'excused_absence') AND s.is_postponed = 0",
        $current_user->ID, $current_week, $week_end
    ));
    
    $attendance_percentage = $total_sessions > 0 ? round(($present_count / $total_sessions) * 100) : 0;
    $attendance_stats = array('present' => $present_count, 'total' => $total_sessions);
}

// Roadmap steps with exact client colors
$roadmap_steps = array(
    'academic_resume' => array('title' => 'Academic Resume', 'question' => 'What is the goal?', 'completion' => 'Resume completed', 'color' => '#9B59B6'),
    'personal_essay' => array('title' => 'Personal Essay', 'question' => 'How will you present yourself?', 'completion' => 'Essay completed', 'color' => '#E74C3C'),
    'recommendation_letters' => array('title' => 'Recommendation Letters', 'question' => 'How will others present you?', 'completion' => 'Letter 1, Letter 2', 'color' => '#D2527F'),
    'transcript' => array('title' => 'Transcript', 'question' => 'Why is Transcript important?', 'completion' => 'Transcript obtained', 'color' => '#F39C12'),
    'financial_aid' => array('title' => 'Financial Aid', 'question' => 'What is the goal?', 'completion' => 'Applied', 'color' => '#2980B9'),
    'community_service' => array('title' => 'Community Service', 'question' => 'What is the goal?', 'completion' => 'Completed', 'color' => '#27AE60'),
    'college_list' => array('title' => 'Create Interest List of Colleges', 'question' => 'How are you showing progress?', 'completion' => 'List completed? Yes or No', 'color' => '#E67E22'),
    'college_tours' => array('title' => 'College Tours', 'question' => 'How are you showing the goal?', 'completion' => 'College Tours Completed? Yes or No', 'color' => '#34495E'),
    'fafsa' => array('title' => 'FAFSA', 'question' => 'Why is this important?', 'completion' => 'FAFSA completed', 'color' => '#C0392B'),
    'admissions_tests' => array('title' => 'College Admissions Tests', 'question' => 'What is your test strategy?', 'completion' => 'Tests completed', 'color' => '#16A085')
);

// Calculate progress
$completed_steps = array();
foreach ($student_progress as $progress) {
    if ($progress->status === 'completed') {
        $completed_steps[] = $progress->step_key;
    }
}

$progress_percentage = count($roadmap_steps) > 0 ? round((count($completed_steps) / count($roadmap_steps)) * 100) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard - Turner Foundation</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Updated: <?php echo date('Y-m-d H:i:s'); ?> -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; line-height: 1.6; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        .welcome-section { background: linear-gradient(135deg, #2d5016 0%, #3f5340 100%); color: white; padding: 40px 30px; border-radius: 16px; margin-bottom: 30px; position: relative; }
        .welcome-content { display: flex; justify-content: space-between; align-items: center; }
        .welcome-text h1 { font-size: 32px !important; margin-bottom: 10px !important; color: white !important; font-weight: 700 !important; }
        .welcome-text p { font-size: 16px !important; color: white !important; max-width: 600px !important; line-height: 1.6 !important; }
        .progress-circle { width: 120px; height: 120px; border-radius: 50%; background: conic-gradient(#8BC34A <?php echo $progress_percentage * 3.6; ?>deg, rgba(255,255,255,0.2) <?php echo $progress_percentage * 3.6; ?>deg); display: flex; align-items: center; justify-content: center; position: relative; }
        .progress-circle::before { content: ''; width: 90px; height: 90px; border-radius: 50%; background: #2d5016; position: absolute; }
        .progress-text { position: relative; z-index: 1; text-align: center; font-weight: bold; font-size: 24px; color: white; }
        .progress-text small { font-size: 14px; display: block; }
        
        .section { background: white; margin: 30px 0; padding: 30px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .section h2 { margin: 0 0 8px; color: #2d5016; font-size: 28px; font-weight: 700; }
        .section p { margin: 0 0 25px; color: #666; font-size: 16px; }
        
        .challenges-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .challenge-card { background: linear-gradient(135deg, #3498DB 0%, #2980B9 100%) !important; color: white !important; padding: 25px !important; border-radius: 12px !important; box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3) !important; transition: all 0.3s !important; }
        .challenge-card:hover { transform: translateY(-4px) !important; box-shadow: 0 12px 30px rgba(52, 152, 219, 0.4) !important; }
        .challenge-card h4 { margin: 0 0 8px !important; font-size: 20px !important; font-weight: 700 !important; color: white !important; }
        .challenge-card .difficulty { font-size: 12px !important; color: rgba(255,255,255,0.8) !important; margin-bottom: 15px !important; font-weight: 500 !important; text-transform: uppercase !important; letter-spacing: 0.5px !important; }
        .challenge-progress { background: rgba(255,255,255,0.2); height: 8px; border-radius: 4px; overflow: hidden; margin: 15px 0; }
        .challenge-progress-fill { background: #27AE60 !important; height: 100% !important; width: 0% !important; transition: width 0.3s !important; }
        .challenge-percentage { font-size: 14px !important; font-weight: 600 !important; color: white !important; }
        
        .roadmap-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .roadmap-stats { font-size: 18px; font-weight: 600; }
        .completed { color: #4caf50; }
        .remaining { color: #ff9800; }
        
        .roadmap-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 25px; }
        .roadmap-card { 
            border-radius: 16px; 
            padding: 25px; 
            position: relative; 
            transition: all 0.3s; 
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .roadmap-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.25); }
        .card-number { position: absolute; top: -15px; left: 25px; background: rgba(255,255,255,0.9); color: #333; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; transition: all 0.3s ease; }
        .card-number.completed { background: #4CAF50 !important; color: white !important; }
        .card-number.completed::before { content: '✓'; font-size: 20px; font-weight: bold; }
        .card-number.in-progress { background: #FF9800 !important; color: white !important; }
        .card-number.in-progress::before { content: '⏳'; font-size: 16px; }
        .card-content { margin-top: 15px; }
        .card-title { margin: 0 0 8px; color: white; font-size: 22px; font-weight: 700; }
        .card-question { margin: 0 0 12px; color: rgba(255,255,255,0.9); font-size: 14px; font-style: italic; }
        .card-completion { margin: 0 0 20px; color: rgba(255,255,255,0.95); font-size: 16px; font-weight: 500; }
        
        .status-buttons { display: flex; gap: 10px; margin: 15px 0; }
        .status-btn { border: none; padding: 10px 20px; border-radius: 25px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; }
        .status-btn.completed { background: rgba(255,255,255,0.9); color: #4caf50; }
        .status-btn.in-progress { background: rgba(255,255,255,0.9); color: #ff9800; }
        .status-btn.pending { background: rgba(255,255,255,0.7); color: #666; }
        .status-btn:hover { background: rgba(255,255,255,1); transform: translateY(-1px); }
        
        .detail-inputs { margin-top: 15px; }
        .detail-input { width: 100%; padding: 10px 15px; border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; margin: 8px 0; font-size: 14px; background: rgba(255,255,255,0.9); color: #333; position: relative; }
        .detail-input::placeholder { color: #666; }
        
        .save-indicator {
            position: absolute;
            right: -5px;
            top: -8px;
            background: #28a745;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .save-indicator.show { opacity: 1; }
        
        .upload-section { 
            border: 2px dashed #8BC34A; 
            border-radius: 16px; 
            padding: 50px 30px; 
            text-align: center; 
            background: linear-gradient(135deg, #f8fff8 0%, #e8f5e8 100%); 
            margin: 25px 0; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            position: relative;
            overflow: hidden;
        }
        .upload-section:hover { 
            border-color: #7CB342; 
            background: linear-gradient(135deg, #f0fff0 0%, #e0f0e0 100%); 
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 195, 74, 0.2);
        }
        .upload-section.dragover {
            border-color: #4CAF50;
            background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c8 100%);
            transform: scale(1.02);
            box-shadow: 0 12px 35px rgba(76, 175, 80, 0.3);
        }
        .upload-icon { 
            font-size: 64px; 
            margin-bottom: 20px; 
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        .upload-text { 
            font-size: 18px; 
            margin-bottom: 10px; 
            font-weight: 700;
            color: #2d5016;
        }
        .upload-subtext { 
            color: #666; 
            font-size: 14px;
        }
        
        .upload-controls { display: flex; gap: 20px; margin: 25px 0; align-items: center; }
        .upload-controls select { flex: 1; padding: 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; }
        .upload-btn { background: #8BC34A; color: white; border: none; padding: 15px 30px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 16px; }
        .upload-btn:hover { background: #7CB342; }
        .upload-btn:disabled { background: #ccc; cursor: not-allowed; }
        
        .file-selected {
            margin-top: 15px;
        }
        .selected-file-info {
            display: flex;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
            border-radius: 12px;
            border-left: 4px solid #4caf50;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .file-icon {
            font-size: 32px;
            margin-right: 15px;
            color: #4caf50;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .file-details {
            flex: 1;
        }
        .file-name {
            font-weight: 700;
            color: #2d5016;
            margin-bottom: 6px;
            font-size: 16px;
        }
        .file-size {
            font-size: 13px;
            color: #666;
            background: rgba(255,255,255,0.7);
            padding: 2px 8px;
            border-radius: 12px;
            display: inline-block;
        }
        
        .upload-progress-state {
            margin-top: 15px;
        }
        .progress-info {
            display: flex;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-radius: 12px;
            border-left: 4px solid #2196f3;
            animation: slideIn 0.3s ease;
        }
        .progress-icon {
            font-size: 32px;
            margin-right: 15px;
            color: #2196f3;
            animation: spin 2s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .progress-details {
            flex: 1;
        }
        .progress-filename {
            font-weight: 700;
            color: #1565c0;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255,255,255,0.7);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #2196f3 0%, #1976d2 100%);
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(33, 150, 243, 0.3);
        }
        .progress-text {
            font-size: 13px;
            color: white;
            font-weight: 700;
            display: inline-block;
        }
        
        .document-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 25px; }
        .upload-column, .documents-column { background: #f8f9fa; padding: 25px; border-radius: 12px; border: 1px solid #e8f5e8; }
        .documents-column h4 { margin: 0 0 20px 0; color: #2d5016; font-size: 18px; font-weight: 600; }
        .documents-list { display: flex; flex-direction: column; gap: 12px; }
        .document-item { display: flex; align-items: center; padding: 15px; background: white; border-radius: 8px; border: 1px solid #e0e0e0; transition: all 0.3s; }
        .document-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateY(-1px); }
        .doc-icon { font-size: 24px; margin-right: 15px; }
        .doc-info { flex: 1; }
        .doc-name { font-weight: 600; color: #333; margin-bottom: 4px; font-size: 14px; }
        .doc-type { font-size: 12px; color: #666; margin-bottom: 2px; }
        .doc-date { font-size: 11px; color: #999; }
        .status-badge { color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        
        @media (max-width: 768px) {
            .document-layout { grid-template-columns: 1fr; gap: 20px; }
        }
        
        .documents-section h4 { margin: 25px 0 15px; color: #333; font-size: 20px; }
        .empty-documents { text-align: center; padding: 50px 20px; color: #666; background: #f8f9fa; border-radius: 12px; }
        
        .advisor-section { display: grid; grid-template-columns: 1fr; gap: 40px; }
        
        .calendar-section {
            background: #f8f9fa;
            border-radius: 16px;
            margin-bottom: 30px;
            border: 1px solid #e8f5e8;
            overflow: hidden;
        }
        .calendar-header {
            background: #8BC34A;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .calendar-header h3 {
            margin: 0;
            font-size: 18px;
        }
        .close-calendar {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .close-calendar:hover {
            background: rgba(255,255,255,0.2);
        }
        .calendar-container {
            padding: 0;
        }
        .advisor-card { 
            background: linear-gradient(135deg, #3f5340 0%, #2d3e2f 100%); 
            color: white; 
            padding: 50px 60px; 
            border-radius: 24px; 
            text-align: center; 
            box-shadow: 0 20px 60px rgba(63, 83, 64, 0.4);
            position: relative;
            overflow: hidden;
            border: 2px solid #8ebb79;
        }
        .advisor-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(142, 187, 121, 0.15) 0%, transparent 70%);
            pointer-events: none;
            animation: pulse 8s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        .advisor-title { 
            font-size: 32px; 
            margin-bottom: 10px; 
            font-weight: 800; 
            color: white;
            text-shadow: 0 4px 12px rgba(0,0,0,0.3);
            position: relative;
            z-index: 1;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }
        .advisor-subtitle { 
            font-size: 16px; 
            margin-bottom: 35px; 
            color: rgba(255,255,255,0.9); 
            font-weight: 500;
            position: relative;
            z-index: 1;
        }
        .advisor-stats { 
            display: flex; 
            justify-content: center;
            gap: 80px;
            margin: 35px 0; 
            position: relative;
            z-index: 1;
        }
        .stat { 
            text-align: center; 
            padding: 28px 45px;
            background: rgba(142, 187, 121, 0.2);
            border-radius: 20px;
            backdrop-filter: blur(20px);
            border: 2px solid rgba(142, 187, 121, 0.4);
            min-width: 170px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }
        .stat:hover {
            background: rgba(142, 187, 121, 0.3);
            transform: translateY(-8px) scale(1.05);
            box-shadow: 0 16px 48px rgba(142, 187, 121, 0.3);
            border-color: #8ebb79;
        }
        .stat-number { 
            font-size: 44px; 
            font-weight: 900; 
            display: block; 
            color: white !important; 
            margin-bottom: 10px; 
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
            line-height: 1;
        }
        .stat-label { 
            font-size: 13px; 
            color: rgba(255,255,255,0.95); 
            font-weight: 600; 
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }
        .advisor-actions { 
            display: flex; 
            gap: 20px; 
            justify-content: center; 
            margin-top: 35px; 
            position: relative;
            z-index: 1;
        }
        .btn-white { 
            background: #8ebb79; 
            color: white !important; 
            padding: 16px 32px; 
            border-radius: 14px; 
            text-decoration: none; 
            font-weight: 700; 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            font-size: 15px;
            box-shadow: 0 8px 24px rgba(142, 187, 121, 0.4);
            letter-spacing: 0.3px;
        }
        .btn-white:hover { 
            background: white; 
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 16px 40px rgba(142, 187, 121, 0.5);
            color: #3f5340 !important;
            text-decoration: none;
        }
        
        .upcoming-section h4 { margin: 0 0 20px; color: #333; font-size: 20px; }
        .no-meetings { background: #f8f9fa; padding: 30px; border-radius: 12px; text-align: center; color: #666; }
        
        .resources-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 30px; }
        .resource-category h4 { margin: 0 0 20px; color: #333; font-size: 20px; }
        .portal-links, .template-links { display: flex; flex-direction: column; gap: 15px; }
        .portal-link, .template-link { display: flex; align-items: center; padding: 20px; border-radius: 12px; text-decoration: none; transition: all 0.3s; }
        .portal-link:hover, .template-link:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
        .portal-link.hbcu { background: linear-gradient(135deg, #4caf50 0%, #45a049 100%); color: white; }
        .portal-link.common { background: linear-gradient(135deg, #e91e63 0%, #c2185b 100%); color: white; }
        .portal-link.fafsa { background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%); color: white; }
        .template-link { background: #f8f9fa; color: #333; border: 1px solid #e9ecef; }
        .link-icon { font-size: 32px; margin-right: 20px; }
        .link-content h5 { margin: 0 0 5px; font-size: 18px; }
        .link-content p { margin: 0; font-size: 14px; opacity: 0.8; }
        
        @media (max-width: 768px) {
            .welcome-content { flex-direction: column; text-align: center; gap: 20px; }
            .advisor-section, .resources-grid { grid-template-columns: 1fr; }
            .roadmap-grid, .challenges-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="welcome-content">
            <div class="welcome-text">
                <h1>Welcome back, <?php echo $current_user->display_name; ?>!</h1>
                <p>Your journey to higher education success continues here. Track your progress, upload documents, and stay connected with your advisor.</p>
            </div>
            <div class="progress-circle">
                <div class="progress-text"><?php echo $progress_percentage; ?>%<br><small>Complete</small></div>
            </div>
        </div>
    </div>
    
    <!-- Participant Waiver Alert -->
    <?php
    // Get student waiver status
    $waiver_status = $wpdb->get_var($wpdb->prepare("
        SELECT waiver_status FROM {$wpdb->prefix}tfsp_students 
        WHERE user_id = %d
    ", $current_user->ID)) ?: 'pending';
    
    if ($waiver_status !== 'form_signed_received'): ?>
        <div style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: white; padding: 24px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3); border: 3px solid #fca5a5; animation: pulse-alert 2s infinite;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 20px;">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="font-size: 48px; animation: shake 1s infinite;">⚠️</div>
                    <div>
                        <h3 style="margin: 0 0 8px; font-size: 22px; font-weight: bold; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">🚨 URGENT: Participant Waiver Required</h3>
                        <p style="margin: 0; opacity: 0.95; font-size: 16px; font-weight: 500;">
                            <strong>Action Required:</strong> Download, complete, and return your signed waiver form immediately to continue program participation.
                        </p>
                        <p style="margin: 8px 0 0; font-size: 14px; opacity: 0.9; font-style: italic;">
                            ⏰ This form must be submitted before your next session
                        </p>
                    </div>
                </div>
                <a href="<?php echo plugins_url('assets/participant-waiver-form.pdf', dirname(__FILE__)); ?>" download class="btn" style="background: white; color: #dc2626; padding: 16px 28px; font-weight: bold; font-size: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); border: 2px solid #fca5a5; animation: glow 1.5s infinite alternate; text-decoration: none; border-radius: 8px; white-space: nowrap;">
                    📥 DOWNLOAD NOW
                </a>
            </div>
        </div>
        
        <style>
        @keyframes pulse-alert {
            0%, 100% { box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3); }
            50% { box-shadow: 0 8px 35px rgba(220, 38, 38, 0.6), 0 0 20px rgba(220, 38, 38, 0.4); }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-2px) rotate(-1deg); }
            75% { transform: translateX(2px) rotate(1deg); }
        }
        
        @keyframes glow {
            0% { box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
            100% { box-shadow: 0 4px 20px rgba(220, 38, 38, 0.5), 0 0 15px rgba(255, 255, 255, 0.3); }
        }
        </style>
    <?php endif; ?>
    
    <!-- Your Challenges -->
    <div class="section">
        <h2>🎯 Your Challenges</h2>
        <p>Complete these challenges to boost your college application success</p>
        
        <div class="challenges-grid">
            <?php
            // Get active challenges from database
            $challenges = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tfsp_challenges WHERE is_active = 1 ORDER BY created_at");
            
            if (empty($challenges)): ?>
                <div class="challenge-card" style="text-align: center; color: #666;">
                    <h4>No Challenges Available</h4>
                    <p>Check back soon for new challenges!</p>
                </div>
            <?php else: ?>
                <?php foreach ($challenges as $challenge): 
                    // Calculate progress based on linked roadmap step
                    $progress_percentage = 0;
                    if (isset($challenge->roadmap_step) && $challenge->roadmap_step) {
                        foreach ($student_progress as $p) {
                            if ($p->step_key === $challenge->roadmap_step) {
                                $progress_percentage = $p->status === 'completed' ? 100 : 
                                                     ($p->status === 'in_progress' ? 50 : 0);
                                break;
                            }
                        }
                    }
                ?>
                    <div class="challenge-card">
                        <h4><?php echo esc_html($challenge->title); ?></h4>
                        <div class="difficulty"><?php echo ucfirst($challenge->difficulty); ?></div>
                        <div class="challenge-progress">
                            <div class="challenge-progress-fill" style="width: <?php echo $progress_percentage; ?>%;"></div>
                        </div>
                        <div class="challenge-percentage"><?php echo $progress_percentage; ?>%</div>
                        <?php if ($challenge->description): ?>
                            <p style="font-size: 12px; color: #666; margin-top: 10px;"><?php echo esc_html($challenge->description); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- College Application Roadmap -->
    <div class="section">
        <div class="roadmap-header">
            <h2>Your College Application Roadmap</h2>
            <div class="roadmap-stats">
                <span class="completed"><?php echo count($completed_steps); ?></span> Completed
                <span class="remaining"><?php echo count($roadmap_steps) - count($completed_steps); ?></span> Remaining
            </div>
        </div>
        
        <div class="roadmap-grid">
            <?php 
            $index = 0;
            foreach ($roadmap_steps as $step_key => $step_data): 
                $is_completed = in_array($step_key, $completed_steps);
                $progress_item = array_filter($student_progress, function($p) use ($step_key) { 
                    return isset($p->step_key) && $p->step_key === $step_key; 
                });
                $current_status = !empty($progress_item) ? reset($progress_item)->status : 'pending';
                $current_notes = !empty($progress_item) ? reset($progress_item)->notes : '';
            ?>
                <div class="roadmap-card" style="background: <?php echo $step_data['color']; ?>;" data-step="<?php echo $step_key; ?>">
                    <div class="card-number <?php echo $current_status === 'completed' ? 'completed' : ($current_status === 'in_progress' ? 'in-progress' : ''); ?>" data-original="<?php echo $index + 1; ?>"><?php echo ($current_status === 'completed' || $current_status === 'in_progress') ? '' : ($index + 1); ?></div>
                    <div class="card-content">
                        <h3 class="card-title"><?php echo $step_data['title']; ?></h3>
                        <p class="card-question"><?php echo $step_data['question']; ?></p>
                        <p class="card-completion"><?php echo $step_data['completion']; ?></p>
                        
                        <div class="status-buttons">
                            <button class="status-btn <?php echo $current_status === 'completed' ? 'completed' : 'pending'; ?>" 
                                    onclick="updateStatus('<?php echo $step_key; ?>', 'completed')">Complete</button>
                            <button class="status-btn <?php echo $current_status === 'in_progress' ? 'in-progress' : 'pending'; ?>" 
                                    onclick="updateStatus('<?php echo $step_key; ?>', 'in_progress')">In Progress</button>
                        </div>
                        
                        <?php if ($step_key === 'recommendation_letters'): ?>
                            <?php
                            // Get recommendation settings
                            $rec_settings = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}tfsp_advisor_settings WHERE setting_key IN ('recommendation_link', 'recommendation_email')", OBJECT_K);
                            $rec_link = $rec_settings['recommendation_link']->setting_value ?? '';
                            $rec_email = $rec_settings['recommendation_email']->setting_value ?? '';
                            ?>
                            
                            <div class="recommendation-process" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;">
                                <h5 style="margin: 0 0 10px; color: #333;">📝 For Your Teachers/Coaches</h5>
                                <p style="margin: 0 0 10px; font-size: 14px; color: #666;">
                                    Staff members should submit recommendation letters using the designated process below:
                                </p>
                                
                                <?php if ($rec_link || $rec_email): ?>
                                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                        <?php if ($rec_link): ?>
                                            <a href="<?php echo esc_url($rec_link); ?>" target="_blank" 
                                               style="background: #667eea; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 12px;">
                                                📋 Recommendation Form
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($rec_email): ?>
                                            <a href="mailto:<?php echo esc_attr($rec_email); ?>" 
                                               style="background: #28a745; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 12px;">
                                                ✉️ Email: <?php echo esc_html($rec_email); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <p style="margin: 0; font-size: 12px; color: #e74c3c;">
                                        Recommendation form link not configured. Please contact your program administrator.
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="detail-inputs">
                                <?php
                                // Get saved field data for recommendation letters
                                $saved_data = array();
                                $progress_item = array_filter($student_progress, fn($p) => $p->step_key === 'recommendation_letters');
                                if (!empty($progress_item)) {
                                    $current_progress = reset($progress_item);
                                    if (!empty($current_progress->field_data)) {
                                        $saved_data = json_decode($current_progress->field_data, true) ?: array();
                                    }
                                }
                                ?>
                                <input type="text" class="detail-input" placeholder="Letter 1 Status" data-field="letter1" value="<?php echo esc_attr($saved_data['letter1'] ?? ''); ?>">
                                <input type="text" class="detail-input" placeholder="Enter details..." data-field="letter1_details" value="<?php echo esc_attr($saved_data['letter1_details'] ?? ''); ?>">
                                <input type="text" class="detail-input" placeholder="Letter 2 Status" data-field="letter2" value="<?php echo esc_attr($saved_data['letter2'] ?? ''); ?>">
                                <input type="text" class="detail-input" placeholder="Enter details..." data-field="letter2_details" value="<?php echo esc_attr($saved_data['letter2_details'] ?? ''); ?>">
                            </div>
                        <?php elseif ($step_key === 'community_service'): ?>
                            <div class="detail-inputs">
                                <?php
                                // Get saved field data for community service
                                $saved_data = array();
                                $progress_item = array_filter($student_progress, fn($p) => $p->step_key === 'community_service');
                                if (!empty($progress_item)) {
                                    $current_progress = reset($progress_item);
                                    if (!empty($current_progress->field_data)) {
                                        $saved_data = json_decode($current_progress->field_data, true) ?: array();
                                    }
                                }
                                ?>
                                <input type="text" class="detail-input" placeholder="How many hours obtained?" data-field="hours" value="<?php echo esc_attr($saved_data['hours'] ?? ''); ?>">
                                <input type="text" class="detail-input" placeholder="Enter details..." data-field="details" value="<?php echo esc_attr($saved_data['details'] ?? ''); ?>">
                            </div>
                        <?php elseif ($step_key === 'college_tours'): ?>
                            <div class="detail-inputs">
                                <?php
                                // Get saved field data for college tours
                                $saved_data = array();
                                $progress_item = array_filter($student_progress, fn($p) => $p->step_key === 'college_tours');
                                if (!empty($progress_item)) {
                                    $current_progress = reset($progress_item);
                                    if (!empty($current_progress->field_data)) {
                                        $saved_data = json_decode($current_progress->field_data, true) ?: array();
                                    }
                                }
                                ?>
                                <input type="text" class="detail-input" placeholder="How many tours obtained?" data-field="tours" value="<?php echo esc_attr($saved_data['tours'] ?? ''); ?>">
                                <input type="text" class="detail-input" placeholder="Enter details..." data-field="details" value="<?php echo esc_attr($saved_data['details'] ?? ''); ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php 
                $index++;
            endforeach; 
            ?>
        </div>
    </div>
    
    <!-- Document Management Hub -->
    <div class="section">
        <h2>Document Management Hub</h2>
        <p>Securely upload and track your college application documents</p>
        
        <!-- Community Service Letter Status Banner -->
        <?php if ($cs_letter_status): ?>
            <div style="background: <?php echo $cs_letter_status->approval_status === 'approved' ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)' : 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'; ?>; color: white; padding: 20px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="margin: 0 0 8px; font-size: 20px;">🏆 Community Service Verification Letter</h3>
                        <p style="margin: 0; opacity: 0.95; font-size: 15px;">
                            <?php if ($cs_letter_status->approval_status === 'approved'): ?>
                                ✓ Your 50-hour completion letter has been approved and is ready to download!
                            <?php elseif ($cs_letter_status->approval_status === 'pending'): ?>
                                ⏳ Your letter is uploaded and awaiting approval from Ms. Parkman
                            <?php else: ?>
                                ⚠️ Your letter requires revision. Please contact the program admin.
                            <?php endif; ?>
                        </p>
                        <?php if ($cs_letter_status->approval_status === 'approved'): ?>
                            <p style="margin: 8px 0 0; font-size: 13px; opacity: 0.9;">
                                Approved on <?php echo date('F j, Y', strtotime($cs_letter_status->approved_at)); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <?php if ($cs_letter_status->approval_status === 'approved'): ?>
                        <a href="<?php echo esc_url($cs_letter_status->file_url); ?>" download class="btn" style="background: white; color: #059669; padding: 12px 24px; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                            📥 Download Letter
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="document-layout">
            <!-- Upload Section - 50% -->
            <div class="upload-column">
                <div class="upload-section" id="uploadSection" onclick="document.getElementById('fileInput').click()">
                    <div class="upload-icon" id="uploadIcon">📁</div>
                    <div class="upload-text" id="uploadText">Upload Documents</div>
                    <div class="upload-subtext" id="uploadSubtext">Drag & drop files here or click to browse</div>
                    <input type="file" id="fileInput" multiple accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,image/*" style="display: none;">
                    
                    <!-- File Selected State -->
                    <div class="file-selected" id="fileSelected" style="display: none;">
                        <div class="selected-file-info">
                            <div class="file-icon">📄</div>
                            <div class="file-details">
                                <div class="file-name" id="fileName"></div>
                                <div class="file-size" id="fileSize"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Upload Progress State -->
                    <div class="upload-progress-state" id="uploadProgressState" style="display: none;">
                        <div class="progress-info">
                            <div class="progress-icon">⏳</div>
                            <div class="progress-details">
                                <div class="progress-filename" id="progressFilename"></div>
                                <div class="progress-bar">
                                    <div class="progress-fill" id="progressFill"></div>
                                </div>
                                <div class="progress-text" id="progressText">0%</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="upload-controls">
                    <select id="documentType">
                        <option value="">Select document type</option>
                        <option value="transcript">Official Transcript</option>
                        <option value="essay">Personal Essay</option>
                        <option value="resume">Academic Resume</option>
                        <option value="recommendation">Recommendation Letter</option>
                        <option value="waiver">Participant Waiver Form</option>
                        <option value="other">Other</option>
                    </select>
                    <button class="upload-btn" id="uploadBtn" onclick="uploadDocument()" disabled>Upload Document</button>
                </div>
            </div>
            
            <!-- Documents List - 50% -->
            <div class="documents-column">
                <h4>Your Documents</h4>
                <?php if (empty($student_documents)): ?>
                    <div class="empty-documents">
                        <p><strong>No documents uploaded yet</strong></p>
                        <p>Start by uploading your first document</p>
                    </div>
                <?php else: ?>
                    <div class="documents-list">
                        <?php foreach ($student_documents as $doc): ?>
                            <div class="document-item">
                                <div class="doc-icon">📄</div>
                                <div class="doc-info">
                                    <div class="doc-name"><?php echo esc_html(ucwords(str_replace('_', ' ', $doc->document_type))); ?></div>
                                    <div class="doc-date"><?php echo date('M j, Y', strtotime($doc->upload_date)); ?></div>
                                </div>
                                <div class="doc-status">
                                    <span class="status-badge" style="background: <?php 
                                        echo $doc->status === 'accepted' ? '#27AE60' : 
                                            ($doc->status === 'needs_revision' ? '#E74C3C' : '#F39C12'); 
                                    ?>">
                                        <?php 
                                        $status_labels = array(
                                            'submitted' => 'Submitted',
                                            'accepted' => 'Accepted', 
                                            'needs_revision' => 'Sent Back'
                                        );
                                        echo $status_labels[$doc->status] ?? ucfirst($doc->status);
                                        ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Student Thursday Attendance View -->
    <?php render_student_thursday_attendance($current_user->ID); ?>
    
    <!-- Consolidated Coaching Module -->
    <?php include(TFSP_PLUGIN_PATH . 'templates/components/coach-sessions-consolidated.php'); ?>
    
    <!-- Embedded Calendar Section -->
    <div id="calendarSection" class="calendar-section" style="display: none;">
        <div class="calendar-header">
            <h3>Schedule Your One-on-One Session</h3>
            <button onclick="toggleCalendar()" class="close-calendar">✕</button>
        </div>
        <div class="calendar-container">
            <!-- Calendly inline widget begin -->
            <?php 
            $advisor_settings = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}tfsp_advisor_settings", OBJECT_K);
            ?>
            <div class="calendly-inline-widget" data-url="<?php echo esc_url($advisor_settings['meeting_link']->setting_value ?? 'https://calendly.com'); ?>?hide_event_type_details=1&hide_gdpr_banner=1" style="min-width:320px;height:600px;"></div>
            <script type="text/javascript" src="https://assets.calendly.com/assets/external/widget.js" async></script>
            <!-- Calendly inline widget end -->
        </div>
    </div>
    
    <!-- Resources & Tools -->
    <div class="section">
        <h2>Resources & Tools</h2>
        <p>Essential links and templates to support your college application journey</p>
        
        <?php
        // Get downloadable resources
        $downloadable_resources = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tfsp_resources WHERE is_active = 1 ORDER BY type, name");
        if (!empty($downloadable_resources)):
        ?>
        <div class="downloadable-resources" style="margin-bottom: 30px;">
            <h3 style="color: #1f2937; margin-bottom: 15px;">📥 Download Resources</h3>
            <div class="download-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <?php foreach ($downloadable_resources as $resource): ?>
                <div class="download-item" style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; transition: all 0.2s;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <span style="font-size: 20px;">
                            <?php 
                            switch($resource->type) {
                                case 'template': echo '📄'; break;
                                case 'guide': echo '📖'; break;
                                case 'checklist': echo '✅'; break;
                                default: echo '📋'; break;
                            }
                            ?>
                        </span>
                        <h4 style="margin: 0; color: #1f2937; font-size: 14px;"><?php echo esc_html($resource->name); ?></h4>
                    </div>
                    <?php if ($resource->description): ?>
                    <p style="margin: 0 0 10px; color: #6b7280; font-size: 12px;"><?php echo esc_html($resource->description); ?></p>
                    <?php endif; ?>
                    <a href="<?php echo admin_url('admin-post.php?action=download_student_resource&id=' . $resource->id); ?>" 
                       style="display: inline-block; background: #8ebb79; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 500;">
                        📥 Download
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="resources-grid">
            <div class="resource-category">
                <h4>Application Portals</h4>
                <div class="portal-links">
                    <a href="https://www.hbcuconnect.com/" target="_blank" class="portal-link hbcu">
                        <div class="link-icon">🎓</div>
                        <div class="link-content">
                            <h5>HBCU Common App</h5>
                            <p>Apply to multiple HBCUs with one application</p>
                        </div>
                    </a>
                    <a href="https://www.commonapp.org/" target="_blank" class="portal-link common">
                        <div class="link-icon">📝</div>
                        <div class="link-content">
                            <h5>Common Application</h5>
                            <p>Apply to 900+ colleges and universities</p>
                        </div>
                    </a>
                    <a href="https://studentaid.gov/h/apply-for-aid/fafsa" target="_blank" class="portal-link fafsa">
                        <div class="link-icon">💰</div>
                        <div class="link-content">
                            <h5>FAFSA Application</h5>
                            <p>Apply for federal financial aid</p>
                        </div>
                    </a>
                    <a href="https://www.gafutures.org/" target="_blank" class="portal-link gafutures" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="link-icon">🎯</div>
                        <div class="link-content">
                            <h5>GAFutures</h5>
                            <p>Georgia college search and planning tool</p>
                        </div>
                    </a>
                    <a href="https://gsfc.georgia.gov/" target="_blank" class="portal-link gsfc" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <div class="link-icon">🏛️</div>
                        <div class="link-content">
                            <h5>GSFC</h5>
                            <p>Georgia Student Finance Commission</p>
                        </div>
                    </a>
                </div>
            </div>
            
            <div class="resource-category">
                <h4>Templates & Guides</h4>
                <div class="template-links">
                    <a href="#" class="template-link" onclick="downloadTemplate('resume')">
                        <div class="link-icon">📄</div>
                        <div class="link-content">
                            <h5>Resume Template</h5>
                            <p>Professional academic resume format</p>
                        </div>
                    </a>
                    <a href="#" class="template-link" onclick="downloadTemplate('essay')">
                        <div class="link-icon">📚</div>
                        <div class="link-content">
                            <h5>Essay Guide</h5>
                            <p>Personal statement writing tips</p>
                        </div>
                    </a>
                    <a href="#" class="template-link" onclick="downloadTemplate('checklist')">
                        <div class="link-icon">✅</div>
                        <div class="link-content">
                            <h5>Application Checklist</h5>
                            <p>Complete application timeline</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- College Prep Vocabulary -->
        <div style="margin-top: 50px;">
            <h4 style="margin: 0 0 25px; color: #333; font-size: 20px;">📖 College Prep Vocabulary</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
                
                <div class="template-link" style="cursor: default; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div class="link-icon">💰</div>
                    <div class="link-content">
                        <h5>529 Plan</h5>
                        <p>Tax-advantaged savings plan designed to encourage saving for future college expenses.</p>
                    </div>
                </div>
                
                <div class="template-link" style="cursor: default; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                    <div class="link-icon">📊</div>
                    <div class="link-content">
                        <h5>COA (Cost of Attendance)</h5>
                        <p>Total cost including tuition, room and board, books, lab fees, transportation and living expenses.</p>
                    </div>
                </div>
                
                <div class="template-link" style="cursor: default; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                    <div class="link-icon">📋</div>
                    <div class="link-content">
                        <h5>CSS/Profile</h5>
                        <p>Supplemental financial aid form required by some colleges in addition to the FAFSA.</p>
                    </div>
                </div>
                
                <div class="template-link" style="cursor: default; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                    <div class="link-icon">🎯</div>
                    <div class="link-content">
                        <h5>EFC (Expected Family Contribution)</h5>
                        <p>Number used to determine your eligibility for federal student financial aid.</p>
                    </div>
                </div>
                
                <div class="template-link" style="cursor: default; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                    <div class="link-icon">📝</div>
                    <div class="link-content">
                        <h5>FAFSA</h5>
                        <p>Free Application for Federal Student Aid. The FIRST step in the financial aid process.</p>
                    </div>
                </div>
                
                <div class="template-link" style="cursor: default; background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white;">
                    <div class="link-icon">🔑</div>
                    <div class="link-content">
                        <h5>FSA ID</h5>
                        <p>Provides access to Federal Student Aid's online systems and serves as your legal signature.</p>
                    </div>
                </div>
                
                <div class="template-link" style="cursor: default; background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333;">
                    <div class="link-icon">📄</div>
                    <div class="link-content">
                        <h5>GSFAPP</h5>
                        <p>Submit this to qualify for state aid like the HOPE Scholarship.</p>
                    </div>
                </div>
                
                <div class="template-link" style="cursor: default; background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); color: #333;">
                    <div class="link-icon">🔍</div>
                    <div class="link-content">
                        <h5>GAFutures</h5>
                        <p>College search tool to research colleges and create a list of top choices.</p>
                    </div>
                </div>
                
                <div class="template-link" style="cursor: default; background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #333;">
                    <div class="link-icon">🎁</div>
                    <div class="link-content">
                        <h5>Grant</h5>
                        <p>Money given to students for education that does not have to be repaid.</p>
                    </div>
                </div>
                
                <div class="template-link" style="cursor: default; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div class="link-icon">📈</div>
                    <div class="link-content">
                        <h5>HOPE GPA</h5>
                        <p>Determines eligibility for HOPE or Zell Miller Scholarship. Includes only core subjects.</p>
                    </div>
                </div>
                
                <div class="template-link" style="cursor: default; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                    <div class="link-icon">🔧</div>
                    <div class="link-content">
                        <h5>HOPE Career Grant</h5>
                        <p>Look into skilled trade careers using the HOPE Career Grant.</p>
                    </div>
                </div>
                
                <div class="template-link" style="cursor: default; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                    <div class="link-icon">🏆</div>
                    <div class="link-content">
                        <h5>Merit-Based Aid</h5>
                        <p>Financial aid based on academic, athletic or other achievement.</p>
                    </div>
                </div>
                
                <div class="template-link" style="cursor: default; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                    <div class="link-icon">🤝</div>
                    <div class="link-content">
                        <h5>Need-Based Aid</h5>
                        <p>Financial aid reserved for low-income students.</p>
                    </div>
                </div>
                
                <div class="template-link" style="cursor: default; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                    <div class="link-icon">💵</div>
                    <div class="link-content">
                        <h5>Pell Grant</h5>
                        <p>Federal need-based grants for low-income students. Does not have to be repaid.</p>
                    </div>
                </div>
                
                <div class="template-link" style="cursor: default; background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white;">
                    <div class="link-icon">🎪</div>
                    <div class="link-content">
                        <h5>PROBE College Fairs</h5>
                        <p>Attend PROBE College Fairs or other college prep events in your area.</p>
                    </div>
                </div>
                
                <div class="template-link" style="cursor: default; background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333;">
                    <div class="link-icon">📊</div>
                    <div class="link-content">
                        <h5>SAR (Student Aid Report)</h5>
                        <p>Summarizes your FAFSA information and shows your EFC amount.</p>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<script>
const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

function updateStatus(stepKey, status) {
    console.log('Updating status:', stepKey, status); // Debug
    
    if (!window.jQuery) {
        alert('jQuery not loaded. Please refresh the page.');
        return;
    }
    
    // Find the roadmap card and elements
    const card = document.querySelector(`[data-step="${stepKey}"]`);
    const cardNumber = card?.querySelector('.card-number');
    const completeBtn = card?.querySelector('.status-btn[onclick*="completed"]');
    
    // Immediate visual feedback for completed and in-progress status
    if (card && cardNumber) {
        if (status === 'completed') {
            cardNumber.classList.add('completed');
            cardNumber.classList.remove('in-progress');
            cardNumber.textContent = '';
            if (completeBtn) {
                completeBtn.classList.remove('pending');
                completeBtn.classList.add('completed');
            }
        } else if (status === 'in_progress') {
            cardNumber.classList.add('in-progress');
            cardNumber.classList.remove('completed');
            cardNumber.textContent = '';
            const progressBtn = card?.querySelector('.status-btn[onclick*="in_progress"]');
            if (progressBtn) {
                progressBtn.classList.remove('pending');
                progressBtn.classList.add('in-progress');
            }
        }
    }
    
    jQuery.ajax({
        url: ajaxurl,
        method: 'POST',
        data: {
            action: 'update_roadmap_status',
            step_key: stepKey,
            status: status,
            nonce: '<?php echo wp_create_nonce('roadmap_nonce'); ?>'
        },
        success: function(response) {
            console.log('Response:', response); // Debug
            if (response.success) {
                setTimeout(() => location.reload(), 500);
            } else {
                // Revert visual changes on error
                if (card && cardNumber) {
                    if (status === 'completed') {
                        cardNumber.classList.remove('completed');
                        cardNumber.textContent = cardNumber.dataset.original;
                        if (completeBtn) {
                            completeBtn.classList.remove('completed');
                            completeBtn.classList.add('pending');
                        }
                    } else if (status === 'in_progress') {
                        cardNumber.classList.remove('in-progress');
                        cardNumber.textContent = cardNumber.dataset.original;
                        const progressBtn = card?.querySelector('.status-btn[onclick*="in_progress"]');
                        if (progressBtn) {
                            progressBtn.classList.remove('in-progress');
                            progressBtn.classList.add('pending');
                        }
                    }
                }
                alert('Error: ' + (response.data || 'Failed to update status'));
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error, xhr.responseText); // Debug
            // Revert visual changes on error
            if (card && cardNumber) {
                if (status === 'completed') {
                    cardNumber.classList.remove('completed');
                    cardNumber.textContent = cardNumber.dataset.original;
                    if (completeBtn) {
                        completeBtn.classList.remove('completed');
                        completeBtn.classList.add('pending');
                    }
                } else if (status === 'in_progress') {
                    cardNumber.classList.remove('in-progress');
                    cardNumber.textContent = cardNumber.dataset.original;
                    const progressBtn = card?.querySelector('.status-btn[onclick*="in_progress"]');
                    if (progressBtn) {
                        progressBtn.classList.remove('in-progress');
                        progressBtn.classList.add('pending');
                    }
                }
            }
            alert('Connection error. Please try again.');
        }
    });
}

// Enhanced file upload handling
const uploadSection = document.getElementById('uploadSection');
const fileInput = document.getElementById('fileInput');

// File input change handler
fileInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        showFileSelected(file);
    }
});

// Drag and drop handlers
uploadSection.addEventListener('dragover', function(e) {
    e.preventDefault();
    uploadSection.classList.add('dragover');
});

uploadSection.addEventListener('dragleave', function(e) {
    e.preventDefault();
    uploadSection.classList.remove('dragover');
});

uploadSection.addEventListener('drop', function(e) {
    e.preventDefault();
    uploadSection.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        showFileSelected(files[0]);
    }
});

function showFileSelected(file) {
    // Validate file type
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!allowedTypes.includes(file.type)) {
        alert('❌ Please select a valid file type (PDF, DOC, DOCX, JPG, PNG)');
        return;
    }
    
    // Validate file size (10MB max)
    if (file.size > 10 * 1024 * 1024) {
        alert('❌ File size must be less than 10MB');
        return;
    }
    
    // Hide default upload UI with animation
    const uploadIcon = document.getElementById('uploadIcon');
    const uploadText = document.getElementById('uploadText');
    const uploadSubtext = document.getElementById('uploadSubtext');
    
    uploadIcon.style.animation = 'none';
    uploadIcon.style.transform = 'scale(0)';
    uploadText.style.opacity = '0';
    uploadSubtext.style.opacity = '0';
    
    setTimeout(() => {
        uploadIcon.style.display = 'none';
        uploadText.style.display = 'none';
        uploadSubtext.style.display = 'none';
        
        // Show file selected state
        document.getElementById('fileSelected').style.display = 'block';
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = formatFileSize(file.size);
        
        // Enable upload button with animation
        const uploadBtn = document.getElementById('uploadBtn');
        uploadBtn.disabled = false;
        uploadBtn.style.background = '#4CAF50';
        uploadBtn.textContent = '🚀 Upload ' + file.name.substring(0, 20) + (file.name.length > 20 ? '...' : '');
        
        // Remove click handler from upload section
        uploadSection.onclick = null;
        uploadSection.style.cursor = 'default';
    }, 300);
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function uploadDocument() {
    const fileInput = document.getElementById('fileInput');
    const docType = document.getElementById('documentType').value;
    
    if (fileInput.files.length === 0) {
        alert('Please select a file to upload');
        return;
    }
    
    if (!docType) {
        alert('Please select document type');
        return;
    }
    
    const file = fileInput.files[0];
    
    // Hide file selected state with animation
    const fileSelected = document.getElementById('fileSelected');
    fileSelected.style.transform = 'scale(0.95)';
    fileSelected.style.opacity = '0';
    
    setTimeout(() => {
        fileSelected.style.display = 'none';
        
        // Show progress state
        document.getElementById('uploadProgressState').style.display = 'block';
        document.getElementById('progressFilename').textContent = file.name;
        
        const uploadBtn = document.getElementById('uploadBtn');
        uploadBtn.disabled = true;
        uploadBtn.textContent = '⏳ Uploading...';
        uploadBtn.style.background = '#FF9800';
    }, 200);
    
    // Enhanced progress simulation
    let progress = 0;
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    
    const progressInterval = setInterval(() => {
        progress += Math.random() * 12 + 3;
        if (progress > 90) progress = 90;
        
        progressFill.style.width = progress + '%';
        progressText.textContent = Math.round(progress) + '%';
        
        // Add some visual flair
        if (progress > 50) {
            progressFill.style.background = 'linear-gradient(90deg, #4CAF50 0%, #2E7D32 100%)';
        }
    }, 150);
    
    const formData = new FormData();
    formData.append('action', 'tfsp_upload_general_document');
    formData.append('document', file);
    formData.append('document_type', docType);
    formData.append('nonce', '<?php echo wp_create_nonce('tfsp_nonce'); ?>');
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        timeout: 30000, // 30 second timeout
        success: function(response) {
            clearInterval(progressInterval);
            
            // Complete the progress bar
            progressFill.style.width = '100%';
            progressText.textContent = '100%';
            
            // Wait a moment for visual completion then reload
            setTimeout(() => {
                location.reload();
            }, 500);
        },
        error: function(xhr, status, error) {
            clearInterval(progressInterval);
            console.log('Upload error:', xhr.responseText); // Debug
            
            if (status === 'timeout') {
                alert('❌ Upload timed out. Please try again with a smaller file.');
            } else {
                alert('❌ Upload failed: ' + error);
            }
            resetUploadForm();
        }
    });
}

function showSuccessMessage(message) {
    const successDiv = document.createElement('div');
    successDiv.innerHTML = `
        <div style="
            position: fixed; 
            top: 20px; 
            right: 20px; 
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%); 
            color: white; 
            padding: 15px 25px; 
            border-radius: 12px; 
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.3);
            z-index: 10000;
            animation: slideInRight 0.5s ease;
            font-weight: 600;
        ">
            ${message}
        </div>
    `;
    document.body.appendChild(successDiv);
    
    setTimeout(() => {
        successDiv.remove();
    }, 3000);
}

function resetUploadForm() {
    // Reset to default state with animations
    const uploadIcon = document.getElementById('uploadIcon');
    const uploadText = document.getElementById('uploadText');
    const uploadSubtext = document.getElementById('uploadSubtext');
    
    uploadIcon.style.display = 'block';
    uploadText.style.display = 'block';
    uploadSubtext.style.display = 'block';
    
    setTimeout(() => {
        uploadIcon.style.transform = 'scale(1)';
        uploadIcon.style.animation = 'bounce 2s infinite';
        uploadText.style.opacity = '1';
        uploadSubtext.style.opacity = '1';
    }, 100);
    
    document.getElementById('fileSelected').style.display = 'none';
    document.getElementById('uploadProgressState').style.display = 'none';
    
    const uploadBtn = document.getElementById('uploadBtn');
    uploadBtn.disabled = true;
    uploadBtn.textContent = 'Upload Document';
    uploadBtn.style.background = '#ccc';
    
    document.getElementById('fileInput').value = '';
    document.getElementById('progressFill').style.width = '0%';
    document.getElementById('progressFill').style.background = 'linear-gradient(90deg, #2196f3 0%, #1976d2 100%)';
    
    // Restore click handler
    const uploadSection = document.getElementById('uploadSection');
    uploadSection.onclick = function() {
        document.getElementById('fileInput').click();
    };
    uploadSection.style.cursor = 'pointer';
}

function openMessaging() {
    // Open messaging interface
    alert('Messaging interface would open here');
}

function downloadTemplate(type) {
    alert('Downloading ' + type + ' template...');
}
</script>

<!-- Override styles to ensure they take precedence -->
<style>
/* Force override all conflicting styles */
body .welcome-text h1 { 
    font-size: 32px !important; 
    margin-bottom: 10px !important; 
    color: white !important; 
    font-weight: 700 !important; 
}
body .welcome-text p { 
    font-size: 16px !important; 
    color: white !important; 
    max-width: 600px !important; 
    line-height: 1.6 !important; 
}

body .challenge-card { 
    background: linear-gradient(135deg, #3498DB 0%, #2980B9 100%) !important; 
    color: white !important; 
    padding: 25px !important; 
    border-radius: 12px !important; 
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3) !important; 
}
body .challenge-card h4 { 
    color: white !important; 
    font-size: 20px !important; 
    font-weight: 700 !important; 
}
body .challenge-card .difficulty { 
    color: rgba(255,255,255,0.8) !important; 
    font-size: 12px !important; 
}
body .challenge-percentage { 
    color: white !important; 
    font-size: 14px !important; 
    font-weight: 600 !important; 
}
</style>

<script>
// Save roadmap field data
function saveRoadmapField(stepKey, fieldName, value, inputElement) {
    if (!value.trim()) return;
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'save_roadmap_field',
            step: stepKey,
            field: fieldName,
            value: value,
            nonce: '<?php echo wp_create_nonce('save_roadmap_field'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && inputElement) {
            // Show success indicator
            inputElement.style.borderColor = '#28a745';
            
            // Show "Saved" indicator
            let indicator = inputElement.parentNode.querySelector('.save-indicator');
            if (!indicator) {
                indicator = document.createElement('span');
                indicator.className = 'save-indicator';
                indicator.textContent = '✓ Saved';
                inputElement.parentNode.style.position = 'relative';
                inputElement.parentNode.appendChild(indicator);
            }
            
            indicator.classList.add('show');
            setTimeout(() => {
                inputElement.style.borderColor = '';
                indicator.classList.remove('show');
            }, 2000);
        }
    });
}

// Auto-save on field blur
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.detail-input').forEach(input => {
        input.addEventListener('blur', function() {
            const roadmapCard = this.closest('.roadmap-card');
            if (!roadmapCard) return;
            
            const stepKey = roadmapCard.dataset.step;
            const fieldName = this.dataset.field;
            const value = this.value;
            
            if (stepKey && fieldName && value) {
                saveRoadmapField(stepKey, fieldName, value, this);
            }
        });
    });
});
</script>

<!-- Messaging Modal -->
<div id="messagingModal" class="messaging-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Send Message</h3>
            <span class="close-modal" onclick="closeMessagingModal()">&times;</span>
        </div>
        
        <div class="messaging-tabs">
            <button class="tab-btn active" onclick="switchTab('coach')">College & Career Coach</button>
            <button class="tab-btn" onclick="switchTab('admin')">Program Admin</button>
        </div>
        
        <!-- Coach Messages Tab -->
        <div id="coachTab" class="tab-content active">
            <div class="message-info">
                <p><strong>📧 Email to College & Career Coach</strong></p>
                <p>This message will be sent directly to your coach's email.</p>
            </div>
            
            <form id="coachMessageForm">
                <div class="form-group">
                    <label>Subject:</label>
                    <input type="text" id="coachSubject" required placeholder="Enter message subject">
                </div>
                <div class="form-group">
                    <label>Message:</label>
                    <textarea id="coachMessage" required placeholder="Type your message here..." rows="6"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Send Email</button>
                    <button type="button" onclick="closeMessagingModal()" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
        
        <!-- Admin Messages Tab -->
        <div id="adminTab" class="tab-content">
            <div class="message-info">
                <p><strong>💬 Admin Messages with Program Admin</strong></p>
                <p>Internal portal communications - stays within the system.</p>
            </div>
            
            <!-- Message History -->
            <div class="message-history" id="messageHistory">
                <div class="loading">Loading messages...</div>
            </div>
            
            <!-- Send New Message -->
            <form id="adminMessageForm">
                <div class="form-group">
                    <textarea id="adminMessage" required placeholder="Type your message here..." rows="3"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Send Message</button>
                </div>
            </form>
        </div>
    </div>
</div>



<style>
.messaging-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.modal-header h3 {
    margin: 0;
    color: #333;
}

.close-modal {
    font-size: 24px;
    cursor: pointer;
    color: #999;
}

.close-modal:hover {
    color: #333;
}

.messaging-tabs {
    display: flex;
    border-bottom: 1px solid #eee;
}

.tab-btn {
    flex: 1;
    padding: 15px;
    border: none;
    background: #f8f9fa;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s;
}

.tab-btn.active {
    background: white;
    border-bottom: 2px solid #8BC34A;
    color: #8BC34A;
}

.tab-content {
    display: none;
    padding: 20px;
}

.tab-content.active {
    display: block;
}

.message-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.message-info p {
    margin: 5px 0;
    font-size: 14px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn-primary {
    background: #8BC34A;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
}

.message-history {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    background: #fafafa;
}

.message-item {
    background: white;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 10px;
    border-left: 3px solid #8BC34A;
}

.message-item.admin {
    border-left-color: #007cba;
    background: #f0f8ff;
}

.message-meta {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.loading {
    text-align: center;
    color: #666;
    padding: 20px;
}

/* Mobile Responsive Styles */
@media (max-width: 768px) {
    .container { padding: 15px; }
    
    /* Document Management Hub */
    .document-layout { 
        display: flex !important;
        flex-direction: column !important; 
        gap: 20px !important;
    }
    
    /* CS Letter Banner Mobile */
    .section > div[style*="background: linear-gradient"] > div[style*="display: flex"] {
        flex-direction: column !important;
        gap: 15px !important;
        align-items: flex-start !important;
    }
    
    .section > div[style*="background: linear-gradient"] a.btn {
        width: 100% !important;
        text-align: center !important;
    }
    
    .upload-column, .documents-column { 
        width: 100% !important;
        flex: none !important;
    }
    
    .upload-section { 
        padding: 30px 15px !important;
        min-height: 150px !important;
    }
    
    .upload-icon { font-size: 40px !important; }
    .upload-text { font-size: 16px !important; }
    .upload-subtext { font-size: 12px !important; }
    
    .upload-controls { 
        flex-direction: column !important;
        gap: 10px !important;
    }
    
    .upload-controls select,
    .upload-controls button { 
        width: 100% !important;
        margin: 0 !important;
    }
    
    .document-item { 
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 10px !important;
        padding: 15px !important;
    }
    
    .document-info { 
        width: 100% !important;
    }
    
    .document-actions { 
        width: 100% !important;
        justify-content: flex-start !important;
    }
    
    /* Weekly Attendance */
    .attendance-card { 
        padding: 20px 15px !important;
    }
    
    .week-header { 
        flex-direction: column !important;
        gap: 15px !important;
        text-align: center !important;
    }
    
    .week-info h3 { 
        font-size: 18px !important;
    }
    
    .week-range { 
        font-size: 13px !important;
    }
    
    .week-nav { 
        flex-direction: column !important; 
        gap: 10px !important;
        width: 100% !important;
    }
    
    .week-nav button { 
        width: 100% !important;
        padding: 10px !important;
        font-size: 14px !important;
    }
    
    .attendance-stats { 
        flex-direction: column !important;
        gap: 15px !important;
        margin: 20px 0 !important;
    }
    
    .stat-box { 
        width: 100% !important;
        padding: 15px !important;
    }
    
    .week-days { 
        grid-template-columns: 1fr !important; 
        gap: 10px !important;
    }
    
    .day-card { 
        padding: 15px !important;
    }
    
    .day-header { 
        flex-direction: row !important;
        justify-content: space-between !important;
    }
    
    .day-name { 
        font-size: 14px !important;
    }
    
    .day-date { 
        font-size: 12px !important;
    }
    
    .recent-weeks { 
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 10px !important;
    }
    
    .week-summary { 
        padding: 12px !important;
    }
    
    .week-label { 
        font-size: 12px !important;
    }
    
    .week-percentage { 
        font-size: 20px !important;
    }
    
    .week-details { 
        font-size: 11px !important;
    }
    
    .welcome-banner { 
        padding: 20px; 
        flex-direction: column;
        text-align: center;
    }
    
    .welcome-banner h1 { font-size: 24px; }
    .welcome-banner p { font-size: 14px; }
    
    .progress-circle { 
        width: 80px; 
        height: 80px; 
        margin-top: 15px;
    }
    
    .section { 
        padding: 20px; 
        margin-bottom: 20px;
    }
    
    .section h2 { font-size: 20px; }
    
    .challenges-grid { 
        grid-template-columns: 1fr !important; 
        gap: 15px;
    }
    
    .roadmap-grid { 
        grid-template-columns: 1fr !important; 
        gap: 15px;
    }
    
    .roadmap-item { 
        padding: 15px; 
        flex-direction: column;
        align-items: flex-start;
    }
    
    .roadmap-number { 
        margin-bottom: 10px;
        position: static;
    }
    
    .roadmap-content { padding-left: 0; }
    
    .roadmap-actions { 
        flex-direction: column; 
        width: 100%;
        gap: 8px;
    }
    
    .roadmap-actions button { width: 100%; }
    
    .document-layout { 
        flex-direction: column !important; 
        gap: 20px;
    }
    
    .upload-column, .documents-column { 
        width: 100% !important; 
    }
    
    .advisor-section { 
        flex-direction: column; 
        gap: 20px;
    }
    
    .advisor-card { 
        width: 100% !important; 
        padding: 20px;
    }
    
    .advisor-stats { 
        flex-direction: column; 
        gap: 15px;
    }
    
    .advisor-actions { 
        flex-direction: column; 
        gap: 10px;
    }
    
    .advisor-actions button,
    .advisor-actions a { 
        width: 100%; 
        margin: 0 !important;
    }
    
    .upcoming-section { 
        width: 100% !important; 
        padding: 20px;
    }
    
    .resources-grid { 
        grid-template-columns: 1fr !important; 
        gap: 15px;
    }
    
    .portal-links { 
        grid-template-columns: 1fr !important; 
    }
    
    .vocab-grid { 
        grid-template-columns: 1fr !important; 
        gap: 12px;
    }
    
    .session-stats { 
        grid-template-columns: 1fr !important; 
        gap: 12px;
    }
    
    .stats-grid { 
        grid-template-columns: 1fr !important; 
        gap: 12px;
    }
    
    .modal-content { 
        width: 95% !important; 
        max-width: 95% !important;
        margin: 20px auto;
        max-height: 85vh;
    }
    
    .messaging-tabs { 
        flex-direction: column; 
    }
    
    .tab-btn { 
        width: 100%; 
        text-align: center;
    }
    
    .calendly-inline-widget { 
        height: 600px !important; 
    }
    
    .week-nav { 
        flex-direction: column; 
        gap: 10px;
    }
    
    .week-nav button { 
        width: 100%; 
        padding: 10px;
    }
    
    .week-days { 
        grid-template-columns: 1fr !important; 
        gap: 10px;
    }
    
    .recent-weeks { 
        grid-template-columns: repeat(2, 1fr) !important; 
    }
    
    .tabs { 
        flex-wrap: wrap; 
    }
    
    .tab { 
        flex: 1 1 auto; 
        min-width: 120px;
    }
}

@media (max-width: 480px) {
    .welcome-banner h1 { font-size: 20px; }
    .section h2 { font-size: 18px; }
    .section { padding: 15px; }
    
    /* Document Hub - Extra Small */
    .upload-section { 
        padding: 20px 10px !important;
        min-height: 120px !important;
    }
    
    .upload-icon { font-size: 35px !important; }
    .upload-text { font-size: 14px !important; }
    
    .document-item { 
        padding: 12px !important;
    }
    
    /* Attendance - Extra Small */
    .attendance-card { 
        padding: 15px 10px !important;
    }
    
    .week-info h3 { 
        font-size: 16px !important;
    }
    
    .week-nav button { 
        padding: 8px !important;
        font-size: 13px !important;
    }
    
    .stat-box { 
        padding: 12px !important;
    }
    
    .stat-number { 
        font-size: 28px !important;
    }
    
    .day-card { 
        padding: 12px !important;
    }
    
    .recent-weeks { 
        grid-template-columns: 1fr !important; 
    }
    
    .week-summary { 
        padding: 10px !important;
    }
    
    .progress-circle { 
        width: 70px; 
        height: 70px; 
    }
    
    .progress-number { font-size: 20px; }
    
    .challenge-card, 
    .roadmap-item { 
        padding: 12px; 
    }
    
    .recent-weeks { 
        grid-template-columns: 1fr !important; 
    }
    
    .modal-content { 
        padding: 15px; 
    }
    
    .modal-header h3 { 
        font-size: 18px; 
    }
}
</style>

<script>
function openMessagingModal() {
    document.getElementById('messagingModal').style.display = 'flex';
    loadAdminMessages();
}

function closeMessagingModal() {
    document.getElementById('messagingModal').style.display = 'none';
}

function toggleCalendar() {
    const calendarSection = document.getElementById('calendarSection');
    if (calendarSection.style.display === 'none' || calendarSection.style.display === '') {
        calendarSection.style.display = 'block';
        calendarSection.scrollIntoView({ behavior: 'smooth' });
    } else {
        calendarSection.style.display = 'none';
    }
}

function switchTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`[onclick="switchTab('${tab}')"]`).classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(tab + 'Tab').classList.add('active');
    
    if (tab === 'admin') {
        loadAdminMessages();
    }
}

function loadAdminMessages() {
    const historyDiv = document.getElementById('messageHistory');
    historyDiv.innerHTML = '<div class="loading">Loading messages...</div>';
    
    fetch(ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'tfsp_get_messages',
            nonce: '<?php echo wp_create_nonce('tfsp_nonce'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayMessages(data.data);
        } else {
            historyDiv.innerHTML = '<div class="loading">No messages yet. Start a conversation!</div>';
        }
    });
}

function displayMessages(messages) {
    const historyDiv = document.getElementById('messageHistory');
    
    if (messages.length === 0) {
        historyDiv.innerHTML = '<div class="loading">No messages yet. Start a conversation!</div>';
        return;
    }
    
    let html = '';
    messages.forEach(msg => {
        const isAdmin = msg.sender_type === 'admin';
        html += `
            <div class="message-item ${isAdmin ? 'admin' : ''}">
                <div class="message-meta">
                    <strong>${isAdmin ? 'Program Admin' : 'You'}</strong> - ${msg.created_at}
                </div>
                <div class="message-text">${msg.message}</div>
            </div>
        `;
    });
    
    historyDiv.innerHTML = html;
    historyDiv.scrollTop = historyDiv.scrollHeight;
}

// Handle coach message form submission
document.getElementById('coachMessageForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const subject = document.getElementById('coachSubject').value;
    const message = document.getElementById('coachMessage').value;
    
    // Create a hidden form and submit it normally (no AJAX)
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = window.location.href;
    
    const subjectInput = document.createElement('input');
    subjectInput.type = 'hidden';
    subjectInput.name = 'coach_subject';
    subjectInput.value = subject;
    
    const messageInput = document.createElement('input');
    messageInput.type = 'hidden';
    messageInput.name = 'coach_message';
    messageInput.value = message;
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'send_coach_message';
    actionInput.value = '1';
    
    const nonceInput = document.createElement('input');
    nonceInput.type = 'hidden';
    nonceInput.name = 'coach_nonce';
    nonceInput.value = '<?php echo wp_create_nonce('coach_message'); ?>';
    
    form.appendChild(subjectInput);
    form.appendChild(messageInput);
    form.appendChild(actionInput);
    form.appendChild(nonceInput);
    
    document.body.appendChild(form);
    form.submit();
});

// Handle admin message form
document.getElementById('adminMessageForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const message = document.getElementById('adminMessage').value;
    
    fetch(ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'tfsp_send_admin_message',
            message: message,
            nonce: '<?php echo wp_create_nonce('tfsp_nonce'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('adminMessage').value = '';
            loadAdminMessages(); // Refresh messages
        } else {
            alert('Error sending message: ' + data.data);
        }
    });
});
</script>

</body>
</html>
