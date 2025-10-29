<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

// Handle session creation/update
if (isset($_POST['save_session']) && wp_verify_nonce($_POST['nonce'], 'coach_session')) {
    $session_id = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;
    $student_id = intval($_POST['student_id']);
    $session_date = sanitize_text_field($_POST['session_date']);
    $session_time = sanitize_text_field($_POST['session_time']);
    $status = sanitize_text_field($_POST['status']);
    $notes = sanitize_textarea_field($_POST['notes']);
    
    $data = array(
        'student_id' => $student_id,
        'session_date' => $session_date,
        'session_time' => $session_time,
        'status' => $status,
        'notes' => $notes,
        'updated_at' => current_time('mysql')
    );
    
    if ($session_id) {
        $wpdb->update($wpdb->prefix . 'tfsp_coach_sessions', $data, array('id' => $session_id));
        echo '<div class="notice notice-success">Session updated successfully!</div>';
    } else {
        $data['created_by'] = get_current_user_id();
        $data['created_at'] = current_time('mysql');
        $wpdb->insert($wpdb->prefix . 'tfsp_coach_sessions', $data);
        echo '<div class="notice notice-success">Session created successfully!</div>';
    }
}

// Get all students
$students = get_users(array('role' => 'subscriber'));

// Get filter
$filter_student = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// Build query
$where = array('1=1');
if ($filter_student) $where[] = $wpdb->prepare('s.student_id = %d', $filter_student);
if ($filter_status) $where[] = $wpdb->prepare('s.status = %s', $filter_status);

