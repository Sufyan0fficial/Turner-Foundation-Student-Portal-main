<?php
if (!defined('ABSPATH')) {
    exit;
}

class TFSP_Attendance_Tracker {
    
    public function __construct() {
        add_action('wp_ajax_update_attendance', array($this, 'update_attendance'));
        add_action('wp_ajax_export_attendance', array($this, 'export_attendance'));
    }
    
    public function render_attendance_tracker() {
        global $wpdb;
        
        // Get all students
        $students = get_users(array('role' => 'subscriber'));
        
        // Get current week dates
        $current_week = $this->get_current_week_dates();
        
        ?>
        <div class="attendance-tracker">
            <h2>📊 Attendance Tracking</h2>
            <div class="attendance-controls">
                <button id="export-attendance" class="button">Export Attendance</button>
                <select id="week-selector">
                    <option value="current">Current Week</option>
                    <option value="previous">Previous Week</option>
                </select>
            </div>
            
            <div class="attendance-grid">
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <?php foreach ($current_week as $date): ?>
                                <th><?php echo date('M j', strtotime($date)); ?></th>
                            <?php endforeach; ?>
                            <th>Attendance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr data-student-id="<?php echo $student->ID; ?>">
                                <td class="student-name"><?php echo $student->display_name; ?></td>
                                <?php foreach ($current_week as $date): ?>
                                    <td class="attendance-cell">
                                        <select class="attendance-status" data-date="<?php echo $date; ?>">
                                            <option value="">-</option>
                                            <option value="present">Present</option>
                                            <option value="excused">Excused</option>
                                            <option value="absent">Didn't Attend</option>
                                            <option value="postponed">Postponed</option>
                                        </select>
                                    </td>
                                <?php endforeach; ?>
                                <td class="attendance-percentage">0%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <style>
        .attendance-tracker {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .attendance-table th,
        .attendance-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .attendance-table th {
            background: #f5f5f5;
            font-weight: 600;
        }
        .student-name {
            text-align: left !important;
            font-weight: 500;
        }
        .attendance-status {
            width: 100%;
            border: none;
            background: transparent;
        }
        .attendance-controls {
            margin-bottom: 15px;
        }
        .attendance-controls button,
        .attendance-controls select {
            margin-right: 10px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Update attendance on change
            $('.attendance-status').on('change', function() {
                var studentId = $(this).closest('tr').data('student-id');
                var date = $(this).data('date');
                var status = $(this).val();
                
                $.post(ajaxurl, {
                    action: 'update_attendance',
                    student_id: studentId,
                    date: date,
                    status: status,
                    nonce: '<?php echo wp_create_nonce('attendance_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        updateAttendancePercentage(studentId);
                    }
                });
            });
            
            // Export attendance
            $('#export-attendance').on('click', function() {
                window.location.href = ajaxurl + '?action=export_attendance&nonce=<?php echo wp_create_nonce('export_nonce'); ?>';
            });
            
            function updateAttendancePercentage(studentId) {
                var row = $('tr[data-student-id="' + studentId + '"]');
                var total = row.find('.attendance-status').length;
                var present = row.find('.attendance-status[value="present"]').length;
                var percentage = total > 0 ? Math.round((present / total) * 100) : 0;
                row.find('.attendance-percentage').text(percentage + '%');
            }
        });
        </script>
        <?php
    }
    
    public function update_attendance() {
        if (!wp_verify_nonce($_POST['nonce'], 'attendance_nonce')) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'tfsp_attendance';
        
        $result = $wpdb->replace($table, array(
            'student_id' => intval($_POST['student_id']),
            'date' => sanitize_text_field($_POST['date']),
            'status' => sanitize_text_field($_POST['status'])
        ));
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
    
    public function export_attendance() {
        if (!wp_verify_nonce($_GET['nonce'], 'export_nonce')) {
            wp_die('Security check failed');
        }
        
        // Generate CSV export
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="attendance-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Student', 'Date', 'Status'));
        
        global $wpdb;
        $results = $wpdb->get_results("
            SELECT u.display_name, a.date, a.status 
            FROM {$wpdb->prefix}tfsp_attendance a 
            JOIN {$wpdb->users} u ON a.student_id = u.ID 
            ORDER BY u.display_name, a.date
        ");
        
        foreach ($results as $row) {
            fputcsv($output, array($row->display_name, $row->date, $row->status));
        }
        
        fclose($output);
        exit;
    }
    
    private function get_current_week_dates() {
        $dates = array();
        $start = strtotime('monday this week');
        
        for ($i = 0; $i < 5; $i++) {
            $dates[] = date('Y-m-d', strtotime("+$i days", $start));
        }
        
        return $dates;
    }
}

new TFSP_Attendance_Tracker();
?>
