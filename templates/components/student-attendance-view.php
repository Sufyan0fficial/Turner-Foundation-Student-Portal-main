<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_student_attendance_view($user_id) {
    global $wpdb;
    
    // Get student's attendance records
    $attendance_records = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM {$wpdb->prefix}tfsp_attendance 
        WHERE student_id = %d 
        ORDER BY date DESC
    ", $user_id));
    
    // Calculate attendance percentage
    $total_sessions = count($attendance_records);
    $present_sessions = count(array_filter($attendance_records, function($record) {
        return $record->status === 'present';
    }));
    $attendance_percentage = $total_sessions > 0 ? round(($present_sessions / $total_sessions) * 100) : 0;
    
    // Get current week for display
    $current_week = array();
    $start = strtotime('monday this week');
    for ($i = 0; $i < 5; $i++) {
        $current_week[] = date('Y-m-d', strtotime("+$i days", $start));
    }
    
    // Organize attendance by date for quick lookup
    $attendance_by_date = array();
    foreach ($attendance_records as $record) {
        $attendance_by_date[$record->date] = $record;
    }
    ?>
    
    <div class="attendance-view-section">
        <h2>📊 Your Attendance</h2>
        <p>View your session attendance and track your participation</p>
        
        <div class="attendance-summary">
            <div class="summary-card">
                <div class="summary-number <?php echo $attendance_percentage >= 80 ? 'good' : ($attendance_percentage >= 60 ? 'warning' : 'poor'); ?>">
                    <?php echo $attendance_percentage; ?>%
                </div>
                <div class="summary-label">Overall Attendance</div>
            </div>
            <div class="summary-card">
                <div class="summary-number"><?php echo $present_sessions; ?></div>
                <div class="summary-label">Sessions Attended</div>
            </div>
            <div class="summary-card">
                <div class="summary-number"><?php echo $total_sessions; ?></div>
                <div class="summary-label">Total Sessions</div>
            </div>
        </div>
        
        <div class="attendance-calendar">
            <h4>Current Week</h4>
            <div class="week-grid">
                <?php foreach ($current_week as $date): ?>
                    <?php 
                    $day_record = array_filter($attendance_records, function($record) use ($date) {
                        return $record->date === $date;
                    });
                    $day_status = !empty($day_record) ? reset($day_record)->status : '';
                    ?>
                    <div class="day-cell">
                        <div class="day-name"><?php echo date('D', strtotime($date)); ?></div>
                        <div class="day-date"><?php echo date('M j', strtotime($date)); ?></div>
                        <div class="day-status <?php echo $day_status; ?>">
                            <?php 
                            $status_labels = array(
                                'present' => 'Present',
                                'excused' => 'Excused',
                                'absent' => 'Absent',
                                'postponed' => 'Postponed'
                            );
                            echo $status_labels[$day_status] ?? 'No Session';
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if (!empty($attendance_records)): ?>
        <div class="attendance-history">
            <h4>Attendance History</h4>
            <div class="history-list">
                <?php foreach (array_slice($attendance_records, 0, 10) as $record): ?>
                    <div class="history-item">
                        <div class="history-date"><?php echo date('M j, Y', strtotime($record->date)); ?></div>
                        <div class="history-status <?php echo $record->status; ?>">
                            <?php 
                            $status_labels = array(
                                'present' => 'Present',
                                'excused' => 'Excused',
                                'absent' => 'Absent',
                                'postponed' => 'Postponed'
                            );
                            echo $status_labels[$record->status] ?? $record->status;
                            ?>
                        </div>
                        <?php if ($record->notes): ?>
                            <div class="history-notes"><?php echo esc_html($record->notes); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <style>
    .attendance-view-section {
        background: white;
        padding: 30px;
        border-radius: 16px;
        margin: 30px 0;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        border: 1px solid #e8f5e8;
    }
    .attendance-view-section h2 {
        margin: 0 0 8px 0;
        color: #2d5016;
        font-size: 24px;
        font-weight: 700;
    }
    .attendance-view-section p {
        margin: 0 0 25px 0;
        color: #666;
    }
    
    .attendance-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .summary-card {
        background: linear-gradient(135deg, #f8fff8 0%, #f0f8f0 100%);
        padding: 25px 20px;
        border-radius: 12px;
        text-align: center;
        border: 2px solid #e8f5e8;
        transition: all 0.3s ease;
    }
    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(139, 195, 74, 0.1);
    }
    .summary-number {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .summary-number.good { color: #27AE60; }
    .summary-number.warning { color: #F39C12; }
    .summary-number.poor { color: #E74C3C; }
    .summary-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }
    
    .attendance-calendar h4 {
        margin: 0 0 15px 0;
        color: #2d5016;
        font-size: 18px;
        font-weight: 600;
    }
    .week-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    .day-cell {
        background: white;
        padding: 20px 15px;
        border-radius: 12px;
        text-align: center;
        border: 2px solid #f0f0f0;
        transition: all 0.3s ease;
        min-height: 120px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .day-cell.present {
        border-color: #27AE60;
        background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e8 100%);
    }
    .day-cell.excused {
        border-color: #2980B9;
        background: linear-gradient(135deg, #e3f2fd 0%, #e1f5fe 100%);
    }
    .day-cell.absent {
        border-color: #E74C3C;
        background: linear-gradient(135deg, #ffebee 0%, #fce4ec 100%);
    }
    .day-cell.postponed {
        border-color: #F39C12;
        background: linear-gradient(135deg, #fff3e0 0%, #fff8e1 100%);
    }
    .day-name {
        font-size: 12px;
        color: #666;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }
    .day-date {
        font-weight: 600;
        margin-bottom: 10px;
        color: #333;
        font-size: 16px;
    }
    .day-status {
        font-size: 14px;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 20px;
        display: inline-block;
    }
    .day-status.present {
        background: rgba(39, 174, 96, 0.1);
        color: #27AE60;
    }
    .day-status.excused {
        background: rgba(41, 128, 185, 0.1);
        color: #2980B9;
    }
    .day-status.absent {
        background: rgba(231, 76, 60, 0.1);
        color: #E74C3C;
    }
    .day-status.postponed {
        background: rgba(243, 156, 18, 0.1);
        color: #F39C12;
    }
    .day-status:empty {
        background: rgba(149, 165, 166, 0.1);
        color: #95A5A6;
    }
    
    .attendance-history h4 {
        margin: 0 0 15px 0;
        color: #2d5016;
        font-size: 18px;
        font-weight: 600;
    }
    .history-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .history-item {
        display: flex;
        align-items: center;
        padding: 15px 20px;
        background: linear-gradient(135deg, #f8fff8 0%, #f0f8f0 100%);
        border-radius: 12px;
        border-left: 4px solid #e0e0e0;
        transition: all 0.3s ease;
        flex-wrap: wrap;
        gap: 10px;
    }
    .history-item:hover {
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(139, 195, 74, 0.1);
    }
    .history-item.present { border-left-color: #27AE60; }
    .history-item.excused { border-left-color: #2980B9; }
    .history-item.absent { border-left-color: #E74C3C; }
    .history-item.postponed { border-left-color: #F39C12; }
    .history-date {
        font-weight: 600;
        min-width: 100px;
        color: #2d5016;
        font-size: 14px;
    }
    .history-status {
        font-weight: 600;
        font-size: 14px;
        flex: 1;
        min-width: 80px;
    }
    .history-status.present { color: #27AE60; }
    .history-status.excused { color: #2980B9; }
    .history-status.absent { color: #E74C3C; }
    .history-status.postponed { color: #F39C12; }
    .history-notes {
        font-size: 12px;
        color: #666;
        font-style: italic;
        flex: 1;
        min-width: 200px;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .attendance-view-section {
            padding: 20px;
            margin: 20px 0;
        }
        
        .attendance-view-section h2 {
            font-size: 20px;
        }
        
        .attendance-summary {
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
        }
        
        .summary-card {
            padding: 20px 15px;
        }
        
        .summary-number {
            font-size: 28px;
        }
        
        .week-grid {
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
        }
        
        .day-cell {
            padding: 15px 10px;
            min-height: 100px;
        }
        
        .day-date {
            font-size: 14px;
        }
        
        .day-status {
            font-size: 12px;
            padding: 4px 8px;
        }
        
        .history-item {
            flex-direction: column;
            align-items: flex-start;
            padding: 15px;
        }
        
        .history-date {
            min-width: auto;
            font-size: 13px;
        }
        
        .history-status {
            min-width: auto;
            font-size: 13px;
        }
        
        .history-notes {
            min-width: auto;
            margin-top: 5px;
        }
    }
    
    @media (max-width: 480px) {
        .attendance-summary {
            grid-template-columns: 1fr;
        }
        
        .week-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .summary-number {
            font-size: 24px;
        }
        
        .day-cell {
            min-height: 80px;
            padding: 10px;
        }
    }
    </style>
    <?php
}
?>
