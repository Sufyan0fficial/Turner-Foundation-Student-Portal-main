<?php
// Enhanced Attendance Grid with Mobile Support
if (!defined('ABSPATH')) {
    exit;
}

// Handle attendance submission
if (isset($_POST['save_attendance'])) {
    global $wpdb;
    
    $session_date = sanitize_text_field($_POST['session_date']);
    $attendance_data = $_POST['attendance'] ?? array();
    
    $saved_count = 0;
    foreach ($attendance_data as $student_id => $status) {
        $student_id = intval($student_id);
        $status = sanitize_text_field($status);
        
        if ($status && $status !== 'not_marked') {
            // Check if record exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}tfsp_attendance_records 
                 WHERE student_id = %d AND session_date = %s",
                $student_id, $session_date
            ));
            
            if ($existing) {
                // Update existing record
                $result = $wpdb->update(
                    $wpdb->prefix . 'tfsp_attendance_records',
                    array('status' => $status),
                    array('id' => $existing),
                    array('%s'),
                    array('%d')
                );
            } else {
                // Insert new record
                $result = $wpdb->insert(
                    $wpdb->prefix . 'tfsp_attendance_records',
                    array(
                        'student_id' => $student_id,
                        'session_date' => $session_date,
                        'status' => $status,
                        'created_at' => current_time('mysql')
                    ),
                    array('%d', '%s', '%s', '%s')
                );
            }
            
            if ($result !== false) {
                $saved_count++;
            }
        }
    }
    
    echo '<div class="notice notice-success"><p>✅ Attendance saved successfully! (' . $saved_count . ' records updated)</p></div>';
}

// Get students and current attendance
$students = get_users(array('role' => 'subscriber'));
$current_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');

global $wpdb;
$existing_attendance = array();
foreach ($students as $student) {
    $status = $wpdb->get_var($wpdb->prepare(
        "SELECT status FROM {$wpdb->prefix}tfsp_attendance_records 
         WHERE student_id = %d AND session_date = %s",
        $student->ID, $current_date
    ));
    $existing_attendance[$student->ID] = $status ?: 'not_marked';
}

// Calculate stats
$present_count = array_count_values($existing_attendance)['present'] ?? 0;
$absent_count = array_count_values($existing_attendance)['absent'] ?? 0;
$tardy_count = array_count_values($existing_attendance)['tardy'] ?? 0;
$excused_count = array_count_values($existing_attendance)['excused'] ?? 0;
$total_students = count($students);
?>