$sessions = $wpdb->get_results("
    SELECT s.*, u.display_name as student_name, u.user_email
    FROM {$wpdb->prefix}tfsp_coach_sessions s
    LEFT JOIN {$wpdb->users} u ON u.ID = s.student_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY s.session_date DESC, s.session_time DESC
");

// Get stats
$total_sessions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_coach_sessions");
$attended = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_coach_sessions WHERE status = 'attended'");
$no_show = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_coach_sessions WHERE status = 'no_show'");
$rescheduled = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_coach_sessions WHERE status = 'rescheduled'");
$excused = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_coach_sessions WHERE status = 'excused'");

// Get group progress report - students and their session completion
$student_progress = $wpdb->get_results("
    SELECT 
        u.ID as student_id,
        u.display_name as student_name,
        u.user_email,
        COUNT(cs.id) as total_sessions,
        SUM(CASE WHEN cs.status = 'attended' THEN 1 ELSE 0 END) as attended_sessions,
        SUM(CASE WHEN cs.status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_sessions,
        ROUND((SUM(CASE WHEN cs.status = 'attended' THEN 1 ELSE 0 END) / 3) * 100) as completion_percentage
    FROM {$wpdb->users} u
    LEFT JOIN {$wpdb->prefix}tfsp_coach_sessions cs ON u.ID = cs.student_id
    WHERE u.ID IN (SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key = '{$wpdb->prefix}capabilities' AND meta_value LIKE '%subscriber%')
    GROUP BY u.ID, u.display_name, u.user_email
    ORDER BY completion_percentage DESC, attended_sessions DESC
");
?>

<style>
.stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 24px; }
.stat-box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.stat-number { font-size: 32px; font-weight: bold; color: #8ebb79; }
.stat-label { color: #6b7280; font-size: 14px; margin-top: 4px; }
.filters { display: flex; gap: 12px; margin-bottom: 20px; }
.session-table { width: 100%; background: white; border-radius: 8px; overflow: hidden; }
.session-table th { background: #f9fafb; padding: 12px; text-align: left; font-weight: 600; }
.session-table td { padding: 12px; border-top: 1px solid #eee; }
.status-badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; }
.status-attended { background: #d1fae5; color: #065f46; }
.status-no_show { background: #fee2e2; color: #991b1b; }
.status-rescheduled { background: #fef3c7; color: #92400e; }
.status-excused { background: #dbeafe; color: #1e40af; }
.status-didnt_attend { background: #f3e8ff; color: #6b21a8; }
.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
.modal-content { background: white; margin: 50px auto; padding: 30px; border-radius: 12px; max-width: 600px; }

/* Group Progress Report Styles */
.progress-report { background: white; border-radius: 8px; padding: 20px; margin-bottom: 24px; }
.progress-table { width: 100%; }
.progress-table th { background: #f9fafb; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e5e7eb; }
.progress-table td { padding: 12px; border-bottom: 1px solid #f3f4f6; }
.progress-bar { width: 100px; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; }
.progress-fill { height: 100%; background: #8ebb79; transition: width 0.3s ease; }
.completion-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.completion-high { background: #d1fae5; color: #065f46; }
.completion-medium { background: #fef3c7; color: #92400e; }
.completion-low { background: #fee2e2; color: #991b1b; }
.completion-none { background: #f3f4f6; color: #6b7280; }

@media (max-width: 768px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .stat-box { padding: 15px; }
    .stat-number { font-size: 24px; }
    .stat-label { font-size: 12px; }
    
    .section > div:first-child {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 15px;
    }
    
    .section > div:first-child button {
        width: 100%;
    }
    
    .filters {
        flex-direction: column;
        gap: 10px;
    }
    
    .filters select {
        width: 100%;
    }
    
    /* Hide table, show cards on mobile */
    .session-table thead,
    .session-table tbody,
    .session-table tr {
        display: block;
    }
    
    .session-table thead {
        display: none;
    }
    
    .session-table tr {
        margin-bottom: 15px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 15px;
        background: white;
    }
    
    .session-table td {
        display: block;
        text-align: left;
        padding: 8px 0;
        border: none;
    }
    
    .session-table td:before {
        content: attr(data-label);
        font-weight: 600;
        display: block;
        margin-bottom: 4px;
        color: #6b7280;
        font-size: 12px;
    }
    
    .session-table td button {
        width: 100%;
        margin-top: 8px;
    }
    
    .modal-content {
        margin: 20px;
        padding: 20px;
        max-width: calc(100% - 40px);
    }
}

@media (max-width: 480px) {
    .stats-grid { grid-template-columns: 1fr; }
    
    .btn {
        padding: 8px 12px;
        font-size: 13px;
    }
}
</style>

<div class="section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h2>👥 One-on-One Coach Sessions</h2>
            <p style="color: #6b7280; margin: 4px 0 0;">Track individual sessions with College & Career Coach</p>
        </div>
        <button onclick="openSessionModal()" class="btn">+ Schedule Session</button>
    </div>
    
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-number"><?php echo $total_sessions; ?></div>
            <div class="stat-label">Total Sessions</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $attended; ?></div>
            <div class="stat-label">Attended</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $no_show; ?></div>
            <div class="stat-label">No-Show</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $rescheduled; ?></div>
            <div class="stat-label">Rescheduled</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $excused; ?></div>
            <div class="stat-label">Excused</div>
        </div>
    </div>
    
    <!-- Group Progress Report -->
    <div class="progress-report">
        <h3 style="margin: 0 0 16px; color: #1f2937;">📊 Student Progress Report</h3>
        <p style="color: #6b7280; margin: 0 0 20px; font-size: 14px;">Track each student's progress toward completing their 3 required coaching sessions</p>
        
        <table class="progress-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Sessions Attended</th>
                    <th>Sessions Scheduled</th>
                    <th>Progress</th>
                    <th>Completion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($student_progress as $student): ?>
                <tr>
                    <td>
                        <div style="font-weight: 600; color: #1f2937;"><?php echo esc_html($student->student_name); ?></div>
                        <div style="font-size: 12px; color: #6b7280;"><?php echo esc_html($student->user_email); ?></div>
                    </td>
                    <td>
                        <span style="font-weight: 600; color: #059669;"><?php echo $student->attended_sessions; ?></span> out of 3
                    </td>
                    <td>
                        <span style="font-weight: 600; color: #0369a1;"><?php echo $student->scheduled_sessions; ?></span> scheduled
                    </td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo min($student->completion_percentage, 100); ?>%"></div>
                        </div>
                    </td>
                    <td>
                        <?php 
                        $completion = $student->completion_percentage;
                        if ($completion >= 100) {
                            echo '<span class="completion-badge completion-high">Complete</span>';
                        } elseif ($completion >= 67) {
                            echo '<span class="completion-badge completion-medium">' . $completion . '%</span>';
                        } elseif ($completion > 0) {
                            echo '<span class="completion-badge completion-low">' . $completion . '%</span>';
                        } else {
                            echo '<span class="completion-badge completion-none">Not Started</span>';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="filters">
        <select onchange="window.location.href='?view=coach-sessions&student_id=' + this.value + '<?php echo $filter_status ? '&status=' . $filter_status : ''; ?>'" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;">
            <option value="">All Students</option>
            <?php foreach ($students as $student): ?>
                <option value="<?php echo $student->ID; ?>" <?php selected($filter_student, $student->ID); ?>>
                    <?php echo esc_html($student->display_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <select onchange="window.location.href='?view=coach-sessions&status=' + this.value + '<?php echo $filter_student ? '&student_id=' . $filter_student : ''; ?>'" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;">
            <option value="">All Statuses</option>
            <option value="attended" <?php selected($filter_status, 'attended'); ?>>Attended</option>
            <option value="no_show" <?php selected($filter_status, 'no_show'); ?>>No-Show</option>
            <option value="rescheduled" <?php selected($filter_status, 'rescheduled'); ?>>Rescheduled</option>
            <option value="excused" <?php selected($filter_status, 'excused'); ?>>Excused</option>
            <option value="didnt_attend" <?php selected($filter_status, 'didnt_attend'); ?>>Didn't Attend</option>
        </select>
    </div>
    
    <table class="session-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Notes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sessions)): ?>
                <tr><td colspan="6" style="text-align: center; color: #9ca3af; padding: 40px;">No sessions found</td></tr>
            <?php else: ?>
                <?php foreach ($sessions as $session): ?>
                    <tr>
                        <td data-label="Student">
                            <strong><?php echo esc_html($session->student_name); ?></strong><br>
                            <small style="color: #6b7280;"><?php echo esc_html($session->user_email); ?></small>
                        </td>
                        <td data-label="Date"><?php echo date('M j, Y', strtotime($session->session_date)); ?></td>
                        <td data-label="Time"><?php echo esc_html($session->session_time); ?></td>
                        <td data-label="Status">
                            <span class="status-badge status-<?php echo $session->status; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $session->status)); ?>
                            </span>
                        </td>
                        <td data-label="Notes"><?php echo esc_html(substr($session->notes, 0, 50)) . (strlen($session->notes) > 50 ? '...' : ''); ?></td>
                        <td data-label="Actions">
                            <button onclick='editSession(<?php echo json_encode($session); ?>)' class="btn" style="padding: 6px 12px; font-size: 13px;">Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Session Modal -->
<div id="sessionModal" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle">Schedule Session</h3>
        <form method="POST">
            <input type="hidden" name="session_id" id="sessionId">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('coach_session'); ?>">
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 500;">Student</label>
                <select name="student_id" id="studentId" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                    <option value="">Select Student</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?php echo $student->ID; ?>"><?php echo esc_html($student->display_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; margin-bottom: 6px; font-weight: 500;">Date</label>
                    <input type="date" name="session_date" id="sessionDate" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 6px; font-weight: 500;">Time</label>
                    <input type="time" name="session_time" id="sessionTime" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 500;">Status</label>
                <select name="status" id="sessionStatus" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                    <option value="attended">Attended</option>
                    <option value="no_show">No-Show</option>
                    <option value="rescheduled">Rescheduled</option>
                    <option value="excused">Excused</option>
                    <option value="didnt_attend">Didn't Attend</option>
                </select>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 500;">Notes</label>
                <textarea name="notes" id="sessionNotes" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;"></textarea>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button type="submit" name="save_session" class="btn">Save Session</button>
                <button type="button" onclick="closeSessionModal()" class="btn" style="background: #6b7280;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSessionModal() {
    document.getElementById('modalTitle').textContent = 'Schedule Session';
    document.getElementById('sessionId').value = '';
    document.getElementById('studentId').value = '';
    document.getElementById('sessionDate').value = '';
    document.getElementById('sessionTime').value = '';
    document.getElementById('sessionStatus').value = 'scheduled';
    document.getElementById('sessionNotes').value = '';
    document.getElementById('sessionModal').style.display = 'flex';
}

function editSession(session) {
    document.getElementById('modalTitle').textContent = 'Edit Session';
    document.getElementById('sessionId').value = session.id;
    document.getElementById('studentId').value = session.student_id;
    document.getElementById('sessionDate').value = session.session_date;
    document.getElementById('sessionTime').value = session.session_time || '';
    document.getElementById('sessionStatus').value = session.status;
    document.getElementById('sessionNotes').value = session.notes || '';
    document.getElementById('sessionModal').style.display = 'flex';
}

function closeSessionModal() {
    document.getElementById('sessionModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('sessionModal');
    if (event.target == modal) {
        closeSessionModal();
    }
}
</script>
