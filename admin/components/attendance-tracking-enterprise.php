<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_attendance_tracking_enterprise() {
    $students = get_users(array('role' => 'subscriber'));
    
    // Get selected month or default to current
    $selected_month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : date('Y-m');
    
    // Generate ALL days for the selected month (including weekends)
    $year = date('Y', strtotime($selected_month . '-01'));
    $month = date('m', strtotime($selected_month . '-01'));
    $days_in_month = date('t', strtotime($selected_month . '-01'));
    
    $all_days = array();
    for ($day = 1; $day <= $days_in_month; $day++) {
        $date = sprintf('%s-%02d-%02d', $year, $month, $day);
        $day_of_week = date('N', strtotime($date)); // 1=Monday, 7=Sunday
        
        $all_days[] = array(
            'date' => $date,
            'day_name' => date('D', strtotime($date)),
            'day_number' => $day,
            'is_weekend' => ($day_of_week == 6 || $day_of_week == 7)
        );
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
    
    // Calculate month stats
    $weekdays_count = count(array_filter($all_days, function($day) { return !$day['is_weekend']; }));
    $total_days = count($all_days);
    ?>
    
    <div class="enterprise-attendance-system">
        <div class="attendance-header">
            <div class="header-content">
                <h1>📊 Enterprise Attendance Management</h1>
                <p>Complete monthly attendance tracking with advanced analytics and reporting</p>
            </div>
            
            <div class="month-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $total_days; ?></span>
                    <span class="stat-label">Total Days</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $weekdays_count; ?></span>
                    <span class="stat-label">Weekdays</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($students); ?></span>
                    <span class="stat-label">Students</span>
                </div>
            </div>
        </div>
        
        <div class="controls-panel">
            <div class="navigation-controls">
                <button class="nav-btn prev" onclick="changeMonth('<?php echo date('Y-m', strtotime($selected_month . ' -1 month')); ?>')">
                    <span class="btn-icon">←</span>
                    <span class="btn-text">Previous</span>
                </button>
                
                <div class="month-selector-wrapper">
                    <select id="month-selector" onchange="changeMonth(this.value)">
                        <?php 
                        for ($i = -12; $i <= 12; $i++) {
                            $month_value = date('Y-m', strtotime("$i months"));
                            $month_name = date('F Y', strtotime("$i months"));
                            $selected = ($month_value === $selected_month) ? 'selected' : '';
                            echo "<option value='$month_value' $selected>$month_name</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <button class="nav-btn next" onclick="changeMonth('<?php echo date('Y-m', strtotime($selected_month . ' +1 month')); ?>')">
                    <span class="btn-text">Next</span>
                    <span class="btn-icon">→</span>
                </button>
            </div>
            
            <div class="action-controls">
                <button class="action-btn export" onclick="exportAttendance()">
                    <span class="btn-icon">📥</span>
                    <span class="btn-text">Export Report</span>
                </button>
                <button class="action-btn calculate" onclick="calculateAllPercentages()">
                    <span class="btn-icon">🔄</span>
                    <span class="btn-text">Recalculate</span>
                </button>
                <button class="action-btn analytics" onclick="showAnalytics()">
                    <span class="btn-icon">📈</span>
                    <span class="btn-text">Analytics</span>
                </button>
            </div>
        </div>
        
        <div class="attendance-grid-container">
            <div class="students-sidebar">
                <div class="sidebar-header">
                    <h3>Students</h3>
                    <span class="student-count"><?php echo count($students); ?> total</span>
                </div>
                
                <?php foreach ($students as $student): ?>
                    <div class="student-row" data-student-id="<?php echo $student->ID; ?>">
                        <div class="student-avatar">
                            <span class="avatar-text"><?php echo strtoupper(substr($student->display_name, 0, 2)); ?></span>
                        </div>
                        <div class="student-info">
                            <div class="student-name"><?php echo $student->display_name; ?></div>
                            <div class="student-email"><?php echo $student->user_email; ?></div>
                        </div>
                        <div class="student-percentage">
                            <?php 
                            $student_records = isset($attendance_by_student[$student->ID]) ? $attendance_by_student[$student->ID] : array();
                            $total = count(array_filter($student_records, function($r) { return !empty($r->status); }));
                            $present = count(array_filter($student_records, function($r) { return $r->status === 'present'; }));
                            $percentage = $total > 0 ? round(($present / $total) * 100) : 0;
                            ?>
                            <span class="percentage-badge <?php echo $percentage >= 80 ? 'excellent' : ($percentage >= 60 ? 'good' : 'needs-improvement'); ?>">
                                <?php echo $percentage; ?>%
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="calendar-main">
                <div class="calendar-header-row">
                    <?php foreach ($all_days as $day): ?>
                        <div class="date-header <?php echo $day['is_weekend'] ? 'weekend' : 'weekday'; ?>">
                            <div class="day-name"><?php echo $day['day_name']; ?></div>
                            <div class="day-number"><?php echo $day['day_number']; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php foreach ($students as $student): ?>
                    <div class="attendance-row" data-student-id="<?php echo $student->ID; ?>">
                        <?php foreach ($all_days as $day): ?>
                            <?php 
                            $current_status = isset($attendance_by_student[$student->ID][$day['date']]) 
                                ? $attendance_by_student[$student->ID][$day['date']]->status 
                                : '';
                            ?>
                            <div class="attendance-cell <?php echo $day['is_weekend'] ? 'weekend-cell' : 'weekday-cell'; ?>">
                                <?php if ($day['is_weekend']): ?>
                                    <div class="weekend-indicator">
                                        <span class="weekend-text">Weekend</span>
                                    </div>
                                <?php else: ?>
                                    <select class="attendance-select" 
                                            data-date="<?php echo $day['date']; ?>" 
                                            onchange="updateAttendanceEnterprise(<?php echo $student->ID; ?>, '<?php echo $day['date']; ?>', this.value)">
                                        <option value="">-</option>
                                        <option value="present" <?php selected($current_status, 'present'); ?>>Present</option>
                                        <option value="excused" <?php selected($current_status, 'excused'); ?>>Excused</option>
                                        <option value="absent" <?php selected($current_status, 'absent'); ?>>Absent</option>
                                        <option value="postponed" <?php selected($current_status, 'postponed'); ?>>Postponed</option>
                                    </select>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="attendance-legend">
            <h4>Status Legend</h4>
            <div class="legend-items">
                <div class="legend-item present">
                    <span class="legend-color"></span>
                    <span class="legend-text">Present</span>
                </div>
                <div class="legend-item excused">
                    <span class="legend-color"></span>
                    <span class="legend-text">Excused</span>
                </div>
                <div class="legend-item absent">
                    <span class="legend-color"></span>
                    <span class="legend-text">Absent</span>
                </div>
                <div class="legend-item postponed">
                    <span class="legend-color"></span>
                    <span class="legend-text">Postponed</span>
                </div>
                <div class="legend-item weekend">
                    <span class="legend-color"></span>
                    <span class="legend-text">Weekend</span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    function updateAttendanceEnterprise(studentId, date, status) {
        const cell = document.querySelector(`[data-student-id="${studentId}"] [data-date="${date}"]`).parentElement;
        const select = cell.querySelector('.attendance-select');
        
        cell.classList.add('updating');
        
        jQuery.post(ajaxurl, {
            action: 'update_attendance',
            student_id: studentId,
            date: date,
            status: status,
            nonce: '<?php echo wp_create_nonce('attendance_nonce'); ?>'
        }, function(response) {
            cell.classList.remove('updating');
            if (response.success) {
                cell.classList.add('updated');
                select.setAttribute('value', status); // Update select color
                setTimeout(() => cell.classList.remove('updated'), 1000);
                updateStudentPercentage(studentId);
                showNotification('Attendance updated successfully', 'success');
            } else {
                showNotification('Error: ' + response.data, 'error');
            }
        }).fail(function() {
            cell.classList.remove('updating');
            showNotification('Network error - please try again', 'error');
        });
    }
    
    // Initialize select colors on page load
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.attendance-select').forEach(select => {
            if (select.value) {
                select.setAttribute('value', select.value);
            }
        });
    });
    
    function updateStudentPercentage(studentId) {
        const row = document.querySelector(`[data-student-id="${studentId}"]`);
        const selects = row.querySelectorAll('.attendance-select');
        
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
        const percentageElement = document.querySelector(`.student-row[data-student-id="${studentId}"] .percentage-badge`);
        
        percentageElement.textContent = percentage + '%';
        percentageElement.className = 'percentage-badge ' + 
            (percentage >= 80 ? 'excellent' : (percentage >= 60 ? 'good' : 'needs-improvement'));
    }
    
    function calculateAllPercentages() {
        // First set all to 0%
        document.querySelectorAll('.percentage-badge').forEach(badge => {
            badge.textContent = '0%';
            badge.className = 'percentage-badge needs-improvement';
        });
        
        showNotification('Recalculating percentages...', 'info');
        
        // Then calculate real percentages after delay
        setTimeout(() => {
            document.querySelectorAll('[data-student-id]').forEach(row => {
                const studentId = row.dataset.studentId;
                updateStudentPercentage(studentId);
            });
            showNotification('All percentages recalculated', 'success');
        }, 1000);
    }
    
    function changeMonth(month) {
        window.location.href = '?view=attendance&month=' + month;
    }
    
    function exportAttendance() {
        const month = '<?php echo $selected_month; ?>';
        window.location.href = ajaxurl + '?action=export_attendance&month=' + month + '&nonce=<?php echo wp_create_nonce('export_nonce'); ?>';
    }
    
    function showAnalytics() {
        // Analytics removed
        return;
    }
    
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-message">${message}</span>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 4000);
    }
    </script>
    <?php
}
?>
