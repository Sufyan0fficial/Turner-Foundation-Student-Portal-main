<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_meeting_management() {
    global $wpdb;
    
    $meetings = $wpdb->get_results("
        SELECT m.*, u.display_name, u.user_email 
        FROM {$wpdb->prefix}tfsp_meetings m 
        JOIN {$wpdb->users} u ON m.user_id = u.ID 
        ORDER BY m.meeting_date DESC, m.meeting_time DESC
    ");
    
    $students = get_users(array('role' => 'subscriber'));
    ?>
    
    <div class="section">
        <h2>📅 Meeting Management</h2>
        <p>Schedule and manage upcoming sessions with students</p>
        
        <div class="meeting-controls">
            <button class="btn" onclick="showAddMeetingForm()">➕ Schedule New Meeting</button>
            <select id="status-filter" onchange="filterMeetings()">
                <option value="">All Meetings</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
        
        <!-- Add Meeting Form -->
        <div id="add-meeting-form" class="meeting-form hidden">
            <h4>Schedule New Meeting</h4>
            <div class="form-grid">
                <div class="form-group">
                    <label>Student</label>
                    <select id="meeting-student" required>
                        <option value="">Select Student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student->ID; ?>"><?php echo $student->display_name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Meeting Date</label>
                    <input type="date" id="meeting-date" required>
                </div>
                <div class="form-group">
                    <label>Meeting Time</label>
                    <input type="time" id="meeting-time" required>
                </div>
                <div class="form-group">
                    <label>Meeting Type</label>
                    <select id="meeting-type" required>
                        <option value="">Select Type</option>
                        <option value="college_planning">College Planning</option>
                        <option value="application_review">Application Review</option>
                        <option value="essay_feedback">Essay Feedback</option>
                        <option value="progress_check">Progress Check</option>
                        <option value="career_guidance">Career Guidance</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>Notes (Optional)</label>
                    <textarea id="meeting-notes" placeholder="Meeting agenda or notes..."></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button class="btn" onclick="scheduleMeeting()">Schedule Meeting</button>
                <button class="btn-secondary" onclick="hideAddMeetingForm()">Cancel</button>
            </div>
        </div>
        
        <!-- Meetings List -->
        <div class="meetings-list">
            <?php if (empty($meetings)): ?>
                <div class="empty-state">
                    <p>No meetings scheduled yet.</p>
                    <p>Click "Schedule New Meeting" to get started.</p>
                </div>
            <?php else: ?>
                <div class="meetings-table">
                    <div class="table-header">
                        <div class="col-student">Student</div>
                        <div class="col-datetime">Date & Time</div>
                        <div class="col-type">Type</div>
                        <div class="col-status">Status</div>
                        <div class="col-actions">Actions</div>
                    </div>
                    <?php foreach ($meetings as $meeting): ?>
                        <div class="meeting-row" data-status="<?php echo $meeting->status; ?>">
                            <div class="col-student">
                                <div class="student-name"><?php echo $meeting->display_name; ?></div>
                                <div class="student-email"><?php echo $meeting->user_email; ?></div>
                            </div>
                            <div class="col-datetime">
                                <div class="meeting-date"><?php echo date('M j, Y', strtotime($meeting->meeting_date)); ?></div>
                                <div class="meeting-time"><?php echo date('g:i A', strtotime($meeting->meeting_time)); ?></div>
                            </div>
                            <div class="col-type"><?php echo ucfirst(str_replace('_', ' ', $meeting->meeting_type)); ?></div>
                            <div class="col-status">
                                <select onchange="updateMeetingStatus(<?php echo $meeting->id; ?>, this.value)" class="status-select">
                                    <option value="pending" <?php selected($meeting->status, 'pending'); ?>>Pending</option>
                                    <option value="confirmed" <?php selected($meeting->status, 'confirmed'); ?>>Confirmed</option>
                                    <option value="completed" <?php selected($meeting->status, 'completed'); ?>>Completed</option>
                                    <option value="cancelled" <?php selected($meeting->status, 'cancelled'); ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-actions">
                                <button class="btn-small" onclick="editMeeting(<?php echo $meeting->id; ?>)">Edit</button>
                                <button class="btn-small btn-danger" onclick="deleteMeeting(<?php echo $meeting->id; ?>)">Delete</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
    .meeting-controls {
        display: flex;
        gap: 15px;
        align-items: center;
        margin-bottom: 25px;
        padding: 20px;
        background: linear-gradient(135deg, #f8fff8 0%, #f0f8f0 100%);
        border-radius: 12px;
        border: 1px solid #e8f5e8;
    }
    
    .meeting-form {
        background: white;
        padding: 25px;
        border-radius: 12px;
        margin: 20px 0;
        border: 2px solid #e8f5e8;
    }
    .meeting-form.hidden {
        display: none;
    }
    .meeting-form h4 {
        margin: 0 0 20px 0;
        color: #2d5016;
        font-size: 18px;
    }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    .form-group.full-width {
        grid-column: 1 / -1;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }
    .form-group input, .form-group select, .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 2px solid #e8f5e8;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.3s;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
        outline: none;
        border-color: #27AE60;
        box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
    }
    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }
    .form-actions {
        display: flex;
        gap: 15px;
    }
    .btn-secondary {
        background: #95A5A6;
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: background 0.3s;
    }
    .btn-secondary:hover {
        background: #7F8C8D;
    }
    
    .meetings-table {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .table-header {
        display: grid;
        grid-template-columns: 2fr 1.5fr 1fr 1fr 1fr;
        gap: 15px;
        padding: 20px;
        background: linear-gradient(135deg, #2d5016 0%, #3f5340 100%);
        color: white;
        font-weight: 600;
        font-size: 14px;
    }
    .meeting-row {
        display: grid;
        grid-template-columns: 2fr 1.5fr 1fr 1fr 1fr;
        gap: 15px;
        padding: 20px;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s;
    }
    .meeting-row:hover {
        background: linear-gradient(135deg, #f8fff8 0%, #f0f8f0 100%);
    }
    .student-name {
        font-weight: 600;
        color: #2d5016;
        margin-bottom: 2px;
    }
    .student-email {
        font-size: 12px;
        color: #666;
    }
    .meeting-date {
        font-weight: 600;
        color: #333;
        margin-bottom: 2px;
    }
    .meeting-time {
        font-size: 12px;
        color: #666;
    }
    .status-select {
        padding: 6px 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 12px;
        background: white;
    }
    .btn-small {
        background: #27AE60;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        margin-right: 5px;
        transition: background 0.3s;
    }
    .btn-small:hover {
        background: #2ECC71;
    }
    .btn-small.btn-danger {
        background: #E74C3C;
    }
    .btn-small.btn-danger:hover {
        background: #C0392B;
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #666;
        background: white;
        border-radius: 12px;
    }
    </style>
    
    <script>
    function showAddMeetingForm() {
        document.getElementById('add-meeting-form').classList.remove('hidden');
    }
    
    function hideAddMeetingForm() {
        document.getElementById('add-meeting-form').classList.add('hidden');
        // Reset form
        document.getElementById('meeting-student').value = '';
        document.getElementById('meeting-date').value = '';
        document.getElementById('meeting-time').value = '';
        document.getElementById('meeting-type').value = '';
        document.getElementById('meeting-notes').value = '';
    }
    
    function scheduleMeeting() {
        const studentId = document.getElementById('meeting-student').value;
        const date = document.getElementById('meeting-date').value;
        const time = document.getElementById('meeting-time').value;
        const type = document.getElementById('meeting-type').value;
        const notes = document.getElementById('meeting-notes').value;
        
        if (!studentId || !date || !time || !type) {
            showCustomAlert('Please fill in all required fields', 'error');
            return;
        }
        
        jQuery.post(ajaxurl, {
            action: 'schedule_meeting_admin',
            student_id: studentId,
            meeting_date: date,
            meeting_time: time,
            meeting_type: type,
            notes: notes,
            nonce: '<?php echo wp_create_nonce('meeting_admin_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                showCustomAlert('Meeting scheduled successfully!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showCustomAlert('Error scheduling meeting: ' + response.data, 'error');
            }
        });
    }
    
    function updateMeetingStatus(meetingId, status) {
        jQuery.post(ajaxurl, {
            action: 'update_meeting_status',
            meeting_id: meetingId,
            status: status,
            nonce: '<?php echo wp_create_nonce('meeting_status_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                showCustomAlert('Meeting status updated', 'success');
            } else {
                showCustomAlert('Error updating status', 'error');
            }
        });
    }
    
    function filterMeetings() {
        const filter = document.getElementById('status-filter').value;
        const rows = document.querySelectorAll('.meeting-row');
        
        rows.forEach(row => {
            if (!filter || row.dataset.status === filter) {
                row.style.display = 'grid';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    function editMeeting(meetingId) {
        // Implementation for editing meetings
        showCustomAlert('Edit meeting functionality coming soon', 'info');
    }
    
    function deleteMeeting(meetingId) {
        if (confirm('Are you sure you want to delete this meeting?')) {
            jQuery.post(ajaxurl, {
                action: 'delete_meeting',
                meeting_id: meetingId,
                nonce: '<?php echo wp_create_nonce('delete_meeting_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    showCustomAlert('Meeting deleted successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showCustomAlert('Error deleting meeting', 'error');
                }
            });
        }
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
    <?php
}
?>
