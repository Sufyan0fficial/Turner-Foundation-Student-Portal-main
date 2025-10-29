<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_admin_dashboard() {
    $students = get_users(array('role' => 'subscriber'));
    
    global $wpdb;
    
    // Get summary stats
    $total_students = count($students);
    $total_documents = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_documents");
    $total_messages = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_messages WHERE status = 'unread'");
    $total_meetings = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_meetings WHERE status = 'pending'");
    ?>
    
    <div class="section">
        <h2>📊 Dashboard Overview</h2>
        <p>Quick overview of student portal activity and management</p>
        
        <!-- Summary Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $total_students; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📄</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $total_documents; ?></div>
                    <div class="stat-label">Documents Uploaded</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💬</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $total_messages; ?></div>
                    <div class="stat-label">Unread Messages</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $total_meetings; ?></div>
                    <div class="stat-label">Pending Meetings</div>
                </div>
            </div>
        </div>
        
        <!-- Students Overview -->
        <div class="students-overview">
            <h3>👥 Students Overview</h3>
            <p>Click on any student to view detailed information</p>
            
            <div class="students-list">
                <?php if (empty($students)): ?>
                    <div class="empty-state">
                        <p>No students registered yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <?php 
                        // Get quick stats for each student
                        $progress = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d", $student->ID));
                        $documents = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_documents WHERE user_id = %d", $student->ID));
                        $attendance = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tfsp_attendance WHERE student_id = %d", $student->ID));
                        
                        $completed_steps = count(array_filter($progress, function($p) { return $p->status === 'completed'; }));
                        $total_steps = count($progress);
                        $progress_percentage = $total_steps > 0 ? round(($completed_steps / $total_steps) * 100) : 0;
                        
                        $total_sessions = count($attendance);
                        $present_sessions = count(array_filter($attendance, function($a) { return $a->status === 'present'; }));
                        $attendance_percentage = $total_sessions > 0 ? round(($present_sessions / $total_sessions) * 100) : 0;
                        ?>
                        
                        <div class="student-overview-item" onclick="viewStudentDetail(<?php echo $student->ID; ?>)">
                            <div class="student-basic-info">
                                <div class="student-avatar">👤</div>
                                <div class="student-details">
                                    <h4><?php echo $student->display_name; ?></h4>
                                    <p><?php echo $student->user_email; ?></p>
                                    <small>Joined: <?php echo date('M j, Y', strtotime($student->user_registered)); ?></small>
                                </div>
                            </div>
                            
                            <div class="student-quick-stats">
                                <div class="quick-stat">
                                    <span class="stat-value <?php echo $progress_percentage >= 80 ? 'good' : ($progress_percentage >= 60 ? 'warning' : 'poor'); ?>">
                                        <?php echo $progress_percentage; ?>%
                                    </span>
                                    <span class="stat-name">Progress</span>
                                </div>
                                
                                <div class="quick-stat">
                                    <span class="stat-value <?php echo $attendance_percentage >= 80 ? 'good' : ($attendance_percentage >= 60 ? 'warning' : 'poor'); ?>">
                                        <?php echo $attendance_percentage; ?>%
                                    </span>
                                    <span class="stat-name">Attendance</span>
                                </div>
                                
                                <div class="quick-stat">
                                    <span class="stat-value"><?php echo $documents; ?></span>
                                    <span class="stat-name">Documents</span>
                                </div>
                            </div>
                            
                            <div class="student-actions">
                                <button class="btn-view" onclick="event.stopPropagation(); viewStudentDetail(<?php echo $student->ID; ?>)">
                                    View Details →
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        display: flex;
        align-items: center;
        gap: 20px;
        transition: all 0.3s;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #27AE60 0%, #2ECC71 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }
    
    .stat-number {
        font-size: 32px;
        font-weight: 700;
        color: #2d5016;
        margin-bottom: 4px;
    }
    
    .stat-label {
        font-size: 14px;
        color: #666;
        font-weight: 500;
    }
    
    .students-overview {
        background: white;
        padding: 30px;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .students-overview h3 {
        margin: 0 0 8px 0;
        color: #2d5016;
        font-size: 20px;
        font-weight: 700;
    }
    
    .students-overview p {
        margin: 0 0 25px 0;
        color: #666;
    }
    
    .students-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .student-overview-item {
        display: grid;
        grid-template-columns: 2fr 2fr 1fr;
        gap: 20px;
        align-items: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s;
        border: 2px solid transparent;
    }
    
    .student-overview-item:hover {
        background: linear-gradient(135deg, #f8fff8 0%, #f0f8f0 100%);
        border-color: #27AE60;
        transform: translateX(4px);
    }
    
    .student-basic-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .student-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3498DB 0%, #2980B9 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: white;
    }
    
    .student-details h4 {
        margin: 0 0 4px 0;
        color: #2d5016;
        font-size: 16px;
        font-weight: 700;
    }
    
    .student-details p {
        margin: 0 0 2px 0;
        color: #666;
        font-size: 14px;
    }
    
    .student-details small {
        color: #999;
        font-size: 12px;
    }
    
    .student-quick-stats {
        display: flex;
        gap: 20px;
        justify-content: center;
    }
    
    .quick-stat {
        text-align: center;
    }
    
    .stat-value {
        display: block;
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 4px;
    }
    
    .stat-value.good { color: #27AE60; }
    .stat-value.warning { color: #F39C12; }
    .stat-value.poor { color: #E74C3C; }
    
    .stat-name {
        font-size: 11px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }
    
    .btn-view {
        background: #27AE60;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s;
    }
    
    .btn-view:hover {
        background: #2ECC71;
        transform: translateY(-1px);
    }
    
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #666;
    }
    
    @media (max-width: 768px) {
        .student-overview-item {
            grid-template-columns: 1fr;
            text-align: center;
        }
        
        .student-quick-stats {
            justify-content: space-around;
        }
    }
    </style>
    
    <script>
    function viewStudentDetail(studentId) {
        window.location.href = '?view=students&student_id=' + studentId;
    }
    </script>
    <?php
}
?>
