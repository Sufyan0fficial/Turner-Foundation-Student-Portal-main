<?php
if (!defined('ABSPATH')) {
    exit;
}

// Check if viewing individual student
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;

if ($student_id) {
    // Show individual student details
    $student = get_user_by('ID', $student_id);
    if (!$student) {
        echo '<div class="notice notice-error">Student not found.</div>';
        return;
    }
    
    global $wpdb;
    
    // Get student progress
    $progress_data = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d ORDER BY step_key",
        $student_id
    ));
    
    // If no progress exists, create default 10 steps
    if (empty($progress_data)) {
        $default_steps = array(
            'academic_resume' => 'Academic Resume',
            'personal_essay' => 'Personal Essay', 
            'recommendation_letters' => 'Recommendation Letters',
            'transcript' => 'Transcript',
            'financial_aid' => 'Financial Aid',
            'community_service' => 'Community Service',
            'college_list' => 'Create Interest List of Colleges',
            'college_tours' => 'College Tours',
            'fafsa' => 'FAFSA',
            'admissions_tests' => 'College Admissions Tests'
        );
        
        foreach ($default_steps as $step_key => $step_title) {
            $wpdb->insert(
                $wpdb->prefix . 'tfsp_student_progress',
                array(
                    'student_id' => $student_id,
                    'step_key' => $step_key,
                    'status' => 'pending'
                ),
                array('%d', '%s', '%s')
            );
        }
        
        // Re-fetch the data
        $progress_data = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d ORDER BY step_key",
            $student_id
        ));
    }
    
    // Get attendance records
    $attendance_data = $wpdb->get_results($wpdb->prepare(
        "SELECT ar.*, s.subject, s.session_date, s.start_time, s.end_time 
         FROM {$wpdb->prefix}tfsp_attendance_records ar
         LEFT JOIN {$wpdb->prefix}tfsp_sessions s ON s.id = ar.session_id
         WHERE ar.student_id = %d 
         ORDER BY s.session_date DESC LIMIT 20",
        $student_id
    ));
    
    // Get documents
    $documents = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tfsp_documents WHERE user_id = %d ORDER BY upload_date DESC",
        $student_id
    ));
    
    // Get messages
    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tfsp_messages 
         WHERE sender_id = %d OR recipient_id = %d 
         ORDER BY sent_date DESC LIMIT 10",
        $student_id, $student_id
    ));
    
    // Calculate stats
    $total_steps = count($progress_data);
    $completed_steps = count(array_filter($progress_data, function($p) { return $p->status === 'completed'; }));
    $progress_percentage = $total_steps > 0 ? round(($completed_steps / $total_steps) * 100) : 0;
    
    $total_sessions = count($attendance_data);
    $present_sessions = count(array_filter($attendance_data, function($a) { return $a->status === 'present'; }));
    $attendance_percentage = $total_sessions > 0 ? round(($present_sessions / $total_sessions) * 100) : 0;
    ?>
    
    <div class="section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2>👤 <?php echo esc_html($student->display_name); ?></h2>
                <p><?php echo esc_html($student->user_email); ?> • Registered: <?php echo date('M j, Y', strtotime($student->user_registered)); ?></p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="<?php echo plugin_dir_url(dirname(dirname(__FILE__))); ?>generate-student-report.php?student_id=<?php echo $student_id; ?>" 
                   class="btn" style="background: #28a745;">
                    📥 Download Report
                </a>
                <a href="?view=students" class="btn">← Back to Students</a>
            </div>
        </div>
        
        <!-- Student Stats -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="card">
                <h3>Progress</h3>
                <div class="number"><?php echo $progress_percentage; ?>%</div>
                <small><?php echo $completed_steps; ?> of <?php echo $total_steps; ?> steps completed</small>
            </div>
            <div class="card">
                <h3>Attendance</h3>
                <div class="number"><?php echo $attendance_percentage; ?>%</div>
                <small><?php echo $present_sessions; ?> of <?php echo $total_sessions; ?> sessions attended</small>
            </div>
            <div class="card">
                <h3>Documents</h3>
                <div class="number"><?php echo count($documents); ?></div>
                <small>Total uploaded</small>
            </div>
            <div class="card">
                <h3>Messages</h3>
                <div class="number"><?php echo count($messages); ?></div>
                <small>Total conversations</small>
            </div>
        </div>
        
        <!-- Tabs -->
        <div style="border-bottom: 1px solid #e5e7eb; margin-bottom: 20px;">
            <div style="display: flex; gap: 20px;">
                <button onclick="showTab('progress')" class="tab-btn active" id="progress-tab">📈 Progress</button>
                <button onclick="showTab('attendance')" class="tab-btn" id="attendance-tab">📅 Attendance</button>
                <button onclick="showTab('documents')" class="tab-btn" id="documents-tab">📄 Documents</button>
                <button onclick="showTab('messages')" class="tab-btn" id="messages-tab">💬 Messages</button>
            </div>
        </div>
        
        <!-- Progress Tab -->
        <div id="progress-content" class="tab-content">
            <h3>Learning Progress</h3>
            <?php if ($progress_data): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Step</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Completed Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $step_titles = array(
                                'academic_resume' => 'Academic Resume',
                                'personal_essay' => 'Personal Essay', 
                                'recommendation_letters' => 'Recommendation Letters',
                                'transcript' => 'Transcript',
                                'financial_aid' => 'Financial Aid',
                                'community_service' => 'Community Service',
                                'college_list' => 'Create Interest List of Colleges',
                                'college_tours' => 'College Tours',
                                'fafsa' => 'FAFSA',
                                'admissions_tests' => 'College Admissions Tests'
                            );
                            $step_counter = 1;
                            foreach ($progress_data as $step): ?>
                                <tr>
                                    <td><?php echo $step_counter++; ?></td>
                                    <td><?php echo esc_html($step_titles[$step->step_key] ?? $step->step_key); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $step->status; ?>">
                                            <?php echo ucfirst($step->status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $step->completed_date ? date('M j, Y', strtotime($step->completed_date)) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No progress data available for this student.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Attendance Tab -->
        <div id="attendance-content" class="tab-content" style="display: none;">
            <h3>Attendance History</h3>
            <?php if ($attendance_data): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Subject</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance_data as $record): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($record->session_date)); ?></td>
                                    <td><?php echo esc_html($record->subject ?: 'General'); ?></td>
                                    <td><?php echo $record->start_time ? date('g:i A', strtotime($record->start_time)) : '-'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $record->status; ?>">
                                            <?php echo ucfirst($record->status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($record->notes ?: '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No attendance records found for this student.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Documents Tab -->
        <div id="documents-content" class="tab-content" style="display: none;">
            <h3>Uploaded Documents</h3>
            <?php if ($documents): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>Upload Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td><?php echo esc_html($doc->document_name ?? $doc->file_name ?? 'Untitled Document'); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($doc->upload_date ?? $doc->created_at)); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo esc_attr($doc->status ?? 'pending'); ?>">
                                            <?php echo ucfirst($doc->status ?? 'pending'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn" style="padding: 4px 8px; font-size: 12px;">View</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No documents uploaded by this student.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Messages Tab -->
        <div id="messages-content" class="tab-content" style="display: none;">
            <h3>Message History</h3>
            <?php if ($messages): ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="message-item">
                        <div class="message-header">
                            <strong><?php echo esc_html($msg->subject); ?></strong>
                            <span class="message-date"><?php echo date('M j, Y g:i A', strtotime($msg->sent_date)); ?></span>
                        </div>
                        <div class="message-content">
                            <?php echo nl2br(esc_html(substr($msg->message, 0, 200))); ?>
                            <?php if (strlen($msg->message) > 200): ?>...<?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No messages found for this student.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
    .tab-btn {
        background: none;
        border: none;
        padding: 12px 0;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        font-weight: 500;
        color: #6b7280;
    }
    
    .tab-btn.active {
        color: #8BC34A;
        border-bottom-color: #8BC34A;
    }
    
    .tab-content {
        animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .status-completed { background: #d4edda; color: #155724; }
    .status-in_progress { background: #fff3cd; color: #856404; }
    .status-not_started { background: #f8d7da; color: #721c24; }
    .status-present { background: #d4edda; color: #155724; }
    .status-absent { background: #f8d7da; color: #721c24; }
    .status-tardy { background: #fff3cd; color: #856404; }
    </style>
    
    <script>
    function showTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.style.display = 'none';
        });
        
        // Remove active class from all tabs
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show selected tab content
        document.getElementById(tabName + '-content').style.display = 'block';
        document.getElementById(tabName + '-tab').classList.add('active');
    }
    </script>
    
    <?php
    return; // Exit early to show student details
}

// Show students list if no specific student selected
$students = get_users(array('role' => 'subscriber'));
global $wpdb;
?>

<div class="section">
    <h2>👥 Student Management</h2>
    <p>Manage student accounts and view detailed information</p>
    
    <div class="controls">
        <input type="text" placeholder="Search students..." id="studentSearch" onkeyup="searchStudents()">
    </div>
    
    <div class="table-responsive">
        <table class="data-table" id="studentsTable">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Email</th>
                    <th>Registration Date</th>
                    <th>Progress</th>
                    <th>Attendance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <?php
                    // Get student progress using same function as dashboard
                    $progress = get_student_progress_percentage($student->ID);
                    
                    // Get attendance
                    $attendance_records = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_attendance_records WHERE student_id = %d",
                        $student->ID
                    ));
                    $present_records = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_attendance_records WHERE student_id = %d AND status = 'present'",
                        $student->ID
                    ));
                    $attendance = $attendance_records > 0 ? round(($present_records / $attendance_records) * 100) : 0;
                    ?>
                    <tr>
                        <td data-label="Student Name"><strong><?php echo esc_html($student->display_name); ?></strong></td>
                        <td data-label="Email"><?php echo esc_html($student->user_email); ?></td>
                        <td data-label="Registration Date"><?php echo date('M j, Y', strtotime($student->user_registered)); ?></td>
                        <td data-label="Progress">
                            <div style="background: #f3f4f6; border-radius: 10px; height: 8px; overflow: hidden;">
                                <div style="background: #8BC34A; height: 100%; width: <?php echo $progress; ?>%;"></div>
                            </div>
                            <small><?php echo $progress; ?>%</small>
                        </td>
                        <td data-label="Attendance"><?php echo $attendance; ?>%</td>
                        <td data-label="Actions">
                            <button onclick="viewStudent(<?php echo $student->ID; ?>)" class="btn" style="padding: 4px 8px; font-size: 12px;">View</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($students)): ?>
        <div class="empty-state">
            <h3>No Students Found</h3>
            <p>No students are currently registered in the system.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function searchStudents() {
    const input = document.getElementById('studentSearch');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('studentsTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < cells.length - 1; j++) {
            if (cells[j].textContent.toLowerCase().includes(filter)) {
                found = true;
                break;
            }
        }
        
        rows[i].style.display = found ? '' : 'none';
    }
}

function addNewStudent() {
    const name = prompt('Student Name:');
    if (!name) return;
    const email = prompt('Student Email:');
    if (!email) return;
    
    // Add AJAX call to create student
    alert('Student creation functionality coming soon!');
}

function viewStudent(studentId) {
    window.location.href = '?view=students&student_id=' + studentId;
}

function editStudent(studentId) {
    alert('Student editing functionality coming soon!');
}
</script>
