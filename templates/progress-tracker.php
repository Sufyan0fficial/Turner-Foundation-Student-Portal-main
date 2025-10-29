<?php
if (!defined('ABSPATH')) {
    exit;
}

// Enhanced progress tracking with visual roadmap
$progress_roadmap = array(
    array(
        'id' => 'academic_resume',
        'title' => 'Academic Resume',
        'description' => 'Create a comprehensive academic resume highlighting your achievements',
        'icon' => '📄',
        'category' => 'Documents',
        'estimated_time' => '2-3 hours',
        'difficulty' => 'Medium',
        'prerequisites' => array(),
        'resources' => array(
            'Resume Template' => '#',
            'Writing Guide' => '#',
            'Examples' => '#'
        )
    ),
    array(
        'id' => 'personal_essay',
        'title' => 'Personal Essay',
        'description' => 'Write a compelling personal statement that showcases your unique story',
        'icon' => '✍️',
        'category' => 'Writing',
        'estimated_time' => '4-6 hours',
        'difficulty' => 'Hard',
        'prerequisites' => array('academic_resume'),
        'resources' => array(
            'Essay Prompts' => '#',
            'Writing Tips' => '#',
            'Sample Essays' => '#'
        )
    ),
    array(
        'id' => 'recommendation_letters',
        'title' => 'Recommendation Letters',
        'description' => 'Request and coordinate recommendation letters from teachers and mentors',
        'icon' => '📝',
        'category' => 'Networking',
        'estimated_time' => '1-2 weeks',
        'difficulty' => 'Medium',
        'prerequisites' => array('academic_resume'),
        'resources' => array(
            'Request Template' => '#',
            'Guidelines' => '#'
        )
    ),
    array(
        'id' => 'college_list',
        'title' => 'College Interest List',
        'description' => 'Research and create a balanced list of target colleges',
        'icon' => '🏫',
        'category' => 'Research',
        'estimated_time' => '3-4 hours',
        'difficulty' => 'Medium',
        'prerequisites' => array(),
        'resources' => array(
            'College Search Tools' => '#',
            'Comparison Guide' => '#'
        )
    ),
    array(
        'id' => 'financial_aid',
        'title' => 'Financial Aid Research',
        'description' => 'Explore scholarships, grants, and financial aid opportunities',
        'icon' => '💰',
        'category' => 'Financial',
        'estimated_time' => '2-3 hours',
        'difficulty' => 'Medium',
        'prerequisites' => array('college_list'),
        'resources' => array(
            'Scholarship Database' => '#',
            'FAFSA Guide' => '#'
        )
    )
);

// Get user progress
global $wpdb;
$user_id = get_current_user_id();
$progress_table = $wpdb->prefix . 'tfsp_progress';
$user_progress = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $progress_table WHERE user_id = %d",
    $user_id
), OBJECT_K);
?>

<style>
.progress-roadmap {
    position: relative;
    padding: 20px 0;
}

