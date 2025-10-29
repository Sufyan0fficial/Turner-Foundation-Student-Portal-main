<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_weekly_attendance_system() {
    $students = get_users(array('role' => 'subscriber'));
    
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
    
    global $wpdb;
    
    // Get attendance for current week
    $attendance_data = $wpdb->get_results($wpdb->prepare("
        SELECT student_id, date, status, notes 
        FROM {$wpdb->prefix}tfsp_attendance 
        WHERE date >= %s AND date <= %s
        ORDER BY date
    ", $week_start, $week_dates[4]['date']));
    
    // Organize by student
    $attendance_by_student = array();
    foreach ($attendance_data as $record) {
        $attendance_by_student[$record->student_id][$record->date] = $record;
    }
    
    // Week navigation
    $prev_week = date('Y-m-d', strtotime('-1 week', strtotime($week_start)));
    $next_week = date('Y-m-d', strtotime('+1 week', strtotime($week_start)));
    $current_week = date('Y-m-d', strtotime('monday this week'));
    
    $week_display = date('M j', strtotime($week_start)) . ' - ' . date('M j, Y', strtotime($week_dates[4]['date']));
    ?>
    
    <div class="weekly-attendance-system">
        <!-- Header -->
        <div class="attendance-header">
            <div class="header-content">
                <h1>📅 Weekly Attendance System</h1>
                <p>Modern week-based attendance tracking with automatic progression</p>
            </div>
            <div class="week-info">
                <div class="current-week">
                    <span class="week-label">Current Week</span>
                    <span class="week-dates"><?php echo $week_display; ?></span>
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
                
                <div class="week-selector">
                    <button class="current-week-btn <?php echo ($selected_week === $current_week) ? 'active' : ''; ?>" 
                            onclick="changeWeek('<?php echo $current_week; ?>')">
                        <i class="icon">📅</i>
                        <span>Current Week</span>
                    </button>
                </div>
                
                <button class="nav-btn next" onclick="changeWeek('<?php echo $next_week; ?>')">
                    <span>Next Week</span>
                    <i class="icon">→</i>
                </button>
            </div>
            
            <div class="quick-actions">
                <button class="action-btn" onclick="markAllPresent()">
                    <i class="icon">✓</i>
                    <span>Mark All Present</span>
                </button>
                <button class="action-btn" onclick="exportWeek()">
                    <i class="icon">📊</i>
                    <span>Export Week</span>
                </button>
            </div>
        </div>
        
        <!-- Attendance Grid -->
        <div class="attendance-grid">
            <!-- Days Header -->
            <div class="days-header">
                <div class="student-column-header">
                    <h3>Students</h3>
                    <span class="student-count"><?php echo count($students); ?> total</span>
                </div>
                <?php foreach ($week_dates as $day): ?>
                    <div class="day-header">
                        <div class="day-name"><?php echo $day['day_short']; ?></div>
                        <div class="day-date">
                            <span class="day-number"><?php echo $day['day_number']; ?></span>
                            <span class="month"><?php echo $day['month']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="stats-header">
                    <div class="stats-label">Week Stats</div>
                </div>
            </div>
            
            <!-- Student Rows -->
            <?php foreach ($students as $student): ?>
                <div class="student-row" data-student-id="<?php echo $student->ID; ?>">
                    <div class="student-info">
                        <div class="student-avatar">
                            <span><?php echo strtoupper(substr($student->display_name, 0, 2)); ?></span>
                        </div>
                        <div class="student-details">
                            <div class="student-name"><?php echo $student->display_name; ?></div>
                            <div class="student-email"><?php echo $student->user_email; ?></div>
                        </div>
                    </div>
                    
                    <?php foreach ($week_dates as $day): ?>
                        <?php 
                        $current_status = isset($attendance_by_student[$student->ID][$day['date']]) 
                            ? $attendance_by_student[$student->ID][$day['date']]->status 
                            : '';
                        ?>
                        <div class="attendance-cell" data-date="<?php echo $day['date']; ?>">
                            <div class="attendance-selector">
                                <button class="status-btn present <?php echo ($current_status === 'present') ? 'active' : ''; ?>" 
                                        onclick="markAttendance(<?php echo $student->ID; ?>, '<?php echo $day['date']; ?>', 'present')"
                                        title="Present">
                                    <i class="icon">✓</i>
                                </button>
                                <button class="status-btn absent <?php echo ($current_status === 'absent') ? 'active' : ''; ?>" 
                                        onclick="markAttendance(<?php echo $student->ID; ?>, '<?php echo $day['date']; ?>', 'absent')"
                                        title="Absent">
                                    <i class="icon">✗</i>
                                </button>
                                <button class="status-btn excused <?php echo ($current_status === 'excused') ? 'active' : ''; ?>" 
                                        onclick="markAttendance(<?php echo $student->ID; ?>, '<?php echo $day['date']; ?>', 'excused')"
                                        title="Excused">
                                    <i class="icon">E</i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="week-stats">
                        <?php 
                        $week_records = array_filter($attendance_by_student[$student->ID] ?? array(), function($record) use ($week_dates) {
                            return in_array($record->date, array_column($week_dates, 'date'));
                        });
                        $present_count = count(array_filter($week_records, function($r) { return $r->status === 'present'; }));
                        $total_marked = count($week_records);
                        $percentage = $total_marked > 0 ? round(($present_count / $total_marked) * 100) : 0;
                        ?>
                        <div class="attendance-percentage <?php echo $percentage >= 80 ? 'excellent' : ($percentage >= 60 ? 'good' : 'poor'); ?>">
                            <?php echo $percentage; ?>%
                        </div>
                        <div class="attendance-fraction">
                            <?php echo $present_count; ?>/<?php echo count($week_dates); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Week Summary -->
        <div class="week-summary">
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="card-icon">👥</div>
                    <div class="card-content">
                        <div class="card-number"><?php echo count($students); ?></div>
                        <div class="card-label">Students</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="card-icon">📅</div>
                    <div class="card-content">
                        <div class="card-number">5</div>
                        <div class="card-label">School Days</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="card-icon">✓</div>
                    <div class="card-content">
                        <div class="card-number" id="total-present">0</div>
                        <div class="card-label">Present</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="card-icon">✗</div>
                    <div class="card-content">
                        <div class="card-number" id="total-absent">0</div>
                        <div class="card-label">Absent</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    function markAttendance(studentId, date, status) {
        const cell = document.querySelector(`[data-student-id="${studentId}"] [data-date="${date}"]`);
        const buttons = cell.querySelectorAll('.status-btn');
        
        // Visual feedback
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
                // Update button states
                buttons.forEach(btn => btn.classList.remove('active'));
                cell.querySelector(`.status-btn.${status}`).classList.add('active');
                
                // Update student stats
                updateStudentStats(studentId);
                updateWeekSummary();
                
                showNotification('Attendance updated', 'success');
            } else {
                showNotification('Error updating attendance', 'error');
            }
        });
    }
    
    function updateStudentStats(studentId) {
        const row = document.querySelector(`[data-student-id="${studentId}"]`);
        const activeBtns = row.querySelectorAll('.status-btn.active');
        const presentBtns = row.querySelectorAll('.status-btn.present.active');
        
        const total = activeBtns.length;
        const present = presentBtns.length;
        const percentage = total > 0 ? Math.round((present / total) * 100) : 0;
        
        const percentageEl = row.querySelector('.attendance-percentage');
        const fractionEl = row.querySelector('.attendance-fraction');
        
        percentageEl.textContent = percentage + '%';
        percentageEl.className = 'attendance-percentage ' + 
            (percentage >= 80 ? 'excellent' : (percentage >= 60 ? 'good' : 'poor'));
        fractionEl.textContent = present + '/5';
    }
    
    function updateWeekSummary() {
        const presentCount = document.querySelectorAll('.status-btn.present.active').length;
        const absentCount = document.querySelectorAll('.status-btn.absent.active').length;
        
        document.getElementById('total-present').textContent = presentCount;
        document.getElementById('total-absent').textContent = absentCount;
    }
    
    function changeWeek(week) {
        window.location.href = '?view=attendance&week=' + week;
    }
    
    function markAllPresent() {
        if (confirm('Mark all students present for this week?')) {
            document.querySelectorAll('.status-btn.present:not(.active)').forEach(btn => {
                btn.click();
            });
        }
    }
    
    function exportWeek() {
        const week = '<?php echo $week_start; ?>';
        window.location.href = ajaxurl + '?action=export_week_attendance&week=' + week + '&nonce=<?php echo wp_create_nonce('export_nonce'); ?>';
    }
    
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()">×</button>
        `;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        updateWeekSummary();
    });
    </script>
    <?php
}
?>