<div class="section">
    <h2>📅 Attendance Tracking</h2>
    <p>Track student attendance with mobile-friendly interface</p>
    
    <!-- Date Selector -->
    <div class="attendance-controls" style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <form method="GET" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                <input type="hidden" name="view" value="attendance">
                <label style="font-weight: 600;">Select Date:</label>
                <input type="date" name="date" value="<?php echo $current_date; ?>" 
                       style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                       onchange="this.form.submit()">
                <div style="font-size: 16px; color: #666;">
                    <?php echo date('l, F j, Y', strtotime($current_date)); ?>
                </div>
            </form>
            
            <div class="export-buttons" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="<?php echo plugin_dir_url(dirname(dirname(__FILE__))); ?>export-attendance.php?export=day&date=<?php echo $current_date; ?>" 
                   style="background: #2196F3; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px;">
                   📊 Export Today
                </a>
                <a href="<?php echo plugin_dir_url(dirname(dirname(__FILE__))); ?>export-attendance.php?export=week" 
                   style="background: #FF9800; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px;">
                   📅 Export Week
                </a>
                <a href="<?php echo plugin_dir_url(dirname(dirname(__FILE__))); ?>export-attendance.php?export=month" 
                   style="background: #9C27B0; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px;">
                   📆 Export Month
                </a>
                <a href="<?php echo plugin_dir_url(dirname(dirname(__FILE__))); ?>export-attendance.php?export=year" 
                   style="background: #4CAF50; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 12px;">
                   📈 Export Year
                </a>
            </div>
        </div>
    </div>
    
    <!-- Stats Dashboard -->
    <div class="attendance-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; margin: 20px 0;">
        <div class="stat-card" style="background: #4CAF50; color: white; padding: 15px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: bold;" id="present-count"><?php echo $present_count; ?></div>
            <div style="font-size: 12px; opacity: 0.9;">Present</div>
        </div>
        <div class="stat-card" style="background: #f44336; color: white; padding: 15px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: bold;" id="absent-count"><?php echo $absent_count; ?></div>
            <div style="font-size: 12px; opacity: 0.9;">Absent</div>
        </div>
        <div class="stat-card" style="background: #FF9800; color: white; padding: 15px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: bold;" id="tardy-count"><?php echo $tardy_count; ?></div>
            <div style="font-size: 12px; opacity: 0.9;">Tardy</div>
        </div>
        <div class="stat-card" style="background: #2196F3; color: white; padding: 15px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: bold;" id="excused-count"><?php echo $excused_count; ?></div>
            <div style="font-size: 12px; opacity: 0.9;">Excused</div>
        </div>
        <div class="stat-card" style="background: #9E9E9E; color: white; padding: 15px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: bold;"><?php echo $total_students; ?></div>
            <div style="font-size: 12px; opacity: 0.9;">Total</div>
        </div>
    </div>
    
    <!-- Attendance Form -->
    <form method="POST" id="attendanceForm">
        <input type="hidden" name="session_date" value="<?php echo $current_date; ?>">
        
        <!-- Desktop View -->
        <div class="desktop-attendance" style="display: block;">
            <div class="table-responsive">
                <table class="data-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Student ID</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td data-label="Student Name"><strong><?php echo esc_html($student->display_name); ?></strong></td>
                                <td data-label="Student ID">#<?php echo $student->ID; ?></td>
                                <td data-label="Status">
                                    <span class="status-badge status-<?php echo $existing_attendance[$student->ID]; ?>" 
                                          id="status-display-<?php echo $student->ID; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $existing_attendance[$student->ID])); ?>
                                    </span>
                                </td>
                                <td data-label="Actions">
                                    <div class="attendance-buttons" style="display: flex; gap: 5px;">
                                        <button type="button" class="attendance-btn present <?php echo $existing_attendance[$student->ID] === 'present' ? 'active' : ''; ?>" 
                                                onclick="setAttendance(<?php echo $student->ID; ?>, 'present', this)">P</button>
                                        <button type="button" class="attendance-btn absent <?php echo $existing_attendance[$student->ID] === 'absent' ? 'active' : ''; ?>" 
                                                onclick="setAttendance(<?php echo $student->ID; ?>, 'absent', this)">A</button>
                                        <button type="button" class="attendance-btn tardy <?php echo $existing_attendance[$student->ID] === 'tardy' ? 'active' : ''; ?>" 
                                                onclick="setAttendance(<?php echo $student->ID; ?>, 'tardy', this)">T</button>
                                        <button type="button" class="attendance-btn excused <?php echo $existing_attendance[$student->ID] === 'excused' ? 'active' : ''; ?>" 
                                                onclick="setAttendance(<?php echo $student->ID; ?>, 'excused', this)">E</button>
                                    </div>
                                    <input type="hidden" name="attendance[<?php echo $student->ID; ?>]" 
                                           value="<?php echo $existing_attendance[$student->ID]; ?>" 
                                           id="attendance_<?php echo $student->ID; ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Mobile View -->
        <div class="mobile-attendance" style="display: none;">
            <?php foreach ($students as $student): ?>
                <div class="mobile-student-card" style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; font-size: 16px;"><?php echo esc_html($student->display_name); ?></div>
                            <div style="font-size: 12px; color: #666;">#<?php echo $student->ID; ?></div>
                        </div>
                        <div class="attendance-buttons" style="display: flex; gap: 8px;">
                            <button type="button" class="attendance-btn-mobile present <?php echo $existing_attendance[$student->ID] === 'present' ? 'active' : ''; ?>" 
                                    onclick="setAttendance(<?php echo $student->ID; ?>, 'present', this)">P</button>
                            <button type="button" class="attendance-btn-mobile absent <?php echo $existing_attendance[$student->ID] === 'absent' ? 'active' : ''; ?>" 
                                    onclick="setAttendance(<?php echo $student->ID; ?>, 'absent', this)">A</button>
                            <button type="button" class="attendance-btn-mobile tardy <?php echo $existing_attendance[$student->ID] === 'tardy' ? 'active' : ''; ?>" 
                                    onclick="setAttendance(<?php echo $student->ID; ?>, 'tardy', this)">T</button>
                            <button type="button" class="attendance-btn-mobile excused <?php echo $existing_attendance[$student->ID] === 'excused' ? 'active' : ''; ?>" 
                                    onclick="setAttendance(<?php echo $student->ID; ?>, 'excused', this)">E</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Save Button -->
        <div style="margin-top: 20px; text-align: center;">
            <button type="submit" name="save_attendance" 
                    style="background: #8BC34A; color: white; padding: 15px 30px; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer;">
                💾 Save Attendance
            </button>
        </div>
    </form>
