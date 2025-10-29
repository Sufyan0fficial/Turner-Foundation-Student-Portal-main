<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_challenges_section($user_id) {
    global $wpdb;
    
    // Get active challenges
    $challenges = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tfsp_challenges WHERE is_active = 1 ORDER BY created_at DESC LIMIT 6");
    
    // Roadmap step to document type mapping
    $step_to_doc_type = array(
        'academic_resume' => 'resume',
        'personal_essay' => 'essay',
        'recommendation' => 'recommendation',
        'transcript' => 'transcript',
        'financial_aid' => 'financial_aid',
        'fafsa' => 'fafsa'
    );
    
    ?>
    
    <div class="challenges-section">
        <h3>🎯 Your Challenges</h3>
        <p>Complete these challenges to boost your college application success</p>
        
        <div class="challenges-grid">
            <?php if (empty($challenges)): ?>
                <div class="empty-state">
                    <p>No active challenges yet. Your advisor will assign challenges soon!</p>
                </div>
            <?php else: ?>
                <?php foreach ($challenges as $challenge): 
                    // Calculate actual progress based on roadmap step
                    $progress = 0;
                    
                    if ($challenge->roadmap_step) {
                        // Check if student has completed this roadmap step
                        $step_progress = $wpdb->get_var($wpdb->prepare(
                            "SELECT status FROM {$wpdb->prefix}tfsp_student_progress 
                            WHERE student_id = %d AND step = %s",
                            $user_id, $challenge->roadmap_step
                        ));
                        
                        if ($step_progress === 'completed') {
                            $progress = 100;
                        } elseif ($step_progress === 'in_progress') {
                            $progress = 50;
                        }
                        
                        // Also check for related document uploads
                        if (isset($step_to_doc_type[$challenge->roadmap_step])) {
                            $doc_type = $step_to_doc_type[$challenge->roadmap_step];
                            $has_document = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_documents 
                                WHERE user_id = %d AND document_type = %s",
                                $user_id, $doc_type
                            ));
                            
                            if ($has_document > 0 && $progress < 100) {
                                $progress = max($progress, 75); // At least 75% if document uploaded
                            }
                        }
                    }
                    
                    // Determine status color
                    $status_class = 'status-pending';
                    if ($progress >= 100) {
                        $status_class = 'status-complete';
                    } elseif ($progress >= 50) {
                        $status_class = 'status-progress';
                    }
                ?>
                    <div class="challenge-card <?php echo $status_class; ?>">
                        <div class="challenge-header">
                            <h4><?php echo esc_html($challenge->title); ?></h4>
                            <?php if ($challenge->difficulty): ?>
                                <span class="difficulty-badge"><?php echo esc_html(ucfirst($challenge->difficulty)); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($challenge->description): ?>
                            <p><?php echo esc_html($challenge->description); ?></p>
                        <?php endif; ?>
                        
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                        </div>
                        <div class="progress-footer">
                            <span class="progress-text"><?php echo $progress; ?>% Complete</span>
                            <?php if ($progress >= 100): ?>
                                <span class="status-icon">✓</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($challenge->roadmap_step && $progress < 100): ?>
                            <div class="challenge-action">
                                <a href="#<?php echo $challenge->roadmap_step; ?>" class="btn-small">View in Roadmap</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
    .challenges-section {
        background: white;
        padding: 30px;
        border-radius: 16px;
        margin: 30px 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .challenges-section h3 {
        margin: 0 0 8px 0;
        color: #2d5016;
        font-size: 24px;
        font-weight: 700;
    }
    
    .challenges-section > p {
        margin: 0 0 25px 0;
        color: #666;
        font-size: 14px;
    }
    
    .challenges-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }
    
    .challenge-card {
        background: #f9fafb;
        padding: 20px;
        border-radius: 12px;
        border: 2px solid #e5e7eb;
        transition: all 0.3s;
    }
    
    .challenge-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .challenge-card.status-complete {
        border-color: #10b981;
        background: #f0fdf4;
    }
    
    .challenge-card.status-progress {
        border-color: #f59e0b;
        background: #fffbeb;
    }
    
    .challenge-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
        gap: 10px;
    }
    
    .challenge-card h4 {
        margin: 0;
        color: #1f2937;
        font-size: 17px;
        font-weight: 600;
        flex: 1;
    }
    
    .difficulty-badge {
        background: #dbeafe;
        color: #1e40af;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        white-space: nowrap;
    }
    
    .challenge-card p {
        margin: 0 0 15px 0;
        color: #6b7280;
        font-size: 14px;
        line-height: 1.5;
    }
    
    .progress-bar {
        height: 8px;
        background: #e5e7eb;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 8px;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #8ebb79 0%, #7CB342 100%);
        transition: width 0.5s ease;
    }
    
    .status-complete .progress-fill {
        background: linear-gradient(90deg, #10b981 0%, #059669 100%);
    }
    
    .status-progress .progress-fill {
        background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
    }
    
    .progress-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .progress-text {
        font-size: 13px;
        color: #6b7280;
        font-weight: 500;
    }
    
    .status-icon {
        color: #10b981;
        font-size: 18px;
        font-weight: bold;
    }
    
    .challenge-action {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #e5e7eb;
    }
    
    .btn-small {
        display: inline-block;
        padding: 6px 14px;
        background: #8ebb79;
        color: white;
        text-decoration: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .btn-small:hover {
        background: #7CB342;
        transform: translateY(-1px);
    }
    
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #9ca3af;
        grid-column: 1 / -1;
    }
    
    @media (max-width: 768px) {
        .challenges-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
    
    <?php
}
?>
