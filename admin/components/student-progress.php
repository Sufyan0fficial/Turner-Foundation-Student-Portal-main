<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_student_progress() {
    $students = get_users(array('role' => 'subscriber'));
    global $wpdb;
    ?>
    <div class="section">
        <h2>👥 Student Progress Overview</h2>
        <p>Monitor and manage individual student progress</p>
        
        <div class="student-grid">
            <?php foreach ($students as $student): ?>
                <?php
                // Get roadmap progress
                $total_steps = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d",
                    $student->ID
                ));
                $completed_steps = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d AND status = 'completed'",
                    $student->ID
                ));
                $roadmap_progress = $total_steps > 0 ? round(($completed_steps / $total_steps) * 100) : 0;
                
                // Get attendance for last 7 days
                $attendance_records = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_attendance_records 
                     WHERE student_id = %d AND session_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
                    $student->ID
                ));
                $present_records = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_attendance_records 
                     WHERE student_id = %d AND session_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status = 'present'",
                    $student->ID
                ));
                $attendance_percentage = $attendance_records > 0 ? round(($present_records / $attendance_records) * 100) : 0;
                
                // Get documents count
                $documents_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_documents WHERE user_id = %d",
                    $student->ID
                ));
                ?>
                <div class="student-card">
                    <div class="student-info">
                        <div class="student-avatar">👤</div>
                        <div class="student-details">
                            <h4><?php echo esc_html($student->display_name); ?></h4>
                            <p><?php echo esc_html($student->user_email); ?></p>
                            <small>Registered: <?php echo date('M j, Y', strtotime($student->user_registered)); ?></small>
                        </div>
                    </div>
                    
                    <div class="student-stats">
                        <div class="stat">
                            <div class="stat-value"><?php echo $roadmap_progress; ?>%</div>
                            <div class="stat-label">Roadmap Progress</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value"><?php echo $attendance_percentage; ?>%</div>
                            <div class="stat-label">Attendance</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value"><?php echo $documents_count; ?></div>
                            <div class="stat-label">Documents</div>
                        </div>
                    </div>
                    
                    <div class="student-actions">
                        <a href="?view=students&student_id=<?php echo $student->ID; ?>" class="btn btn-small">📊 View Details</a>
                        <a href="?view=messages&student_id=<?php echo $student->ID; ?>" class="btn btn-small">💬 Message</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
?>