</div>

<style>
.attendance-btn, .attendance-btn-mobile {
    width: 40px;
    height: 40px;
    border: 2px solid #ddd;
    background: white;
    color: #333;
    border-radius: 6px;
    font-weight: bold;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.attendance-btn:hover, .attendance-btn-mobile:hover {
    transform: scale(1.05);
}

.attendance-btn.present.active, .attendance-btn-mobile.present.active {
    background: #4CAF50;
    color: white;
    border-color: #4CAF50;
}

.attendance-btn.absent.active, .attendance-btn-mobile.absent.active {
    background: #f44336;
    color: white;
    border-color: #f44336;
}

.attendance-btn.tardy.active, .attendance-btn-mobile.tardy.active {
    background: #FF9800;
    color: white;
    border-color: #FF9800;
}

.attendance-btn.excused.active, .attendance-btn-mobile.excused.active {
    background: #2196F3;
    color: white;
    border-color: #2196F3;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.status-present { background: #d4edda; color: #155724; }
.status-absent { background: #f8d7da; color: #721c24; }
.status-tardy { background: #fff3cd; color: #856404; }
.status-excused { background: #d1ecf1; color: #0c5460; }
.status-not_marked { background: #e2e3e5; color: #495057; }

/* Responsive Design */
@media (max-width: 768px) {
    .desktop-attendance { display: none !important; }
    .mobile-attendance { display: block !important; }
    
    .attendance-controls {
        padding: 15px;
    }
    
    .attendance-controls > div {
        flex-direction: column;
        align-items: center !important;
        text-align: center;
    }
    
    .attendance-controls form {
        flex-direction: column;
        align-items: center;
        gap: 10px;
        width: 100%;
    }
    
    .attendance-controls input[type="date"],
    .attendance-controls button {
        width: 100%;
        max-width: 300px;
    }
    
    .export-buttons {
        flex-direction: column !important;
        width: 100%;
        max-width: 300px;
    }
    
    .export-buttons a {
        width: 100%;
        text-align: center;
    }
    
    .attendance-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .stat-card {
        padding: 12px;
    }
    
    .stat-card h4 {
        font-size: 11px;
    }
    
    .stat-card .number {
        font-size: 24px;
    }
    
    .attendance-btn-mobile {
        width: 45px;
        height: 45px;
        font-size: 16px;
    }
    
    .mobile-student-card {
        padding: 12px;
    }
}

@media (max-width: 480px) {
    .attendance-stats {
        grid-template-columns: 1fr;
    }
    
    .stat-card .number {
        font-size: 20px;
    }
    
    .attendance-btn-mobile {
        width: 40px;
        height: 40px;
        font-size: 14px;
    }
}
</style>

<script>
function setAttendance(studentId, status, button) {
    // Remove active class from all buttons for this student (both desktop and mobile)
    document.querySelectorAll(`[onclick*="${studentId}"]`).forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Add active class to clicked button and its counterpart
    document.querySelectorAll(`[onclick*="${studentId}"][onclick*="${status}"]`).forEach(btn => {
        btn.classList.add('active');
    });
    
    // Update hidden input
    document.getElementById('attendance_' + studentId).value = status;
    
    // Update status display in desktop view
    const statusDisplay = document.getElementById('status-display-' + studentId);
    if (statusDisplay) {
        statusDisplay.textContent = status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
        statusDisplay.className = 'status-badge status-' + status;
    }
    
    // Update stats
    updateStats();
}

function updateStats() {
    const inputs = document.querySelectorAll('input[name^="attendance"]');
    let present = 0, absent = 0, tardy = 0, excused = 0;
    
    inputs.forEach(input => {
        if (input.value === 'present') present++;
        else if (input.value === 'absent') absent++;
        else if (input.value === 'tardy') tardy++;
        else if (input.value === 'excused') excused++;
    });
    
    document.getElementById('present-count').textContent = present;
    document.getElementById('absent-count').textContent = absent;
    document.getElementById('tardy-count').textContent = tardy;
    document.getElementById('excused-count').textContent = excused;
}
</script>
