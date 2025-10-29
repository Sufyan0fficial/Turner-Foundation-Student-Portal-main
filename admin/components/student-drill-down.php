<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_student_drill_down($student_id) {
    global $wpdb;
    
    $student = get_userdata($student_id);
    if (!$student) {
        echo '<div class="notice notice-error"><p>Student not found.</p></div>';
        return;
    }
    
    // Get student info from tfsp_students table
    $student_info = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tfsp_students WHERE user_id = %d",
        $student_id
    ));
    
    // Get progress
    $progress = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d",
        $student_id
    ));
    
    // Organize progress by step_key
    $progress_by_step = array();
    foreach ($progress as $p) {
        $progress_by_step[$p->step_key] = $p;
    }
    
    // Get documents
    $documents = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tfsp_documents WHERE user_id = %d ORDER BY upload_date DESC",
        $student_id
    ));
    
    // Get attendance
    $attendance_records = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tfsp_attendance_records WHERE student_id = %d ORDER BY session_date DESC LIMIT 10",
        $student_id
    ));
    
    $total_attendance = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_attendance_records WHERE student_id = %d",
        $student_id
    ));
    
    $present_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_attendance_records WHERE student_id = %d AND status = 'present'",
        $student_id
    ));
    
    $attendance_percentage = $total_attendance > 0 ? round(($present_count / $total_attendance) * 100) : 0;
    
    // Get coach sessions
    $coach_sessions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tfsp_coach_sessions WHERE student_id = %d ORDER BY session_date DESC LIMIT 10",
        $student_id
    ));
    
    $total_coach_sessions = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_coach_sessions WHERE student_id = %d",
        $student_id
    ));
    
    $completed_coach_sessions = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_coach_sessions WHERE student_id = %d AND status IN ('completed', 'attended')",
        $student_id
    ));
    
    $coach_attendance_percentage = $total_coach_sessions > 0 ? round(($completed_coach_sessions / $total_coach_sessions) * 100) : 0;
    
    // Roadmap steps
    $roadmap_steps = array(
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
    
    $completed_steps = array_filter($progress, function($p) { return $p->status === 'completed'; });
    $progress_percentage = count($roadmap_steps) > 0 ? round((count($completed_steps) / count($roadmap_steps)) * 100) : 0;
    ?>
    
    <style>
    .drill-header {
        background: linear-gradient(135deg, #3f5340 0%, #8ebb79 100%);
        color: white;
        padding: 24px;
        border-radius: 12px;
        margin-bottom: 24px;
    }
    .drill-header h2 {
        margin: 0 0 8px;
        font-size: 24px;
    }
    .drill-header .back-btn {
        background: rgba(255,255,255,0.2);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        margin-bottom: 12px;
    }
    .drill-header .back-btn:hover {
        background: rgba(255,255,255,0.3);
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .stat-card h4 {
        margin: 0 0 8px;
        font-size: 13px;
        color: #6b7280;
        text-transform: uppercase;
    }
    .stat-card .number {
        font-size: 32px;
        font-weight: bold;
        color: #8ebb79;
    }
    .drill-section {
        background: white;
        padding: 24px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 24px;
    }
    .drill-section h3 {
        margin: 0 0 20px;
        font-size: 18px;
        color: #1f2937;
    }
    .roadmap-list {
        display: grid;
        gap: 12px;
    }
    .roadmap-item {
        display: flex;
        align-items: center;
        padding: 16px;
        background: #f9fafb;
        border-radius: 8px;
        border-left: 4px solid #e5e7eb;
    }
    .roadmap-item.completed {
        border-left-color: #10b981;
        background: #f0fdf4;
    }
    .roadmap-item.in_progress {
        border-left-color: #f59e0b;
        background: #fffbeb;
    }
    .roadmap-icon {
        font-size: 24px;
        margin-right: 16px;
    }
    .roadmap-info {
        flex: 1;
    }
    .roadmap-info h4 {
        margin: 0 0 4px;
        font-size: 15px;
        color: #1f2937;
    }
    .roadmap-status {
        font-size: 13px;
        color: #6b7280;
    }
    .doc-list {
        display: grid;
        gap: 12px;
    }
    .doc-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px;
        background: #f9fafb;
        border-radius: 8px;
    }
    .doc-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .stat-card {
            padding: 15px;
        }
        
        .stat-card .number {
            font-size: 24px;
        }
        
        .drill-section {
            padding: 15px;
        }
        
        .roadmap-item {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .doc-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .data-table {
            display: block;
            overflow-x: auto;
        }
        
        .data-table th,
        .data-table td {
            padding: 8px;
            font-size: 13px;
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .drill-header {
            padding: 15px;
        }
        
        .drill-header h2 {
            font-size: 20px;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-submitted {
            background: #e0f2fe;
            color: #0277bd;
        }
        
        .status-accepted {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-sent_back {
            background: #fef3c7;
            color: #92400e;
        }
    }
    </style>
    
    <div class="drill-header">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
            <button class="back-btn" onclick="window.location.href='?view=students'">← Back to Students</button>
            <a href="<?php echo plugin_dir_url(dirname(dirname(__FILE__))); ?>generate-student-report.php?student_id=<?php echo $student_id; ?>" 
               class="back-btn" style="background: #28a745; text-decoration: none; display: inline-block;">
                📥 Download Report
            </a>
        </div>
        <h2>📊 <?php echo esc_html($student->display_name); ?></h2>
        <p style="margin: 0; opacity: 0.9;"><?php echo esc_html($student->user_email); ?></p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h4>Roadmap Progress</h4>
            <div class="number"><?php echo $progress_percentage; ?>%</div>
            <p style="margin: 8px 0 0; font-size: 13px; color: #6b7280;">
                <?php echo count($completed_steps); ?> of <?php echo count($roadmap_steps); ?> completed
            </p>
        </div>
        <div class="stat-card">
            <h4>Attendance Rate</h4>
            <div class="number"><?php echo $attendance_percentage; ?>%</div>
            <p style="margin: 8px 0 0; font-size: 13px; color: #6b7280;">
                <?php echo $present_count; ?> of <?php echo $total_attendance; ?> sessions
            </p>
        </div>
        <div class="stat-card">
            <h4>Documents</h4>
            <div class="number"><?php echo count($documents); ?></div>
            <p style="margin: 8px 0 0; font-size: 13px; color: #6b7280;">
                Total uploaded
            </p>
        </div>
        <div class="stat-card">
            <h4>Coach Sessions</h4>
            <div class="number"><?php echo $coach_attendance_percentage; ?>%</div>
            <p style="margin: 8px 0 0; font-size: 13px; color: #6b7280;">
                <?php echo $completed_coach_sessions; ?> of <?php echo $total_coach_sessions; ?> completed
            </p>
        </div>
        <?php if ($student_info): ?>
        <div class="stat-card">
            <h4>Classification</h4>
            <div class="number" style="font-size: 20px;"><?php echo ucfirst($student_info->classification ?? 'N/A'); ?></div>
            <p style="margin: 8px 0 0; font-size: 13px; color: #6b7280;">
                Grade level
            </p>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($student_info): ?>
    <div class="drill-section">
        <h3>👤 Student Profile</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div>
                <h4 style="margin: 0 0 8px; font-size: 13px; color: #6b7280; text-transform: uppercase;">Student Contact</h4>
                <p style="margin: 0 0 4px;"><strong>Email:</strong> <?php echo esc_html($student_info->email); ?></p>
                <p style="margin: 0;"><strong>Phone:</strong> <?php echo esc_html($student_info->student_phone ?? 'N/A'); ?></p>
            </div>
            <div>
                <h4 style="margin: 0 0 8px; font-size: 13px; color: #6b7280; text-transform: uppercase;">Parent Contact</h4>
                <p style="margin: 0 0 4px;"><strong>Name:</strong> <?php echo esc_html($student_info->parent_name ?? 'N/A'); ?></p>
                <p style="margin: 0 0 4px;"><strong>Email:</strong> <?php echo esc_html($student_info->parent_email ?? 'N/A'); ?></p>
                <p style="margin: 0;"><strong>Phone:</strong> <?php echo esc_html($student_info->parent_phone ?? 'N/A'); ?></p>
            </div>
            <div>
                <h4 style="margin: 0 0 8px; font-size: 13px; color: #6b7280; text-transform: uppercase;">Apparel Sizes</h4>
                <p style="margin: 0 0 4px;"><strong>Shirt:</strong> <?php echo esc_html($student_info->shirt_size ?? 'N/A'); ?></p>
                <p style="margin: 0;"><strong>Blazer:</strong> <?php echo esc_html($student_info->blazer_size ?? 'N/A'); ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="drill-section">
        <h3>🗺️ Roadmap Progress</h3>
        <div class="roadmap-list">
            <?php foreach ($roadmap_steps as $step_key => $step_title): 
                $step_data = $progress_by_step[$step_key] ?? null;
                $step_status = $step_data ? $step_data->status : 'pending';
                $icon = $step_status === 'completed' ? '✅' : ($step_status === 'in_progress' ? '⏳' : '⭕');
            ?>
            <div class="roadmap-item <?php echo $step_status; ?>">
                <div class="roadmap-icon"><?php echo $icon; ?></div>
                <div class="roadmap-info">
                    <h4><?php echo esc_html($step_title); ?></h4>
                    <div class="roadmap-status">
                        Status: <strong><?php echo ucfirst(str_replace('_', ' ', $step_status)); ?></strong>
                        <?php if ($step_data && $step_data->updated_at): ?>
                            | Updated: <?php echo date('M j, Y', strtotime($step_data->updated_at)); ?>
                        <?php endif; ?>
                        
                        <?php 
                        // Display field data if available
                        if ($step_data && !empty($step_data->field_data)) {
                            $field_data = json_decode($step_data->field_data, true);
                            if ($field_data) {
                                echo '<div style="margin-top: 8px; padding: 8px; background: #f3f4f6; border-radius: 4px; font-size: 12px;">';
                                foreach ($field_data as $field_key => $field_value) {
                                    if (!empty($field_value)) {
                                        $field_label = ucwords(str_replace('_', ' ', $field_key));
                                        echo '<div><strong>' . esc_html($field_label) . ':</strong> ' . esc_html($field_value) . '</div>';
                                    }
                                }
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="drill-section">
        <h3>📄 Documents (<?php echo count($documents); ?>)</h3>
        <?php if (empty($documents)): ?>
            <p style="color: #9ca3af;">No documents uploaded yet</p>
        <?php else: ?>
            <div class="doc-list">
                <?php foreach ($documents as $doc): ?>
                <div class="doc-item">
                    <div class="doc-info">
                        <span style="font-size: 20px;">📄</span>
                        <div>
                            <strong><?php echo ucwords(str_replace('_', ' ', $doc->document_type)); ?></strong>
                            <div style="font-size: 12px; color: #6b7280;">
                                <?php echo date('M j, Y', strtotime($doc->upload_date)); ?>
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="status-badge status-<?php echo $doc->status; ?>">
                            <?php 
                            $status_labels = array(
                                'submitted' => 'Submitted',
                                'accepted' => 'Accepted', 
                                'sent_back' => 'Sent back for development'
                            );
                            echo $status_labels[$doc->status] ?? ucfirst($doc->status);
                            ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="drill-section">
        <h3>📅 Recent Attendance</h3>
        <?php if (empty($attendance_records)): ?>
            <p style="color: #9ca3af;">No attendance records yet</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_records as $record): ?>
                        <tr>
                            <td data-label="Date"><?php echo date('M j, Y', strtotime($record->session_date)); ?></td>
                            <td data-label="Status">
                                <span class="status-badge status-<?php echo $record->status; ?>">
                                    <?php echo ucfirst($record->status); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="drill-section">
        <h3>👥 One-on-One Coach Sessions</h3>
        <?php if (empty($coach_sessions)): ?>
            <p style="color: #9ca3af;">No coach sessions scheduled yet</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coach_sessions as $session): ?>
                        <tr>
                            <td data-label="Date"><?php echo date('M j, Y', strtotime($session->session_date)); ?></td>
                            <td data-label="Time"><?php echo $session->session_time ? date('g:i A', strtotime($session->session_time)) : 'TBD'; ?></td>
                            <td data-label="Status">
                                <span class="status-badge status-<?php echo $session->status; ?>">
                                    <?php echo ucfirst($session->status); ?>
                                </span>
                            </td>
                            <td data-label="Notes"><?php echo esc_html($session->notes ?: '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <?php
}
?>
