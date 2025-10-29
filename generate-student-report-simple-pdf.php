<?php
// Simple PDF generation using browser's print-to-PDF functionality
// This creates a print-optimized HTML that browsers can convert to PDF

require_once('../../../wp-load.php');

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
if (!$student_id) die('Invalid student ID');

global $wpdb;
$student = get_user_by('ID', $student_id);
if (!$student) die('Student not found');

// Get all data (same as original)
$student_info = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tfsp_students WHERE user_id = %d", $student_id
));

$progress = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d ORDER BY created_at", $student_id
));

$attendance = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tfsp_attendance_records WHERE student_id = %d ORDER BY session_date DESC", $student_id
));

$coach_sessions = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tfsp_coach_sessions WHERE student_id = %d ORDER BY session_date DESC", $student_id
));

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

// Set headers for PDF-optimized HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Progress Report - <?php echo esc_html($student->display_name); ?></title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; font-size: 12px; }
        .header { text-align: center; border-bottom: 3px solid #3f5340; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #3f5340; font-size: 24px; }
        .header h2 { margin: 5px 0; color: #666; font-size: 18px; }
        .section { margin-bottom: 25px; page-break-inside: avoid; }
        .section h2 { background: #3f5340; color: white; padding: 8px; margin: 0 0 10px; font-size: 14px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 15px; }
        .info-item { padding: 8px; background: #f8f9fa; border-left: 3px solid #3f5340; }
        .info-label { font-weight: bold; color: #3f5340; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 15px; }
        .stat-box { text-align: center; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .stat-number { font-size: 20px; font-weight: bold; color: #3f5340; }
        .stat-label { font-size: 11px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
        th { background: #3f5340; color: white; padding: 6px; text-align: left; }
        td { padding: 5px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background: #f8f9fa; }
        .status-completed { color: #28a745; font-weight: bold; }
        .status-in_progress { color: #ffc107; font-weight: bold; }
        .status-not_started { color: #6c757d; }
        .status-present { color: #28a745; font-weight: bold; }
        .status-absent { color: #dc3545; font-weight: bold; }
        .status-tardy { color: #ff9800; font-weight: bold; }
        .status-excused { color: #2196f3; font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; color: #666; font-size: 10px; border-top: 1px solid #ddd; padding-top: 15px; }
        .print-btn { position: fixed; top: 10px; right: 10px; background: #3f5340; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; z-index: 1000; }
    </style>
    <script>
        window.onload = function() {
            // Auto-trigger print dialog for PDF generation
            setTimeout(function() {
                window.print();
            }, 1000);
        }
    </script>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">Print/Save as PDF</button>
    
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
        </div>
    </div>

    <div class="section">
        <h2>Performance Summary</h2>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-number"><?php echo $progress_pct; ?>%</div>
                <div class="stat-label">Progress</div>
                <div style="font-size: 9px; color: #999;"><?php echo $completed_steps; ?>/<?php echo $total_steps; ?> steps</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $attendance_pct; ?>%</div>
                <div class="stat-label">Attendance</div>
                <div style="font-size: 9px; color: #999;"><?php echo $present; ?>/<?php echo count($attendance); ?> present</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $sessions_attended; ?></div>
                <div class="stat-label">Coach Sessions</div>
                <div style="font-size: 9px; color: #999;"><?php echo $sessions_total; ?> total</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo count($documents); ?></div>
                <div class="stat-label">Documents</div>
                <div style="font-size: 9px; color: #999;">Uploaded</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>College Readiness Progress</h2>
        <table>
            <thead>
                <tr>
                    <th>Step</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $roadmap_steps = array(
                    'academic_resume' => 'Academic Resume',
                    'personal_essay' => 'Personal Essay', 
                    'recommendation_letters' => 'Recommendation Letters',
                    'transcript' => 'Transcript',
                    'financial_aid' => 'Financial Aid',
                    'community_service' => 'Community Service',
                    'college_list' => 'College Interest List',
                    'college_tours' => 'College Tours',
                    'fafsa' => 'FAFSA',
                    'admissions_tests' => 'Admissions Tests'
                );
                
                if ($progress): 
                    $step_num = 1;
                    foreach ($roadmap_steps as $key => $title):
                        $step_progress = array_filter($progress, fn($p) => $p->step_key === $key);
                        $current_step = !empty($step_progress) ? reset($step_progress) : null;
                        $status = $current_step ? $current_step->status : 'not_started';
                        $notes = $current_step ? $current_step->notes : '';
                ?>
                    <tr>
                        <td><?php echo $step_num; ?>. <?php echo $title; ?></td>
                        <td class="status-<?php echo $status; ?>"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></td>
                        <td><?php echo esc_html($notes ?: '-'); ?></td>
                    </tr>
                <?php 
                        $step_num++;
                    endforeach;
                else: 
                ?>
                    <tr><td colspan="3" style="text-align: center; color: #999;">No progress data available</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="page-break-before: always;"></div>

    <div class="section">
        <h2>Attendance Record</h2>
        <div style="margin-bottom: 10px;">
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
                    <?php foreach (array_slice($attendance, 0, 15) as $record): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($record->session_date)); ?></td>
                            <td class="status-<?php echo $record->status; ?>"><?php echo ucfirst($record->status); ?></td>
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
        <h2>Documents & Coach Sessions</h2>
        <table>
            <thead>
                <tr>
                    <th>Document Type</th>
                    <th>Upload Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($documents): ?>
                    <?php foreach (array_slice($documents, 0, 10) as $doc): ?>
                        <tr>
                            <td><?php echo esc_html(ucwords(str_replace('_', ' ', $doc->document_type))); ?></td>
                            <td><?php echo date('M j, Y', strtotime($doc->upload_date)); ?></td>
                            <td><?php echo ucfirst($doc->approval_status); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align: center; color: #999;">No documents uploaded</td></tr>
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
