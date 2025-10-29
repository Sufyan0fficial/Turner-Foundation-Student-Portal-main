<?php
session_start();
if (!isset($_SESSION['tfsp_admin_id'])) {
    die('Unauthorized access');
}

// Redirect to PDF version
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
if (!$student_id) die('Invalid student ID');

// Check if we want PDF (default) or HTML
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf';

if ($format === 'pdf') {
    // Try mPDF first, fallback to simple PDF
    if (class_exists('Mpdf\Mpdf')) {
        header('Location: generate-student-report-pdf.php?student_id=' . $student_id);
    } else {
        header('Location: generate-student-report-simple-pdf.php?student_id=' . $student_id);
    }
    exit;
}

// Original HTML version (if specifically requested)
require_once('../../../wp-load.php');

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
if (!$student_id) die('Invalid student ID');

global $wpdb;
$student = get_user_by('ID', $student_id);
if (!$student) die('Student not found');

// Get student info
$student_info = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tfsp_students WHERE user_id = %d", $student_id
));

// Get progress data
$progress = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d ORDER BY step_order", $student_id
));

// Get attendance
$attendance = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tfsp_attendance_records WHERE student_id = %d ORDER BY session_date DESC", $student_id
));

// Get coach sessions
$coach_sessions = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tfsp_coach_sessions WHERE student_id = %d ORDER BY session_date DESC", $student_id
));

// Get documents
$documents = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tfsp_documents WHERE user_id = %d ORDER BY upload_date DESC", $student_id
));

// Calculate stats
$total_steps = count($progress);
$completed_steps = count(array_filter($progress, fn($p) => $p->status === 'completed'));
$progress_pct = $total_steps > 0 ? round(($completed_steps / $total_steps) * 100) : 0;

$present = count(array_filter($attendance, fn($a) => $a->status === 'present'));
$absent = count(array_filter($attendance, fn($a) => $a->status === 'absent'));
$tardy = count(array_filter($attendance, fn($a) => $a->status === 'tardy'));
$excused = count(array_filter($attendance, fn($a) => $a->status === 'excused'));
$attendance_pct = count($attendance) > 0 ? round(($present / count($attendance)) * 100) : 0;

$sessions_attended = count(array_filter($coach_sessions, fn($s) => in_array($s->status, ['completed', 'attended'])));
$sessions_total = count($coach_sessions);

