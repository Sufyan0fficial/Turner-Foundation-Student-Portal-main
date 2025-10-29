<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_student_thursday_attendance($user_id) {
    global $wpdb;
    
    // Get Thursday attendance records for the program (14 weeks total)
    // Determine program start dynamically: settings -> earliest attendance -> today
    $program_start = null;
    // 1) Attempt settings table value if available
    $settings_table = $wpdb->prefix . 'tfsp_settings';
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $settings_table))) {
        $ps = $wpdb->get_var($wpdb->prepare("SELECT setting_value FROM $settings_table WHERE setting_key = %s", 'program_start_date'));
        if (!empty($ps)) { $program_start = $ps; }
    }
    // 2) Fall back to earliest attendance for this student
    if (!$program_start) {
        $earliest = null;
        // Prefer session-based attendance
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . 'tfsp_attendance_records')) &&
            $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . 'tfsp_sessions'))) {
            $earliest = $wpdb->get_var($wpdb->prepare(
                "SELECT MIN(s.session_date) FROM {$wpdb->prefix}tfsp_attendance_records ar
                 JOIN {$wpdb->prefix}tfsp_sessions s ON s.id = ar.session_id
                 WHERE ar.student_id = %d",
                $user_id
            ));
        }
        // Legacy attendance table fallback
        if (!$earliest) {
            $legacy = $wpdb->prefix . 'tfsp_attendance';
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $legacy))) {
                $date_col = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM $legacy LIKE %s", 'session_date')) ? 'session_date' : 'date';
                $earliest = $wpdb->get_var($wpdb->prepare("SELECT MIN($date_col) FROM $legacy WHERE student_id = %d", $user_id));
            }
        }
        $program_start = $earliest ?: date('Y-m-d');
    }
    $thursdays = array();
    
    // Generate 14 Thursdays starting from the Thursday of the program_start week
    $base_ts = strtotime($program_start);
    $base_dow = intval(date('N', $base_ts)); // 1=Mon..7=Sun
    $base_monday = strtotime('-' . ($base_dow - 1) . ' days', $base_ts);
    $current_date = date('Y-m-d', strtotime('+3 days', $base_monday));
    for ($i = 0; $i < 14; $i++) {
        $thursday_date = date('Y-m-d', strtotime("+$i weeks", strtotime($current_date)));
        $thursdays[] = array(
            'date' => $thursday_date,
            'week_number' => $i + 1,
            'display_date' => date('M j, Y', strtotime($thursday_date)),
            'is_past' => strtotime($thursday_date) < strtotime('today'),
            'is_today' => $thursday_date === date('Y-m-d'),
            'is_future' => strtotime($thursday_date) > strtotime('today')
        );
    }
    
    // Get student's attendance records (all days, not just Thursdays)
    // Prefer session-based records if available, fallback to date-only
    $records_table = $wpdb->prefix . 'tfsp_attendance_records';
    $has_session_id = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM $records_table LIKE %s", 'session_id')) ? true : false;
    $sessions_table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . 'tfsp_sessions')) ? true : false;

    if ($has_session_id && $sessions_table_exists) {
        $attendance_records = $wpdb->get_results($wpdb->prepare(
            "SELECT s.session_date as date, ar.status
             FROM {$wpdb->prefix}tfsp_attendance_records ar
             JOIN {$wpdb->prefix}tfsp_sessions s ON s.id = ar.session_id
             WHERE ar.student_id = %d
             ORDER BY s.session_date",
            $user_id
        ));
    } else {
        // Fallback 1: tfsp_attendance_records without session_id
        $attendance_records = $wpdb->get_results($wpdb->prepare(
            "SELECT session_date as date, status
             FROM {$wpdb->prefix}tfsp_attendance_records
             WHERE student_id = %d
             ORDER BY session_date",
            $user_id
        ));

        // Fallback 2: legacy tfsp_attendance table
        if (empty($attendance_records)) {
            $legacy_table = $wpdb->prefix . 'tfsp_attendance';
            // Determine date column name
            $date_col = 'session_date';
            $has_session_date = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM $legacy_table LIKE %s", 'session_date')) ? true : false;
            if (!$has_session_date) {
                $date_col = 'date';
            }
            // Only proceed if table exists
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $legacy_table))) {
                $attendance_records = $wpdb->get_results($wpdb->prepare(
                    "SELECT $date_col as date, status FROM $legacy_table WHERE student_id = %d ORDER BY $date_col",
                    $user_id
                ));
            }
        }
    }
    
    // Organize attendance by date
    $attendance_by_date = array();
    foreach ($attendance_records as $record) {
        // Normalize statuses to match unified set
        $status = $record->status;
        if ($status === 'excused') { $status = 'excused_absence'; }
        if ($status === 'didnt_attend') { $status = 'did_not_attend'; }
        $attendance_by_date[$record->date] = $status;
    }
    
    // Aggregate weekly status (Thursday of each week)
    $weekly_status = array();
    $priority = array('present' => 3, 'excused_absence' => 2, 'absent' => 1, 'did_not_attend' => 1);
    foreach ($attendance_by_date as $d => $st) {
        $ts = strtotime($d);
        $dow = intval(date('N', $ts)); // 1=Mon..7=Sun
        $monday = strtotime('-' . ($dow - 1) . ' days', $ts);
        $thu = date('Y-m-d', strtotime('+3 days', $monday));
        $existing = isset($weekly_status[$thu]) ? $weekly_status[$thu] : null;
        if ($existing === null || ($priority[$st] ?? 0) > ($priority[$existing] ?? 0)) {
            $weekly_status[$thu] = $st;
        }
    }

    // Merge weekly aggregate into per-date map so UI displays labels for weeks without exact Thursday marks
    foreach ($weekly_status as $d => $st) {
        if (!isset($attendance_by_date[$d])) {
            $attendance_by_date[$d] = $st;
        }
    }

    // Calculate total weeks present (prefer exact Thursday, else weekly aggregate)
    $weeks_present = 0;
    foreach ($thursdays as $thursday) {
        $d = $thursday['date'];
        $st = isset($attendance_by_date[$d]) ? $attendance_by_date[$d] : (isset($weekly_status[$d]) ? $weekly_status[$d] : null);
        if ($st && in_array($st, ['present', 'excused_absence'])) {
            $weeks_present++;
        }
    }
    
    $attendance_percentage = round(($weeks_present / 14) * 100);
    ?>
    
    <div class="thursday-attendance-section">
        <div class="attendance-header">
            <h2>Weekly Attendance</h2>
            <p>YCAM Mentorship Program meets every Thursday</p>
        </div>
        
        <div class="attendance-summary">
            <div class="summary-stat">
                <span class="stat-number"><?php echo $weeks_present; ?> out of 14 weeks</span>
                <span class="stat-label">Weeks Present</span>
            </div>
            <div class="summary-stat">
                <span class="stat-number"><?php echo $attendance_percentage; ?>%</span>
                <span class="stat-label">Attendance Rate</span>
            </div>
        </div>
        
        <div class="thursday-timeline">
            <?php foreach ($thursdays as $thursday): ?>
                <div class="thursday-item <?php 
                    if ($thursday['is_today']) echo 'today ';
                    if ($thursday['is_past']) echo 'past ';
                    if ($thursday['is_future']) echo 'future ';
                    if (isset($attendance_by_date[$thursday['date']])) {
                        echo $attendance_by_date[$thursday['date']] === 'present' ? 'present' : 
                             ($attendance_by_date[$thursday['date']] === 'excused_absence' ? 'excused' : 'absent');
                    } elseif (isset($weekly_status[$thursday['date']])) {
                        echo $weekly_status[$thursday['date']] === 'present' ? 'present' : 
                             ($weekly_status[$thursday['date']] === 'excused_absence' ? 'excused' : 'absent');
                    } else {
                        echo $thursday['is_future'] ? 'upcoming' : 'no-record';
                    }
                ?>">
                    <div class="thursday-date">
                        <span class="week-number">Week <?php echo $thursday['week_number']; ?></span>
                        <span class="date"><?php echo $thursday['display_date']; ?></span>
                    </div>
                    <div class="attendance-status">
                        <?php 
                        if (isset($attendance_by_date[$thursday['date']])) {
                            switch ($attendance_by_date[$thursday['date']]) {
                                case 'present':
                                    echo '<span class="status-icon">✓</span><span class="status-text">Present</span>';
                                    break;
                                case 'excused_absence':
                                    echo '<span class="status-icon">E</span><span class="status-text">Excused</span>';
                                    break;
                                case 'absent':
                                    echo '<span class="status-icon">✗</span><span class="status-text">Absent</span>';
                                    break;
                            }
                        } else {
                            if ($thursday['is_future']) {
                                echo '<span class="status-icon">○</span><span class="status-text">Upcoming</span>';
                            } else {
                                echo '<span class="status-icon">-</span><span class="status-text">No Record</span>';
                            }
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <style>
    .thursday-attendance-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 16px;
        margin-bottom: 30px;
    }
    
    .attendance-header h2 {
        color: white !important;
        font-size: 24px !important;
        margin: 0 0 8px 0 !important;
        font-weight: 700;
    }
    
    .attendance-header p {
        color: rgba(255,255,255,0.9) !important;
        margin: 0 0 25px 0 !important;
        font-size: 16px;
    }
    
    .attendance-summary {
        display: flex;
        gap: 30px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }
    
    .summary-stat {
        display: flex;
        flex-direction: column;
        align-items: center;
        background: rgba(255,255,255,0.1);
        padding: 20px;
        border-radius: 12px;
        min-width: 150px;
    }
    
    .stat-number {
        font-size: 24px;
        font-weight: 700;
        color: white;
        margin-bottom: 5px;
    }
    
    .stat-label {
        font-size: 14px;
        color: rgba(255,255,255,0.8);
        text-align: center;
    }
    
    .thursday-timeline {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 15px;
        max-height: 400px;
        overflow-y: auto;
        padding-right: 10px;
    }
    
    .thursday-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: rgba(255,255,255,0.1);
        padding: 15px 20px;
        border-radius: 8px;
        border-left: 4px solid transparent;
        transition: all 0.3s ease;
    }
    
    .thursday-item.present {
        border-left-color: #10b981;
        background: rgba(16, 185, 129, 0.2);
    }
    
    .thursday-item.excused {
        border-left-color: #f59e0b;
        background: rgba(245, 158, 11, 0.2);
    }
    
    .thursday-item.absent {
        border-left-color: #ef4444;
        background: rgba(239, 68, 68, 0.2);
    }
    
    .thursday-item.upcoming {
        border-left-color: #6b7280;
        background: rgba(107, 114, 128, 0.2);
    }
    
    .thursday-item.today {
        border-left-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.3);
        box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
    }
    
    .thursday-date {
        display: flex;
        flex-direction: column;
    }
    
    .week-number {
        font-size: 12px;
        color: rgba(255,255,255,0.7);
        margin-bottom: 2px;
    }
    
    .date {
        font-size: 14px;
        font-weight: 600;
        color: white;
    }
    
    .attendance-status {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .status-icon {
        font-size: 16px;
        font-weight: bold;
    }
    
    .status-text {
        font-size: 13px;
        font-weight: 500;
    }
    
    @media (max-width: 768px) {
        .thursday-timeline {
            grid-template-columns: 1fr;
        }
        
        .attendance-summary {
            flex-direction: column;
            gap: 15px;
        }
        
        .summary-stat {
            min-width: auto;
        }
    }
    </style>
    
    <?php
}
?>

