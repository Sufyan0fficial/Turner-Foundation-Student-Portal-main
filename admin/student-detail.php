<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
if (!$student_id) {
    wp_die('Invalid student ID');
}

global $wpdb;
$students_table = $wpdb->prefix . 'tfsp_students';
$documents_table = $wpdb->prefix . 'tfsp_documents';
$messages_table = $wpdb->prefix . 'tfsp_messages';
$attendance_table = $wpdb->prefix . 'tfsp_attendance';

// Get student details
$student = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $students_table WHERE user_id = %d",
    $student_id
));

if (!$student) {
    wp_die('Student not found');
}

// Get student documents
$documents = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $documents_table WHERE user_id = %d ORDER BY upload_date DESC",
    $student_id
));

// Get student messages
$messages = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $messages_table WHERE student_id = %d ORDER BY created_at DESC LIMIT 5",
    $student_id
));

// Get attendance records
$attendance = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $attendance_table WHERE student_id = %d ORDER BY session_date DESC LIMIT 10",
    $student_id
));

// Calculate attendance percentage
$total_sessions = count($attendance);
$present_sessions = count(array_filter($attendance, function($a) { 
    return in_array($a->status, ['present', 'excused']); 
}));
$attendance_percentage = $total_sessions > 0 ? round(($present_sessions / $total_sessions) * 100) : 0;

// College prep progress (mock data - in real implementation, get from progress table)
$college_prep_items = array(
    'Academic Resume' => 'completed',
    'Personal Essay' => 'in_progress', 
    'Recommendation Letters' => 'not_started',
    'Transcript' => 'completed',
    'Financial Aid' => 'not_started',
    'Community Service' => 'completed',
    'College List' => 'in_progress',
    'College Tours' => 'not_started',
    'FAFSA' => 'not_started',
    'College Admissions Tests' => 'not_started'
);
?>

<style>
.student-detail-header {
    background: linear-gradient(135deg, #8BC34A 0%, #689F38 100%);
    color: white;
    padding: 24px;
    border-radius: 12px;
    margin-bottom: 24px;
}

.student-detail-header h1 {
    margin: 0 0 8px 0;
    font-size: 24px;
}

.student-meta {
    display: flex;
    gap: 24px;
    font-size: 14px;
    opacity: 0.9;
}

.detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

.detail-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.detail-card-header {
    padding: 16px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 600;
    color: #000;
}

.detail-card-content {
    padding: 20px;
}

.progress-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.progress-item:last-child {
    border-bottom: none;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-completed { background: #d4edda; color: #155724; }
.status-in-progress { background: #fff3cd; color: #856404; }
.status-not-started { background: #f8d7da; color: #721c24; }

.attendance-summary {
    text-align: center;
    padding: 20px;
}

.attendance-percentage {
    font-size: 36px;
    font-weight: 700;
    color: #8BC34A;
    margin-bottom: 8px;
}

.document-item, .message-item {
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.document-item:last-child, .message-item:last-child {
    border-bottom: none;
}

.document-name, .message-subject {
    font-weight: 500;
    margin-bottom: 4px;
}

.document-meta, .message-meta {
    font-size: 12px;
    color: #666;
}

@media (max-width: 768px) {
    .detail-grid {
        grid-template-columns: 1fr;
    }
    
    .student-meta {
        flex-direction: column;
        gap: 8px;
    }
}
</style>

<div class="wrap">
    <div class="student-detail-header">
        <h1><?php echo esc_html($student->first_name . ' ' . $student->last_name); ?></h1>
        <div class="student-meta">
            <span>📧 <?php echo esc_html($student->email); ?></span>
            <span>🎓 Grade <?php echo esc_html($student->grade_level); ?></span>
            <span>🏫 <?php echo esc_html($student->school); ?></span>
            <span>📅 Joined <?php echo date('M j, Y', strtotime($student->created_at)); ?></span>
        </div>
    </div>

    <div class="detail-grid">
        <!-- College Prep Progress -->
        <div class="detail-card">
            <div class="detail-card-header">
                🎯 College Preparation Progress
            </div>
            <div class="detail-card-content">
                <?php foreach ($college_prep_items as $item => $status): ?>
                    <div class="progress-item">
                        <span><?php echo esc_html($item); ?></span>
                        <span class="status-badge status-<?php echo $status; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Attendance Summary -->
        <div class="detail-card">
            <div class="detail-card-header">
                📊 Attendance Summary
            </div>
            <div class="detail-card-content">
                <div class="attendance-summary">
                    <div class="attendance-percentage"><?php echo $attendance_percentage; ?>%</div>
                    <div>Overall Attendance</div>
                    <div style="margin-top: 16px; font-size: 14px; color: #666;">
                        <?php echo $present_sessions; ?> of <?php echo $total_sessions; ?> sessions attended
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="detail-grid">
        <!-- Recent Documents -->
        <div class="detail-card">
            <div class="detail-card-header">
                📄 Recent Documents (<?php echo count($documents); ?>)
            </div>
            <div class="detail-card-content">
                <?php if (empty($documents)): ?>
                    <p style="color: #666; text-align: center; margin: 20px 0;">No documents uploaded yet</p>
                <?php else: ?>
                    <?php foreach (array_slice($documents, 0, 5) as $doc): ?>
                        <div class="document-item">
                            <div class="document-name"><?php echo esc_html($doc->document_name); ?></div>
                            <div class="document-meta">
                                <?php echo esc_html($doc->document_type); ?> • 
                                <span class="status-badge status-<?php echo $doc->status; ?>">
                                    <?php echo ucfirst($doc->status); ?>
                                </span> • 
                                <?php echo date('M j, Y', strtotime($doc->upload_date)); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Messages -->
        <div class="detail-card">
            <div class="detail-card-header">
                💬 Recent Messages (<?php echo count($messages); ?>)
            </div>
            <div class="detail-card-content">
                <?php if (empty($messages)): ?>
                    <p style="color: #666; text-align: center; margin: 20px 0;">No messages yet</p>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="message-item">
                            <div class="message-subject"><?php echo esc_html($msg->subject); ?></div>
                            <div class="message-meta">
                                To <?php echo ucfirst($msg->message_type); ?> • 
                                <span class="status-badge status-<?php echo $msg->status; ?>">
                                    <?php echo ucfirst($msg->status); ?>
                                </span> • 
                                <?php echo date('M j, Y', strtotime($msg->created_at)); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <p>
        <a href="<?php echo admin_url('admin.php?page=tfsp-students'); ?>" class="button">
            ← Back to Students
        </a>
        <a href="<?php echo admin_url('admin.php?page=tfsp-attendance'); ?>" class="button button-primary">
            View Full Attendance
        </a>
    </p>
</div>