.roadmap-timeline {
    position: absolute;
    left: 50%;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(to bottom, #8BC34A 0%, #8BC34A 100%);
    transform: translateX(-50%);
}

.roadmap-step {
    display: flex;
    align-items: center;
    margin-bottom: 40px;
    position: relative;
}

.roadmap-step:nth-child(even) {
    flex-direction: row-reverse;
}

.step-connector {
    position: absolute;
    left: 50%;
    width: 20px;
    height: 20px;
    background: #8BC34A;
    border: 4px solid white;
    border-radius: 50%;
    transform: translateX(-50%);
    z-index: 2;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.step-connector.completed {
    background: #10b981;
}

.step-connector.in-progress {
    background: #FFC107;
}

.step-connector.locked {
    background: #e5e7eb;
}

.step-content {
    flex: 1;
    max-width: 45%;
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.step-content:hover {
    border-color: #8BC34A;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.step-content.completed {
    border-color: #10b981;
    background: linear-gradient(135deg, #f0fff4 0%, #ffffff 100%);
}

.step-content.locked {
    opacity: 0.6;
    background: #f8f9fa;
}

.step-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.step-icon {
    font-size: 24px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f9ff;
    border-radius: 8px;
}

.step-title {
    font-size: 18px;
    font-weight: 600;
    color: #000;
    margin: 0;
}

.step-meta {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.meta-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}

.badge-category {
    background: #e3f2fd;
    color: #1565c0;
}

.badge-difficulty {
    background: #fff3e0;
    color: #ef6c00;
}

.badge-time {
    background: #f3e5f5;
    color: #7b1fa2;
}

.step-description {
    color: #666;
    margin-bottom: 16px;
    line-height: 1.5;
}

.step-prerequisites {
    margin-bottom: 16px;
}

.prerequisite-item {
    display: inline-block;
    padding: 4px 8px;
    background: #f8f9fa;
    border-radius: 6px;
    font-size: 12px;
    margin-right: 8px;
    margin-bottom: 4px;
}

.step-resources {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.resource-link {
    padding: 6px 12px;
    background: #8BC34A;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    transition: background 0.2s ease;
}

.resource-link:hover {
    background: #689F38;
    color: white;
}

.step-actions {
    margin-top: 16px;
    display: flex;
    gap: 8px;
}

.step-button {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-start {
    background: #8BC34A;
    color: white;
}

.btn-start:hover {
    background: #689F38;
}

.btn-continue {
    background: #FFC107;
    color: #000;
}

.btn-review {
    background: #10b981;
    color: white;
}

.progress-summary {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.progress-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.progress-stat {
    text-align: center;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-number {
    font-size: 24px;
    font-weight: 700;
    color: #8BC34A;
}

.stat-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

@media (max-width: 768px) {
    .roadmap-timeline {
        left: 30px;
    }
    
    .roadmap-step {
        flex-direction: row !important;
        padding-left: 60px;
    }
    
    .step-connector {
        left: 30px;
    }
    
    .step-content {
        max-width: 100%;
    }
}
</style>

<div class="progress-summary">
    <h3>📊 Your Progress Summary</h3>
    <div class="progress-stats">
        <div class="progress-stat">
            <div class="stat-number">3</div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="progress-stat">
            <div class="stat-number">1</div>
            <div class="stat-label">In Progress</div>
        </div>
        <div class="progress-stat">
            <div class="stat-number">1</div>
            <div class="stat-label">Remaining</div>
        </div>
        <div class="progress-stat">
            <div class="stat-number">75%</div>
            <div class="stat-label">Overall</div>
        </div>
    </div>
</div>

<div class="progress-roadmap">
    <div class="roadmap-timeline"></div>
    
    <?php foreach ($progress_roadmap as $index => $step): ?>
        <?php
        $step_status = 'not_started'; // Default status
        $is_locked = false;
        
        // Check prerequisites
        if (!empty($step['prerequisites'])) {
            foreach ($step['prerequisites'] as $prereq) {
                // In real implementation, check if prerequisite is completed
                // For demo, assume first 3 are completed
                if ($index > 2) {
                    $is_locked = true;
                    break;
                }
            }
        }
        
        // Mock status for demo
        if ($index < 3) {
            $step_status = 'completed';
        } elseif ($index === 3) {
            $step_status = 'in_progress';
        }
        ?>
        
        <div class="roadmap-step">
            <div class="step-connector <?php echo $step_status; ?> <?php echo $is_locked ? 'locked' : ''; ?>"></div>
            
            <div class="step-content <?php echo $step_status; ?> <?php echo $is_locked ? 'locked' : ''; ?>">
                <div class="step-header">
                    <div class="step-icon"><?php echo $step['icon']; ?></div>
                    <h4 class="step-title"><?php echo esc_html($step['title']); ?></h4>
                </div>
                
                <div class="step-meta">
                    <span class="meta-badge badge-category"><?php echo $step['category']; ?></span>
                    <span class="meta-badge badge-difficulty"><?php echo $step['difficulty']; ?></span>
                    <span class="meta-badge badge-time"><?php echo $step['estimated_time']; ?></span>
                </div>
                
                <p class="step-description"><?php echo esc_html($step['description']); ?></p>
                
                <?php if (!empty($step['prerequisites'])): ?>
                    <div class="step-prerequisites">
                        <strong>Prerequisites:</strong><br>
                        <?php foreach ($step['prerequisites'] as $prereq): ?>
                            <span class="prerequisite-item"><?php echo esc_html($prereq); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="step-resources">
                    <?php foreach ($step['resources'] as $name => $url): ?>
                        <a href="<?php echo esc_url($url); ?>" class="resource-link" target="_blank">
                            <?php echo esc_html($name); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="step-actions">
                    <?php if ($is_locked): ?>
                        <button class="step-button" disabled>🔒 Locked</button>
                    <?php elseif ($step_status === 'completed'): ?>
                        <button class="step-button btn-review">✅ Review</button>
                    <?php elseif ($step_status === 'in_progress'): ?>
                        <button class="step-button btn-continue">⏳ Continue</button>
                    <?php else: ?>
                        <button class="step-button btn-start">🚀 Start</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