// Set headers for download
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="Student_Report_' . sanitize_file_name($student->display_name) . '_' . date('Y-m-d') . '.html"');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Progress Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #333; }
        .header { text-align: center; border-bottom: 3px solid #3f5340; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { margin: 0; color: #3f5340; }
        .header p { margin: 5px 0; color: #666; }
        .section { margin-bottom: 30px; page-break-inside: avoid; }
        .section h2 { background: #3f5340; color: white; padding: 10px; margin: 0 0 15px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .info-item { padding: 10px; background: #f8f9fa; border-left: 3px solid #3f5340; }
        .info-label { font-weight: bold; color: #3f5340; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat-box { text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .stat-number { font-size: 32px; font-weight: bold; color: #3f5340; }
        .stat-label { font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #3f5340; color: white; padding: 10px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background: #f8f9fa; }
        .status-completed { color: #28a745; font-weight: bold; }
        .status-in_progress { color: #ffc107; font-weight: bold; }
        .status-not_started { color: #6c757d; }
        .status-present { color: #28a745; font-weight: bold; }
        .status-absent { color: #dc3545; font-weight: bold; }
        .status-tardy { color: #ff9800; font-weight: bold; }
        .status-excused { color: #2196f3; font-weight: bold; }
        .footer { margin-top: 50px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ddd; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>YCAM Mentorship Program</h1>
        <h2>Student Progress Report</h2>
        <p>Generated: <?php echo date('F j, Y g:i A'); ?></p>
    </div>

    <div class="section">
        <h2>Student Information</h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Name:</div>
                <?php echo esc_html($student->display_name); ?>
            </div>
            <div class="info-item">
                <div class="info-label">Email:</div>
                <?php echo esc_html($student->user_email); ?>
            </div>
            <div class="info-item">
                <div class="info-label">Student Phone:</div>
                <?php echo esc_html($student_info->student_phone ?? 'N/A'); ?>
            </div>
            <div class="info-item">
                <div class="info-label">Classification:</div>
                <?php echo esc_html($student_info->classification ?? 'N/A'); ?>
            </div>
            <div class="info-item">
                <div class="info-label">Parent Name:</div>
                <?php echo esc_html($student_info->parent_name ?? 'N/A'); ?>
            </div>
            <div class="info-item">
                <div class="info-label">Parent Email:</div>
                <?php echo esc_html($student_info->parent_email ?? 'N/A'); ?>
            </div>
            <div class="info-item">
                <div class="info-label">Parent Phone:</div>
                <?php echo esc_html($student_info->parent_phone ?? 'N/A'); ?>
            </div>
            <div class="info-item">
                <div class="info-label">Registered:</div>
                <?php echo date('M j, Y', strtotime($student->user_registered)); ?>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Performance Summary</h2>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-number"><?php echo $progress_pct; ?>%</div>
                <div class="stat-label">Progress</div>
                <div style="font-size: 11px; color: #999;"><?php echo $completed_steps; ?>/<?php echo $total_steps; ?> steps</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $attendance_pct; ?>%</div>
                <div class="stat-label">Attendance</div>
                <div style="font-size: 11px; color: #999;"><?php echo $present; ?>/<?php echo count($attendance); ?> present</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $sessions_attended; ?></div>
                <div class="stat-label">Coach Sessions</div>
                <div style="font-size: 11px; color: #999;"><?php echo $sessions_total; ?> total</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo count($documents); ?></div>
                <div class="stat-label">Documents</div>
                <div style="font-size: 11px; color: #999;">Uploaded</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>College Readiness Progress</h2>
        <table>
            <thead>
                <tr>
                    <th>Step</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Completed Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($progress): ?>
                    <?php foreach ($progress as $step): ?>
                        <tr>
                            <td><?php echo $step->step_order; ?></td>
                            <td><?php echo esc_html($step->step_description); ?></td>
                            <td class="status-<?php echo $step->status; ?>"><?php echo ucfirst(str_replace('_', ' ', $step->status)); ?></td>
                            <td><?php echo $step->completed_date ? date('M j, Y', strtotime($step->completed_date)) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align: center; color: #999;">No progress data available</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Attendance Record</h2>
        <div style="margin-bottom: 15px;">
            <strong>Summary:</strong> 
            Present: <?php echo $present; ?> | 
            Absent: <?php echo $absent; ?> | 
            Tardy: <?php echo $tardy; ?> | 
            Excused: <?php echo $excused; ?>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($attendance): ?>
                    <?php foreach (array_slice($attendance, 0, 20) as $record): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($record->session_date)); ?></td>
                            <td class="status-<?php echo $record->status; ?>"><?php echo strtoupper($record->status[0]); ?> - <?php echo ucfirst($record->status); ?></td>
                            <td><?php echo esc_html($record->notes ?: '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align: center; color: #999;">No attendance records</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>One-on-One Coach Sessions</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($coach_sessions): ?>
                    <?php foreach ($coach_sessions as $session): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($session->session_date)); ?></td>
                            <td><?php echo $session->session_time ? date('g:i A', strtotime($session->session_time)) : '-'; ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $session->status)); ?></td>
                            <td><?php echo esc_html($session->notes ?: '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align: center; color: #999;">No coach sessions recorded</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Uploaded Documents</h2>
        <table>
            <thead>
                <tr>
                    <th>Document Type</th>
                    <th>File Name</th>
                    <th>Upload Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($documents): ?>
                    <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td><?php echo esc_html(ucwords(str_replace('_', ' ', $doc->document_type))); ?></td>
                            <td><?php echo esc_html($doc->file_name); ?></td>
                            <td><?php echo date('M j, Y', strtotime($doc->upload_date)); ?></td>
                            <td><?php echo ucfirst($doc->approval_status); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align: center; color: #999;">No documents uploaded</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p><strong>YCAM Mentorship Program</strong></p>
        <p>This report is confidential and intended for program use only.</p>
    </div>
</body>
</html>
