<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_attendance_tracking() {
    $students = get_users(array('role' => 'subscriber'));
    
    // Get current week dates
    $current_week = array();
    $start = strtotime('monday this week');
    for ($i = 0; $i < 5; $i++) {
        $current_week[] = date('Y-m-d', strtotime("+$i days", $start));
    }
    
    global $wpdb;
    ?>
    
    <div class="section">
        <h2>📊 Attendance Tracking</h2>
        <p><strong>Admin-level only function</strong> - Spreadsheet-style interface with students vertical, sessions horizontal</p>
        
        <div class="controls">
            <button class="btn" onclick="exportAttendance()">📥 Export Attendance Report</button>
            <select id="week-selector" onchange="changeWeek()">
                <option value="current">Current Week</option>
                <option value="previous">Previous Week</option>
                <option value="next">Next Week</option>
            </select>
            <button class="btn" onclick="calculateAllPercentages()">🔄 Recalculate All %</button>
        </div>
        
        <div class="attendance-spreadsheet">
            <table class="data-table attendance-table">
                <thead>
                    <tr>
                        <th class="student-header">Student</th>
                        <?php foreach ($current_week as $date): ?>
                            <th class="date-header">
                                <div class="date-display">
                                    <div class="day-name"><?php echo date('D', strtotime($date)); ?></div>
                                    <div class="date-number"><?php echo date('M j', strtotime($date)); ?></div>
                                </div>
                            </th>
                        <?php endforeach; ?>
                        <th class="percentage-header">Attendance %</th>
                        <th class="actions-header">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="<?php echo count($current_week) + 3; ?>" class="empty-state">
                                No students registered yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <tr class="student-row" data-student-id="<?php echo $student->ID; ?>">
                                <td class="student-cell">
                                    <div class="student-info">
                                        <div class="student-name"><?php echo $student->display_name; ?></div>
                                        <div class="student-email"><?php echo $student->user_email; ?></div>
                                    </div>
                                </td>
                                <?php foreach ($current_week as $date): ?>
                                    <td class="attendance-cell">
                                        <?php 
                                        $attendance = $wpdb->get_var($wpdb->prepare("
                                            SELECT status FROM {$wpdb->prefix}tfsp_attendance 
                                            WHERE student_id = %d AND date = %s
                                        ", $student->ID, $date));
                                        ?>
                                        <select class="attendance-status" data-date="<?php echo $date; ?>" onchange="updateAttendance(<?php echo $student->ID; ?>, '<?php echo $date; ?>', this.value)">
                                            <option value="">-</option>
                                            <option value="present" <?php selected($attendance, 'present'); ?>>✅ Present</option>
                                            <option value="excused" <?php selected($attendance, 'excused'); ?>>📋 Excused</option>
                                            <option value="absent" <?php selected($attendance, 'absent'); ?>>❌ Didn't Attend</option>
                                            <option value="postponed" <?php selected($attendance, 'postponed'); ?>>⏰ Postponed</option>
                                        </select>
                                    </td>
                                <?php endforeach; ?>
                                <td class="percentage-cell">
                                    <div class="percentage-display" id="percentage-<?php echo $student->ID; ?>">
                                        <?php 
                                        $total_sessions = $wpdb->get_var($wpdb->prepare("
                                            SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_attendance 
                                            WHERE student_id = %d AND status != ''
                                        ", $student->ID));
                                        $present_sessions = $wpdb->get_var($wpdb->prepare("
                                            SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_attendance 
                                            WHERE student_id = %d AND status = 'present'
                                        ", $student->ID));
                                        $percentage = $total_sessions > 0 ? round(($present_sessions / $total_sessions) * 100) : 0;
                                        ?>
                                        <div class="percentage-number <?php echo $percentage >= 80 ? 'good' : ($percentage >= 60 ? 'warning' : 'poor'); ?>">
                                            <?php echo $percentage; ?>%
                                        </div>
                                        <div class="session-count"><?php echo $present_sessions; ?>/<?php echo $total_sessions; ?></div>
                                    </div>
                                </td>
                                <td class="actions-cell">
                                    <button class="btn-small" onclick="viewStudentAttendance(<?php echo $student->ID; ?>)">📊 View</button>
                                    <button class="btn-small" onclick="addNote(<?php echo $student->ID; ?>)">📝 Note</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="attendance-summary">
            <div class="summary-stats">
                <div class="stat-item">
                    <div class="stat-number" id="total-students"><?php echo count($students); ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="avg-attendance">0%</div>
                    <div class="stat-label">Average Attendance</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="perfect-attendance">0</div>
                    <div class="stat-label">Perfect Attendance</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="at-risk">0</div>
                    <div class="stat-label">At Risk (&lt;60%)</div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .attendance-spreadsheet {
        overflow-x: auto;
        margin: 20px 0;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .attendance-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        min-width: 800px;
    }
    
    .attendance-table th {
        background: #2d5016;
        color: white;
        padding: 15px 10px;
        text-align: center;
        font-weight: 600;
        border-right: 1px solid rgba(255,255,255,0.2);
    }
    
    .student-header {
        min-width: 200px;
        text-align: left !important;
    }
    
    .date-header {
        min-width: 120px;
    }
    
    .date-display {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .day-name {
        font-size: 12px;
        opacity: 0.8;
        margin-bottom: 2px;
    }
    
    .date-number {
        font-size: 14px;
        font-weight: 700;
    }
    
    .percentage-header, .actions-header {
        min-width: 100px;
    }
    
    .student-row {
        border-bottom: 1px solid #e5e7eb;
        transition: background-color 0.3s;
    }
    
    .student-row:hover {
        background-color: #f8f9fa;
    }
    
    .student-cell {
        padding: 15px;
        border-right: 1px solid #e5e7eb;
    }
    
    .student-info {
        display: flex;
        flex-direction: column;
    }
    
    .student-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 2px;
    }
    
    .student-email {
        font-size: 12px;
        color: #666;
    }
    
    .attendance-cell {
        padding: 8px;
        text-align: center;
        border-right: 1px solid #e5e7eb;
    }
    
    .attendance-status {
        width: 100%;
        padding: 6px 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 12px;
        background: white;
        cursor: pointer;
    }
    
    .attendance-status option {
        padding: 5px;
    }
    
    .percentage-cell {
        padding: 15px;
        text-align: center;
        border-right: 1px solid #e5e7eb;
    }
    
    .percentage-display {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .percentage-number {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 2px;
    }
    
    .percentage-number.good {
        color: #4caf50;
    }
    
    .percentage-number.warning {
        color: #ff9800;
    }
    
    .percentage-number.poor {
        color: #f44336;
    }
    
    .session-count {
        font-size: 11px;
        color: #666;
    }
    
    .actions-cell {
        padding: 15px 8px;
        text-align: center;
    }
    
    .btn-small {
        background: #8BC34A;
        color: white;
        border: none;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        cursor: pointer;
        margin: 2px;
        transition: background 0.3s;
    }
    
    .btn-small:hover {
        background: #7CB342;
    }
    
    .attendance-summary {
        margin-top: 30px;
        padding: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .summary-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 20px;
    }
    
    .stat-item {
        text-align: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .stat-number {
        font-size: 24px;
        font-weight: 700;
        color: #2d5016;
        margin-bottom: 5px;
    }
    
    .stat-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #666;
        font-style: italic;
    }
    </style>
    
    <script>
    function updateAttendance(studentId, date, status) {
        jQuery.post(ajaxurl, {
            action: 'update_attendance',
            student_id: studentId,
            date: date,
            status: status,
            nonce: '<?php echo wp_create_nonce('attendance_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                calculateStudentPercentage(studentId);
                updateSummaryStats();
            } else {
                alert('Error updating attendance: ' + response.data);
            }
        });
    }
    
    function calculateStudentPercentage(studentId) {
        const row = document.querySelector(`tr[data-student-id="${studentId}"]`);
        const selects = row.querySelectorAll('.attendance-status');
        
        let total = 0;
        let present = 0;
        
        selects.forEach(select => {
            if (select.value) {
                total++;
                if (select.value === 'present') {
                    present++;
                }
            }
        });
        
        const percentage = total > 0 ? Math.round((present / total) * 100) : 0;
        const percentageElement = document.getElementById(`percentage-${studentId}`);
        
        percentageElement.querySelector('.percentage-number').textContent = percentage + '%';
        percentageElement.querySelector('.session-count').textContent = `${present}/${total}`;
        
        // Update color class
        const numberElement = percentageElement.querySelector('.percentage-number');
        numberElement.className = 'percentage-number ' + 
            (percentage >= 80 ? 'good' : (percentage >= 60 ? 'warning' : 'poor'));
    }
    
    function calculateAllPercentages() {
        document.querySelectorAll('.student-row').forEach(row => {
            const studentId = row.dataset.studentId;
            calculateStudentPercentage(studentId);
        });
        updateSummaryStats();
    }
    
    function updateSummaryStats() {
        const percentageElements = document.querySelectorAll('.percentage-number');
        let totalPercentage = 0;
        let perfectCount = 0;
        let atRiskCount = 0;
        let studentCount = 0;
        
        percentageElements.forEach(element => {
            const percentage = parseInt(element.textContent);
            totalPercentage += percentage;
            studentCount++;
            
            if (percentage === 100) perfectCount++;
            if (percentage < 60) atRiskCount++;
        });
        
        const avgPercentage = studentCount > 0 ? Math.round(totalPercentage / studentCount) : 0;
        
        document.getElementById('avg-attendance').textContent = avgPercentage + '%';
        document.getElementById('perfect-attendance').textContent = perfectCount;
        document.getElementById('at-risk').textContent = atRiskCount;
    }
    
    function exportAttendance() {
        window.location.href = ajaxurl + '?action=export_attendance&nonce=<?php echo wp_create_nonce('export_nonce'); ?>';
    }
    
    function changeWeek() {
        const weekSelector = document.getElementById('week-selector');
        // In real implementation, this would reload the table with different week data
        alert('Week change functionality - would reload table with ' + weekSelector.value + ' week data');
    }
    
    function viewStudentAttendance(studentId) {
        // Open detailed view for student
        window.open(`?view=student-detail&student_id=${studentId}`, '_blank');
    }
    
    function addNote(studentId) {
        const note = prompt('Add attendance note for this student:');
        if (note) {
            jQuery.post(ajaxurl, {
                action: 'add_attendance_note',
                student_id: studentId,
                note: note,
                nonce: '<?php echo wp_create_nonce('attendance_note_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('Note added successfully');
                } else {
                    alert('Error adding note');
                }
            });
        }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        calculateAllPercentages();
    });
    </script>
    <?php
}
?>
