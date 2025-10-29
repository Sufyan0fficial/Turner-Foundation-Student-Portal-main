<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_attendance_tracking_fixed() {
    $students = get_users(array('role' => 'subscriber'));
    
    // Get selected month or default to current
    $selected_month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : date('Y-m');
    
    // Generate all weekdays for the selected month
    $year = date('Y', strtotime($selected_month . '-01'));
    $month = date('m', strtotime($selected_month . '-01'));
    $days_in_month = date('t', strtotime($selected_month . '-01'));
    
    $weekdays = array();
    for ($day = 1; $day <= $days_in_month; $day++) {
        $date = sprintf('%s-%02d-%02d', $year, $month, $day);
        $day_of_week = date('N', strtotime($date)); // 1=Monday, 7=Sunday
        
        // Only include weekdays (Monday=1 to Friday=5)
        if ($day_of_week >= 1 && $day_of_week <= 5) {
            $weekdays[] = $date;
        }
    }
    
    global $wpdb;
    
    // Get all attendance records for selected month
    $attendance_data = $wpdb->get_results($wpdb->prepare("
        SELECT student_id, date, status, notes 
        FROM {$wpdb->prefix}tfsp_attendance 
        WHERE date LIKE %s
        ORDER BY date
    ", $selected_month . '%'));
    
    // Organize attendance by student and date
    $attendance_by_student = array();
    foreach ($attendance_data as $record) {
        $attendance_by_student[$record->student_id][$record->date] = $record;
    }
    ?>
    
    <div class="section">
        <h2>📊 Calendar-Based Attendance Tracking</h2>
        <p>Complete monthly attendance management with full calendar view</p>
        
        <div class="controls">
            <div class="month-navigation">
                <button class="btn" onclick="changeMonth('<?php echo date('Y-m', strtotime($selected_month . ' -1 month')); ?>')">
                    ← Previous Month
                </button>
                <select id="month-selector" onchange="changeMonth(this.value)">
                    <?php 
                    for ($i = -6; $i <= 6; $i++) {
                        $month_value = date('Y-m', strtotime("$i months"));
                        $month_name = date('F Y', strtotime("$i months"));
                        $selected = ($month_value === $selected_month) ? 'selected' : '';
                        echo "<option value='$month_value' $selected>$month_name</option>";
                    }
                    ?>
                </select>
                <button class="btn" onclick="changeMonth('<?php echo date('Y-m', strtotime($selected_month . ' +1 month')); ?>')">
                    Next Month →
                </button>
            </div>
            
            <div class="action-buttons">
                <button class="btn" onclick="exportAttendance()">📥 Export Report</button>
                <button class="btn" onclick="calculateAllPercentages()">🔄 Recalculate</button>
            </div>
        </div>
        
        <div class="attendance-calendar-view">
            <div class="calendar-header">
                <h3><?php echo date('F Y', strtotime($selected_month . '-01')); ?> - <?php echo count($weekdays); ?> School Days</h3>
            </div>
            
            <div class="attendance-container">
                <div class="students-column">
                    <div class="student-header-cell">Student</div>
                    <?php foreach ($students as $student): ?>
                        <div class="student-cell">
                            <div class="student-name"><?php echo $student->display_name; ?></div>
                            <div class="student-email"><?php echo $student->user_email; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="calendar-grid">
                    <!-- Calendar Header -->
                    <div class="calendar-dates-header">
                        <?php foreach ($weekdays as $date): ?>
                            <div class="date-cell">
                                <div class="day-name"><?php echo date('D', strtotime($date)); ?></div>
                                <div class="day-number"><?php echo date('j', strtotime($date)); ?></div>
                            </div>
                        <?php endforeach; ?>
                        <div class="percentage-cell">%</div>
                    </div>
                    
                    <!-- Student Attendance Rows -->
                    <?php foreach ($students as $student): ?>
                        <div class="student-attendance-row" data-student-id="<?php echo $student->ID; ?>">
                            <?php foreach ($weekdays as $date): ?>
                                <?php 
                                $current_status = isset($attendance_by_student[$student->ID][$date]) 
                                    ? $attendance_by_student[$student->ID][$date]->status 
                                    : '';
                                ?>
                                <div class="attendance-day-cell">
                                    <select class="attendance-status" data-date="<?php echo $date; ?>" 
                                            onchange="updateAttendanceFixed(<?php echo $student->ID; ?>, '<?php echo $date; ?>', this.value)">
                                        <option value="">-</option>
                                        <option value="present" <?php selected($current_status, 'present'); ?>>Present</option>
                                        <option value="excused" <?php selected($current_status, 'excused'); ?>>Excused</option>
                                        <option value="absent" <?php selected($current_status, 'absent'); ?>>Absent</option>
                                        <option value="postponed" <?php selected($current_status, 'postponed'); ?>>Postponed</option>
                                    </select>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="percentage-display-cell">
                                <?php 
                                $student_records = isset($attendance_by_student[$student->ID]) ? $attendance_by_student[$student->ID] : array();
                                $total = count(array_filter($student_records, function($r) { return !empty($r->status); }));
                                $present = count(array_filter($student_records, function($r) { return $r->status === 'present'; }));
                                $percentage = $total > 0 ? round(($present / $total) * 100) : 0;
                                ?>
                                <div class="percentage-display <?php echo $percentage >= 80 ? 'good' : ($percentage >= 60 ? 'warning' : 'poor'); ?>">
                                    <?php echo $percentage; ?>%
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .attendance-calendar-view {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        margin-top: 20px;
    }
    
    .calendar-header {
        background: linear-gradient(135deg, #2d5016 0%, #3f5340 100%);
        color: white;
        padding: 20px;
        text-align: center;
    }
    
    .calendar-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }
    
    .attendance-container {
        display: flex;
        overflow-x: auto;
        max-width: 100%;
    }
    
    .students-column {
        min-width: 250px;
        background: #f8f9fa;
        border-right: 2px solid #e8f5e8;
        flex-shrink: 0;
    }
    
    .student-header-cell {
        background: linear-gradient(135deg, #2d5016 0%, #3f5340 100%);
        color: white;
        padding: 15px 20px;
        font-weight: 600;
        font-size: 14px;
        text-align: center;
    }
    
    .student-cell {
        padding: 15px 20px;
        border-bottom: 1px solid #e0e0e0;
        min-height: 60px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .student-name {
        font-weight: 600;
        color: #2d5016;
        font-size: 14px;
        margin-bottom: 2px;
    }
    
    .student-email {
        font-size: 12px;
        color: #666;
    }
    
    .calendar-grid {
        flex: 1;
        min-width: 0;
    }
    
    .calendar-dates-header {
        display: flex;
        background: linear-gradient(135deg, #2d5016 0%, #3f5340 100%);
        color: white;
    }
    
    .date-cell {
        min-width: 70px;
        padding: 10px 5px;
        text-align: center;
        border-right: 1px solid rgba(255,255,255,0.1);
        flex-shrink: 0;
    }
    
    .day-name {
        font-size: 10px;
        opacity: 0.8;
        margin-bottom: 2px;
    }
    
    .day-number {
        font-size: 14px;
        font-weight: 700;
    }
    
    .percentage-cell {
        min-width: 80px;
        padding: 15px 10px;
        text-align: center;
        font-weight: 600;
        font-size: 14px;
    }
    
    .student-attendance-row {
        display: flex;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s;
    }
    
    .student-attendance-row:hover {
        background: linear-gradient(135deg, #f8fff8 0%, #f0f8f0 100%);
    }
    
    .attendance-day-cell {
        min-width: 70px;
        padding: 8px 5px;
        border-right: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .attendance-status {
        width: 100%;
        max-width: 60px;
        padding: 4px 2px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 10px;
        background: white;
        cursor: pointer;
    }
    
    .attendance-status:focus {
        outline: none;
        border-color: #27AE60;
        box-shadow: 0 0 0 2px rgba(39, 174, 96, 0.1);
    }
    
    .percentage-display-cell {
        min-width: 80px;
        padding: 15px 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .percentage-display {
        font-size: 16px;
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 20px;
        min-width: 50px;
        text-align: center;
    }
    
    .percentage-display.good { 
        color: #27AE60; 
        background: rgba(39, 174, 96, 0.1);
    }
    .percentage-display.warning { 
        color: #F39C12; 
        background: rgba(243, 156, 18, 0.1);
    }
    .percentage-display.poor { 
        color: #E74C3C; 
        background: rgba(231, 76, 60, 0.1);
    }
    
    .controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding: 20px;
        background: linear-gradient(135deg, #f8fff8 0%, #f0f8f0 100%);
        border-radius: 12px;
        border: 1px solid #e8f5e8;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .month-navigation {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .btn {
        background: linear-gradient(135deg, #27AE60 0%, #2ECC71 100%);
        color: white;
        border: none;
        padding: 10px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.3s ease;
        white-space: nowrap;
    }
    
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
    }
    
    #month-selector {
        padding: 10px 15px;
        border: 2px solid #e8f5e8;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        background: white;
        cursor: pointer;
    }
    
    @media (max-width: 768px) {
        .controls {
            flex-direction: column;
            align-items: stretch;
        }
        
        .month-navigation {
            justify-content: center;
        }
        
        .action-buttons {
            justify-content: center;
        }
        
        .students-column {
            min-width: 200px;
        }
        
        .date-cell, .attendance-day-cell {
            min-width: 60px;
        }
        
        .attendance-status {
            max-width: 50px;
            font-size: 9px;
        }
    }
    </style>
    
    <script>
    const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    function updateAttendanceFixed(studentId, date, status) {
        jQuery.post(ajaxurl, {
            action: 'update_attendance',
            student_id: studentId,
            date: date,
            status: status,
            nonce: '<?php echo wp_create_nonce('attendance_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                calculateStudentPercentage(studentId);
                showCustomAlert('Attendance updated successfully', 'success');
            } else {
                showCustomAlert('Error: ' + response.data, 'error');
            }
        }).fail(function() {
            showCustomAlert('Network error - please try again', 'error');
        });
    }
    
    function calculateStudentPercentage(studentId) {
        const row = document.querySelector(`[data-student-id="${studentId}"]`);
        const selects = row.querySelectorAll('.attendance-status');
        
        let total = 0;
        let present = 0;
        
        selects.forEach(select => {
            if (select.value) {
                total++;
                if (select.value === 'present') {
                    present++;
                }
            }
        });
        
        const percentage = total > 0 ? Math.round((present / total) * 100) : 0;
        const percentageElement = row.querySelector('.percentage-display');
        
        percentageElement.textContent = percentage + '%';
        percentageElement.className = 'percentage-display ' + 
            (percentage >= 80 ? 'good' : (percentage >= 60 ? 'warning' : 'poor'));
    }
    
    function calculateAllPercentages() {
        document.querySelectorAll('[data-student-id]').forEach(row => {
            const studentId = row.dataset.studentId;
            calculateStudentPercentage(studentId);
        });
        showCustomAlert('All percentages recalculated', 'success');
    }
    
    function changeMonth(month) {
        window.location.href = '?view=attendance&month=' + month;
    }
    
    function exportAttendance() {
        const month = '<?php echo $selected_month; ?>';
        window.location.href = ajaxurl + '?action=export_attendance&month=' + month + '&nonce=<?php echo wp_create_nonce('export_nonce'); ?>';
    }
    
    function showCustomAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `custom-alert ${type}`;
        alertDiv.innerHTML = `
            <div class="alert-content">
                <span class="alert-message">${message}</span>
                <button class="alert-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 3000);
    }
    </script>
    
    <style>
    .custom-alert {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
    }
    .custom-alert.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .custom-alert.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .alert-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .alert-close {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        margin-left: 15px;
    }
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    </style>
    <?php
}
?>
