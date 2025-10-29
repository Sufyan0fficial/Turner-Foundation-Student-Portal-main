<?php
session_start();
if (!isset($_SESSION['tfsp_admin_id'])) {
    die('Unauthorized access');
}

require_once('../../../wp-load.php');

// Check if mPDF is available, if not use simple HTML to PDF conversion
if (!class_exists('Mpdf\Mpdf')) {
    // Fallback: Use DomPDF or simple HTML conversion
    require_once('generate-student-report-simple-pdf.php');
    exit;
}

use Mpdf\Mpdf;

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
    "SELECT * FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d ORDER BY created_at", $student_id
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

// Create PDF
$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 16,
    'margin_bottom' => 16,
    'margin_header' => 9,
    'margin_footer' => 9
]);

$mpdf->SetDisplayMode('fullpage');
$mpdf->SetTitle('Student Progress Report - ' . $student->display_name);
$mpdf->SetAuthor('YCAM Mentorship Program');

// CSS for PDF
$css = '
body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
.header { text-align: center; border-bottom: 3px solid #3f5340; padding-bottom: 15px; margin-bottom: 20px; }
.header h1 { margin: 0; color: #3f5340; font-size: 24px; }
.header h2 { margin: 5px 0; color: #666; font-size: 18px; }
.header p { margin: 5px 0; color: #666; font-size: 12px; }
.section { margin-bottom: 25px; page-break-inside: avoid; }
.section h2 { background: #3f5340; color: white; padding: 8px; margin: 0 0 10px; font-size: 14px; }
.info-grid { width: 100%; }
.info-row { width: 100%; margin-bottom: 8px; }
.info-item { display: inline-block; width: 48%; padding: 8px; background: #f8f9fa; border-left: 3px solid #3f5340; margin-right: 2%; }
.info-label { font-weight: bold; color: #3f5340; }
.stats-grid { width: 100%; margin-bottom: 15px; }
.stat-box { display: inline-block; width: 23%; text-align: center; padding: 12px; background: #f8f9fa; border-radius: 5px; margin-right: 2%; }
.stat-number { font-size: 20px; font-weight: bold; color: #3f5340; }
.stat-label { font-size: 10px; color: #666; }
table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 10px; }
th { background: #3f5340; color: white; padding: 6px; text-align: left; font-size: 10px; }
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
';

$mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
// HTML content for PDF
$html = '
<div class="header">
    <h1>YCAM Mentorship Program</h1>
    <h2>Student Progress Report</h2>
    <p>Generated: ' . date('F j, Y g:i A') . '</p>
</div>

<div class="section">
    <h2>Student Information</h2>
    <div class="info-grid">
        <div class="info-row">
            <div class="info-item">
                <div class="info-label">Name:</div>
                ' . esc_html($student->display_name) . '
            </div>
            <div class="info-item">
                <div class="info-label">Email:</div>
                ' . esc_html($student->user_email) . '
            </div>
        </div>
        <div class="info-row">
            <div class="info-item">
                <div class="info-label">Student Phone:</div>
                ' . esc_html($student_info->student_phone ?? 'N/A') . '
            </div>
            <div class="info-item">
                <div class="info-label">Classification:</div>
                ' . esc_html($student_info->classification ?? 'N/A') . '
            </div>
        </div>
        <div class="info-row">
            <div class="info-item">
                <div class="info-label">Parent Name:</div>
                ' . esc_html($student_info->parent_name ?? 'N/A') . '
            </div>
            <div class="info-item">
                <div class="info-label">Parent Email:</div>
                ' . esc_html($student_info->parent_email ?? 'N/A') . '
            </div>
        </div>
    </div>
</div>

<div class="section">
    <h2>Performance Summary</h2>
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-number">' . $progress_pct . '%</div>
            <div class="stat-label">Progress</div>
            <div style="font-size: 9px; color: #999;">' . $completed_steps . '/' . $total_steps . ' steps</div>
        </div>
        <div class="stat-box">
            <div class="stat-number">' . $attendance_pct . '%</div>
            <div class="stat-label">Attendance</div>
            <div style="font-size: 9px; color: #999;">' . $present . '/' . count($attendance) . ' present</div>
        </div>
        <div class="stat-box">
            <div class="stat-number">' . $sessions_attended . '</div>
            <div class="stat-label">Coach Sessions</div>
            <div style="font-size: 9px; color: #999;">' . $sessions_total . ' total</div>
        </div>
        <div class="stat-box">
            <div class="stat-number">' . count($documents) . '</div>
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
        <tbody>';

if ($progress) {
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
    
    $step_num = 1;
    foreach ($roadmap_steps as $key => $title) {
        $step_progress = array_filter($progress, fn($p) => $p->step_key === $key);
        $current_step = !empty($step_progress) ? reset($step_progress) : null;
        $status = $current_step ? $current_step->status : 'not_started';
        $notes = $current_step ? $current_step->notes : '';
        
        $html .= '<tr>
            <td>' . $step_num . '. ' . $title . '</td>
            <td class="status-' . $status . '">' . ucfirst(str_replace('_', ' ', $status)) . '</td>
            <td>' . esc_html($notes ?: '-') . '</td>
        </tr>';
        $step_num++;
    }
} else {
    $html .= '<tr><td colspan="3" style="text-align: center; color: #999;">No progress data available</td></tr>';
}

$html .= '</tbody>
    </table>
</div>';

$mpdf->WriteHTML($html);
$mpdf->AddPage();
// Page 2 content
$html2 = '
<div class="section">
    <h2>Attendance Record</h2>
    <div style="margin-bottom: 10px; font-size: 11px;">
        <strong>Summary:</strong> 
        Present: ' . $present . ' | 
        Absent: ' . $absent . ' | 
        Tardy: ' . $tardy . ' | 
        Excused: ' . $excused . '
    </div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>';

if ($attendance) {
    foreach (array_slice($attendance, 0, 15) as $record) {
        $html2 .= '<tr>
            <td>' . date('M j, Y', strtotime($record->session_date)) . '</td>
            <td class="status-' . $record->status . '">' . ucfirst($record->status) . '</td>
            <td>' . esc_html($record->notes ?: '-') . '</td>
        </tr>';
    }
} else {
    $html2 .= '<tr><td colspan="3" style="text-align: center; color: #999;">No attendance records</td></tr>';
}

$html2 .= '</tbody>
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
        <tbody>';

if ($coach_sessions) {
    foreach ($coach_sessions as $session) {
        $html2 .= '<tr>
            <td>' . date('M j, Y', strtotime($session->session_date)) . '</td>
            <td>' . ($session->session_time ? date('g:i A', strtotime($session->session_time)) : '-') . '</td>
            <td>' . ucfirst(str_replace('_', ' ', $session->status)) . '</td>
            <td>' . esc_html($session->notes ?: '-') . '</td>
        </tr>';
    }
} else {
    $html2 .= '<tr><td colspan="4" style="text-align: center; color: #999;">No coach sessions recorded</td></tr>';
}

$html2 .= '</tbody>
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
        <tbody>';

if ($documents) {
    foreach ($documents as $doc) {
        $html2 .= '<tr>
            <td>' . esc_html(ucwords(str_replace('_', ' ', $doc->document_type))) . '</td>
            <td>' . esc_html($doc->file_name) . '</td>
            <td>' . date('M j, Y', strtotime($doc->upload_date)) . '</td>
            <td>' . ucfirst($doc->approval_status) . '</td>
        </tr>';
    }
} else {
    $html2 .= '<tr><td colspan="4" style="text-align: center; color: #999;">No documents uploaded</td></tr>';
}

$html2 .= '</tbody>
    </table>
</div>

<div class="footer">
    <p><strong>YCAM Mentorship Program</strong></p>
    <p>This report is confidential and intended for program use only.</p>
</div>';

$mpdf->WriteHTML($html2);

// Output PDF
$filename = 'Student_Report_' . sanitize_file_name($student->display_name) . '_' . date('Y-m-d') . '.pdf';
$mpdf->Output($filename, 'D'); // D = Download
?>
