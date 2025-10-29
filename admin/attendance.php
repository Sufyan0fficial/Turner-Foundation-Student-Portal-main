<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$students_table = $wpdb->prefix . 'tfsp_students';
$attendance_table = $wpdb->prefix . 'tfsp_attendance';

// Create attendance table if not exists
$wpdb->query("CREATE TABLE IF NOT EXISTS $attendance_table (
    id int(11) NOT NULL AUTO_INCREMENT,
    student_id int(11) NOT NULL,
    session_date date NOT NULL,
    status enum('present','excused','absent','postponed') DEFAULT 'absent',
    notes text,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_attendance (student_id, session_date),
    INDEX idx_student_date (student_id, session_date),
    INDEX idx_session_date (session_date),
    FOREIGN KEY (student_id) REFERENCES {$wpdb->prefix}tfsp_students(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Get all students
$students = $wpdb->get_results("SELECT * FROM $students_table ORDER BY first_name, last_name");

// Generate last 8 weeks of sessions (Mondays)
$sessions = array();
for ($i = 7; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i weeks monday"));
    $sessions[] = $date;
}

// Handle attendance updates
if (isset($_POST['update_attendance']) && wp_verify_nonce($_POST['_wpnonce'], 'update_attendance_nonce')) {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }
    
    foreach ($_POST['attendance'] as $student_id => $session_data) {
        $student_id = intval($student_id);
        if ($student_id <= 0) continue;
        
        foreach ($session_data as $session_date => $status) {
            $session_date = sanitize_text_field($session_date);
            $status = sanitize_text_field($status);
            
            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $session_date)) continue;
            
            // Validate status
            if (!in_array($status, ['present', 'excused', 'absent', 'postponed'])) continue;
            
            $wpdb->replace($attendance_table, array(
                'student_id' => $student_id,
                'session_date' => $session_date,
                'status' => $status
            ), array('%d', '%s', '%s'));
        }
    }
    echo '<div class="notice notice-success"><p>Attendance updated successfully!</p></div>';
}

// Get existing attendance data
$attendance_data = array();
$attendance_records = $wpdb->get_results("SELECT * FROM $attendance_table");
foreach ($attendance_records as $record) {
    $attendance_data[$record->student_id][$record->session_date] = $record->status;
}
?>

<style>
.attendance-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.attendance-table th,
.attendance-table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: center;
}

.attendance-table th {
    background: #8BC34A;
    color: white;
    font-weight: 600;
}

.attendance-table .student-name {
    text-align: left;
    font-weight: 500;
    background: #f9f9f9;
}

.attendance-select {
    width: 100%;
    padding: 4px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.status-present { background-color: #d4edda; }
.status-excused { background-color: #fff3cd; }
.status-absent { background-color: #f8d7da; }
.status-postponed { background-color: #cce7ff; }

.attendance-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-number {
    font-size: 24px;
    font-weight: 700;
    color: #8BC34A;
}
</style>

<div class="wrap">
    <h1>📊 Attendance Tracking</h1>
    <p>Track weekly session attendance for all students</p>

    <?php
    // Calculate attendance statistics
    $total_sessions = count($sessions) * count($students);
    $present_count = 0;
    $excused_count = 0;
    $absent_count = 0;
    $postponed_count = 0;

    foreach ($attendance_data as $student_attendance) {
        foreach ($student_attendance as $status) {
            switch ($status) {
                case 'present': $present_count++; break;
                case 'excused': $excused_count++; break;
                case 'absent': $absent_count++; break;
                case 'postponed': $postponed_count++; break;
            }
        }
    }
    ?>

    <div class="attendance-stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo $present_count; ?></div>
            <div>Present</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $excused_count; ?></div>
            <div>Excused</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $absent_count; ?></div>
            <div>Absent</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $postponed_count; ?></div>
            <div>Postponed</div>
        </div>
    </div>

    <form method="post">
        <?php wp_nonce_field('update_attendance_nonce'); ?>
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <?php foreach ($sessions as $session): ?>
                        <th><?php echo date('M j', strtotime($session)); ?></th>
                    <?php endforeach; ?>
                    <th>Attendance %</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <?php
                    // Calculate attendance percentage
                    $student_sessions = 0;
                    $student_present = 0;
                    foreach ($sessions as $session) {
                        $status = isset($attendance_data[$student->id][$session]) ? $attendance_data[$student->id][$session] : 'absent';
                        $student_sessions++;
                        if ($status === 'present' || $status === 'excused') {
                            $student_present++;
                        }
                    }
                    $percentage = $student_sessions > 0 ? round(($student_present / $student_sessions) * 100) : 0;
                    ?>
                    <tr>
                        <td class="student-name">
                            <?php echo esc_html($student->first_name . ' ' . $student->last_name); ?>
                        </td>
                        <?php foreach ($sessions as $session): ?>
                            <?php
                            $current_status = isset($attendance_data[$student->id][$session]) ? $attendance_data[$student->id][$session] : 'absent';
                            ?>
                            <td class="status-<?php echo $current_status; ?>">
                                <select name="attendance[<?php echo $student->id; ?>][<?php echo $session; ?>]" class="attendance-select">
                                    <option value="absent" <?php selected($current_status, 'absent'); ?>>Absent</option>
                                    <option value="present" <?php selected($current_status, 'present'); ?>>Present</option>
                                    <option value="excused" <?php selected($current_status, 'excused'); ?>>Excused</option>
                                    <option value="postponed" <?php selected($current_status, 'postponed'); ?>>Postponed</option>
                                </select>
                            </td>
                        <?php endforeach; ?>
                        <td><strong><?php echo $percentage; ?>%</strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p>
            <button type="submit" name="update_attendance" class="button button-primary">Update Attendance</button>
            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=tfsp-attendance&export=csv'), 'export_attendance'); ?>" class="button">Export CSV</a>
        </p>
    </form>
</div>

<?php
// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv' && wp_verify_nonce($_GET['_wpnonce'], 'export_attendance')) {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="attendance_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // CSV headers
    $headers = array('Student Name', 'Student ID');
    foreach ($sessions as $session) {
        $headers[] = date('M j, Y', strtotime($session));
    }
    $headers[] = 'Attendance %';
    $headers[] = 'Total Present';
    $headers[] = 'Total Sessions';
    fputcsv($output, $headers);
    
    // CSV data
    foreach ($students as $student) {
        $row = array(
            $student->first_name . ' ' . $student->last_name,
            'STU' . str_pad($student->id, 6, '0', STR_PAD_LEFT)
        );
        $student_present = 0;
        $total_sessions = 0;
        
        foreach ($sessions as $session) {
            $status = isset($attendance_data[$student->id][$session]) ? $attendance_data[$student->id][$session] : 'absent';
            $row[] = ucfirst($status);
            $total_sessions++;
            if ($status === 'present' || $status === 'excused') {
                $student_present++;
            }
        }
        
        $percentage = $total_sessions > 0 ? round(($student_present / $total_sessions) * 100) : 0;
        $row[] = $percentage . '%';
        $row[] = $student_present;
        $row[] = $total_sessions;
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}
?>
