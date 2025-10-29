<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_student_weekly_attendance($user_id) {
    global $wpdb;
    
    // Get selected week or default to current
    $selected_week = isset($_GET['week']) ? sanitize_text_field($_GET['week']) : date('Y-m-d', strtotime('monday this week'));
    
    // Calculate week dates (Monday to Friday)
    $week_start = date('Y-m-d', strtotime('monday', strtotime($selected_week)));
    $week_dates = array();
    for ($i = 0; $i < 5; $i++) {
        $date = date('Y-m-d', strtotime("+$i days", strtotime($week_start)));
        $week_dates[] = array(
            'date' => $date,
            'day_name' => date('l', strtotime($date)),
            'day_short' => date('D', strtotime($date)),
            'day_number' => date('j', strtotime($date)),
            'month' => date('M', strtotime($date))
        );
    }
    
    // Get student's attendance records from tfsp_attendance_records table (join with sessions)
    $attendance_records = $wpdb->get_results($wpdb->prepare("
        SELECT s.session_date as date, ar.status
        FROM {$wpdb->prefix}tfsp_attendance_records ar
        JOIN {$wpdb->prefix}tfsp_sessions s ON s.id = ar.session_id
        WHERE ar.student_id = %d AND s.session_date >= %s AND s.session_date <= %s
        ORDER BY s.session_date
    ", $user_id, $week_start, $week_dates[4]['date']));
    
    // Organize by date
    $attendance_by_date = array();
    foreach ($attendance_records as $record) {
        $attendance_by_date[$record->date] = $record;
    }
    
    // Calculate week stats
    $total_sessions = count($attendance_records);
    $present_count = count(array_filter($attendance_records, function($r) { 
        return $r->status === 'present'; 
    }));
    $week_percentage = $total_sessions > 0 ? round(($present_count / $total_sessions) * 100) : 0;
    
    // Week navigation
    $prev_week = date('Y-m-d', strtotime('-1 week', strtotime($week_start)));
    $next_week = date('Y-m-d', strtotime('+1 week', strtotime($week_start)));
    $current_week = date('Y-m-d', strtotime('monday this week'));
    
    $week_display = date('M j', strtotime($week_start)) . ' - ' . date('M j, Y', strtotime($week_dates[4]['date']));
    ?>
    
    <div class="student-weekly-attendance">
        <!-- Header -->
        <div class="attendance-header">
            <div class="header-content" style="text-align: left; margin-bottom: 20px; display: block; width: 100%;">
                <h2 style="color: white !important; font-size: 28px !important; margin: 0 0 8px 0 !important; font-weight: 700; display: block; width: 100%;">📅 YCAM Mentorship Program Weekly Attendance</h2>
                <p class="attendance-subtitle" style="color: white !important; font-size: 16px !important; margin: 0 !important; opacity: 0.95; display: block; width: 100%; clear: both;">Track your attendance progress week by week</p>
            </div>
            <div class="week-info" style="text-align: left; margin-top: 20px;">
                <div class="current-week">
                    <span class="week-label" style="color: rgba(255,255,255,0.8) !important; display: block; font-size: 13px; margin-bottom: 6px;">Viewing Week</span>
                    <span class="week-dates" style="color: white !important; font-size: 18px; font-weight: 600; display: block;"><?php echo $week_display; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Week Navigation -->
        <div class="week-navigation">
            <div class="nav-controls">
                <button class="nav-btn prev" onclick="changeWeek('<?php echo $prev_week; ?>')">
                    <i class="icon">←</i>
                    <span>Previous Week</span>
                </button>
                
                <button class="current-week-btn <?php echo ($selected_week === $current_week) ? 'active' : ''; ?>" 
                        onclick="changeWeek('<?php echo $current_week; ?>')">
                    <i class="icon">📅</i>
                    <span>Current Week</span>
                </button>
                
                <button class="nav-btn next" onclick="changeWeek('<?php echo $next_week; ?>')">
                    <span>Next Week</span>
                    <i class="icon">→</i>
                </button>
            </div>
            
            <div class="week-stats-summary">
                <div class="stat-item">
                    <span class="stat-number <?php echo $week_percentage >= 80 ? 'excellent' : ($week_percentage >= 60 ? 'good' : 'poor'); ?>">
                        <?php echo $week_percentage; ?>%
                    </span>
                    <span class="stat-label">Week Attendance</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $present_count; ?>/<?php echo $total_sessions; ?></span>
                    <span class="stat-label">Days Present</span>
                </div>
            </div>
        </div>
        
        <!-- Week Calendar -->
        <div class="week-calendar">
            <div class="calendar-header">
                <h3>Week Overview</h3>
            </div>
            
            <div class="days-grid">
                <?php foreach ($week_dates as $day): ?>
                    <?php 
                    $day_record = isset($attendance_by_date[$day['date']]) ? $attendance_by_date[$day['date']] : null;
                    $status = $day_record ? $day_record->status : 'unmarked';
                    $is_future = strtotime($day['date']) > time();
                    ?>
                    <div class="day-card <?php echo $status; ?> <?php echo $is_future ? 'future' : ''; ?>">
                        <div class="day-header">
                            <div class="day-name"><?php echo $day['day_name']; ?></div>
                            <div class="day-date">
                                <span class="day-number"><?php echo $day['day_number']; ?></span>
                                <span class="month"><?php echo $day['month']; ?></span>
                            </div>
                        </div>
                        
                        <div class="day-status">
                            <?php if ($is_future): ?>
                                <div class="status-indicator future">
                                    <i class="icon">⏰</i>
                                    <span>Upcoming</span>
                                </div>
                            <?php elseif ($day_record): ?>
                                <div class="status-indicator <?php echo $status; ?>">
                                    <?php 
                                    $status_config = array(
                                        'present' => array('icon' => '✓', 'label' => 'Present'),
                                        'did_not_attend' => array('icon' => '✗', 'label' => 'Absent'),
                                        'excused_absence' => array('icon' => 'E', 'label' => 'Excused'),
                                        'postponed' => array('icon' => '⏸', 'label' => 'Postponed')
                                    );
                                    $config = $status_config[$status] ?? array('icon' => '○', 'label' => 'Not Marked');
                                    ?>
                                    <i class="icon"><?php echo $config['icon']; ?></i>
                                    <span><?php echo $config['label']; ?></span>
                                </div>
                                <?php if (!empty($day_record->notes)): ?>
                                    <div class="day-notes">
                                        <i class="icon">💬</i>
                                        <span><?php echo esc_html($day_record->notes); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="status-indicator unmarked">
                                    <i class="icon">○</i>
                                    <span>Not Marked</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Recent Weeks Summary -->
        <div class="recent-weeks">
            <div class="section-header">
                <h3 style="display: block !important; margin-bottom: 8px !important;">Recent Weeks</h3>
                <p class="section-subtitle" style="display: block !important; margin: 0 0 24px 0 !important;">Your attendance history over the past few weeks</p>
            </div>
            
            <div class="weeks-timeline">
                <?php 
                // Get last 4 weeks including current
                for ($i = 3; $i >= 0; $i--) {
                    $timeline_week_start = date('Y-m-d', strtotime("-$i weeks", strtotime($current_week)));
                    $timeline_week_end = date('Y-m-d', strtotime('+4 days', strtotime($timeline_week_start)));
                    
                    // Get attendance for this week from tfsp_attendance_records
                    $timeline_records = $wpdb->get_results($wpdb->prepare("
                        SELECT session_date as date, status
                        FROM {$wpdb->prefix}tfsp_attendance_records
                        WHERE student_id = %d AND session_date >= %s AND session_date <= %s
                    ", $user_id, $timeline_week_start, $timeline_week_end));
                    
                    $timeline_present = count(array_filter($timeline_records, function($r) { 
                        return $r->status === 'present'; 
                    }));
                    $timeline_total = count($timeline_records);
                    $timeline_percentage = $timeline_total > 0 ? round(($timeline_present / $timeline_total) * 100) : 0;
                    
                    $is_current_week = ($timeline_week_start === $current_week);
                    $week_label = $is_current_week ? 'This Week' : date('M j', strtotime($timeline_week_start));
                ?>
                    <div class="timeline-week <?php echo $is_current_week ? 'current' : ''; ?>" 
                         onclick="changeWeek('<?php echo $timeline_week_start; ?>')">
                        <div class="timeline-header">
                            <div class="timeline-date"><?php echo $week_label; ?></div>
                            <div class="timeline-percentage <?php echo $timeline_percentage >= 80 ? 'excellent' : ($timeline_percentage >= 60 ? 'good' : 'poor'); ?>">
                                <?php echo $timeline_percentage; ?>%
                            </div>
                        </div>
                        <div class="timeline-bar">
                            <div class="timeline-fill" style="width: <?php echo $timeline_percentage; ?>%;"></div>
                        </div>
                        <div class="timeline-stats">
                            <?php echo $timeline_present; ?>/<?php echo $timeline_total; ?> days present
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    
    <style>
    .student-weekly-attendance {
        background: white;
        border-radius: 16px;
        margin: 20px 0;
        box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    
    .attendance-header {
        background: linear-gradient(135deg, #3f5340 0%, #2d3e2f 100%);
        color: white;
        padding: 32px 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .header-content {
        flex: 1;
    }
    
    .header-content h2 {
        margin: 0 0 12px 0 !important;
        font-size: 24px;
        font-weight: 700;
        display: block !important;
        width: 100%;
    }
    
    .header-content .attendance-subtitle {
        margin: 0 !important;
        opacity: 0.9;
        display: block !important;
        font-size: 15px;
        clear: both;
    }
    
    .week-info {
        text-align: right;
    }
    
    .week-label {
        display: block;
        font-size: 14px;
        opacity: 0.8;
        margin-bottom: 4px;
    }
    
    .week-dates {
        font-size: 18px;
        font-weight: 600;
    }
    
    .week-navigation {
        background: #f8fafc;
        padding: 24px 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .nav-controls {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .nav-btn, .current-week-btn {
        background: white;
        border: 2px solid #e2e8f0;
        padding: 12px 20px;
        border-radius: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        transition: all 0.2s ease;
        color: #475569;
    }
    
    .current-week-btn {
        background: linear-gradient(135deg, #3f5340 0%, #2d3e2f 100%);
        color: white;
        border: none;
    }
    
    .current-week-btn.active {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    
    .nav-btn:hover, .current-week-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    }
    
    .week-stats-summary {
        display: flex;
        gap: 24px;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-number {
        display: block;
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 4px;
    }
    
    .stat-number.excellent { color: #10b981; }
    .stat-number.good { color: #f59e0b; }
    .stat-number.poor { color: #ef4444; }
    
    .stat-label {
        font-size: 12px;
        color: #64748b;
        font-weight: 600;
    }
    
    .week-calendar {
        padding: 32px 40px;
    }
    
    .calendar-header h3 {
        margin: 0 0 24px 0;
        color: #1e293b;
        font-size: 20px;
        font-weight: 700;
    }
    
    .days-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 20px;
    }
    
    .day-card {
        background: white;
        border: 2px solid #f1f5f9;
        border-radius: 16px;
        padding: 20px;
        transition: all 0.2s ease;
        min-height: 140px;
    }
    
    .day-card.present {
        border-color: #10b981;
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    }
    
    .day-card.absent {
        border-color: #ef4444;
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    }
    
    .day-card.excused {
        border-color: #3b82f6;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    }
    
    .day-card.future {
        border-color: #d1d5db;
        background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        opacity: 0.7;
    }
    
    .day-header {
        text-align: center;
        margin-bottom: 16px;
    }
    
    .day-name {
        font-size: 14px;
        color: #64748b;
        margin-bottom: 8px;
        font-weight: 600;
    }
    
    .day-number {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .month {
        font-size: 12px;
        color: #64748b;
        margin-left: 4px;
    }
    
    .status-indicator {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        padding: 12px;
        border-radius: 12px;
        font-weight: 600;
    }
    
    .status-indicator.present {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
    }
    
    .status-indicator.absent {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
    }
    
    .status-indicator.excused {
        background: rgba(59, 130, 246, 0.1);
        color: #2563eb;
    }
    
    .status-indicator.future {
        background: rgba(107, 114, 128, 0.1);
        color: #6b7280;
    }
    
    .status-indicator.unmarked {
        background: rgba(156, 163, 175, 0.1);
        color: #9ca3af;
    }
    
    .day-notes {
        margin-top: 12px;
        padding: 8px;
        background: rgba(0,0,0,0.05);
        border-radius: 8px;
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .recent-weeks {
        background: #f8fafc;
        padding: 32px 40px;
        border-top: 1px solid #e2e8f0;
    }
    
    .section-header h3 {
        margin: 0 0 8px 0;
        color: #1e293b;
        font-size: 20px;
        font-weight: 700;
        display: block;
    }
    
    .section-header p,
    .section-header .section-subtitle {
        margin: 0 0 24px 0 !important;
        color: #64748b;
        font-size: 14px;
        display: block !important;
    }
    
    .weeks-timeline {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }
    
    .timeline-week {
        background: white;
        padding: 20px;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 2px solid #f1f5f9;
    }
    
    .timeline-week:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }
    
    .timeline-week.current {
        border-color: #667eea;
        background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%);
    }
    
    .timeline-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }
    
    .timeline-date {
        font-weight: 600;
        color: #1e293b;
    }
    
    .timeline-percentage {
        font-weight: 700;
        font-size: 14px;
    }
    
    .timeline-percentage.excellent { color: #10b981; }
    .timeline-percentage.good { color: #f59e0b; }
    .timeline-percentage.poor { color: #ef4444; }
    
    .timeline-bar {
        height: 6px;
        background: #f1f5f9;
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 8px;
    }
    
    .timeline-fill {
        height: 100%;
        background: linear-gradient(135deg, #3f5340 0%, #2d3e2f 100%);
        transition: width 0.3s ease;
    }
    
    .timeline-stats {
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
    }
    
    @media (max-width: 768px) {
        .attendance-header {
            flex-direction: column;
            gap: 16px;
            text-align: center;
            padding: 20px;
        }
        
        .week-navigation {
            flex-direction: column;
            gap: 16px;
            padding: 16px 20px;
        }
        
        .days-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
        
        .weeks-timeline {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .week-calendar, .recent-weeks {
            padding: 20px;
        }
    }
    </style>
    
    <script>
    function changeWeek(week) {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('week', week);
        window.location.href = currentUrl.toString();
    }
    </script>
    <?php
}
?>
