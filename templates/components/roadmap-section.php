<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_roadmap_section($user_id) {
    $roadmap_items = array(
        array('title' => 'Academic Resume', 'description' => 'Build your academic achievements portfolio', 'color' => '#9c27b0'),
        array('title' => 'Personal Essay', 'description' => 'Write your compelling personal story', 'color' => '#e91e63'),
        array('title' => 'Recommendation Letters', 'description' => 'Request letters from teachers/mentors', 'color' => '#f44336'),
        array('title' => 'Transcript', 'description' => 'Official academic records', 'color' => '#ff9800'),
        array('title' => 'Financial Aid', 'description' => 'Apply for scholarships and grants', 'color' => '#009688'),
        array('title' => 'Community Service', 'description' => 'Document volunteer activities', 'color' => '#2196f3'),
        array('title' => 'Create Interest List of Colleges', 'description' => 'Research and list target schools', 'color' => '#673ab7'),
        array('title' => 'College Tours', 'description' => 'Visit campuses and attend info sessions', 'color' => '#ff5722'),
        array('title' => 'FAFSA', 'description' => 'Complete federal financial aid application', 'color' => '#1a237e')
    );
    
    $completed = 6; // Dynamic from database
    $remaining = count($roadmap_items) - $completed;
    ?>
    
    <div class="roadmap-section">
        <div class="roadmap-header">
            <h3>🗺️ Your College Application Roadmap</h3>
            <div class="roadmap-progress">
                <span class="completed"><?php echo $completed; ?> Completed</span> | 
                <span class="remaining"><?php echo $remaining; ?> Remaining</span>
            </div>
        </div>
        
        <div class="roadmap-grid">
            <?php foreach ($roadmap_items as $index => $item): ?>
                <?php 
                $is_completed = $index < $completed;
                $status = $is_completed ? 'completed' : ($index == $completed ? 'in-progress' : 'pending');
                ?>
                <div class="roadmap-card <?php echo $status; ?>" style="--card-color: <?php echo $item['color']; ?>" data-original-number="<?php echo $index + 1; ?>">
                    <div class="card-number"><?php echo $is_completed ? '' : ($index + 1); ?></div>
                    <div class="card-content">
                        <h4><?php echo $item['title']; ?></h4>
                        <p><?php echo $item['description']; ?></p>
                        
                        <?php if ($status === 'completed'): ?>
                            <button class="status-btn completed" disabled>✓ Complete</button>
                        <?php elseif ($status === 'in-progress'): ?>
                            <button class="status-btn in-progress" onclick="markComplete('<?php echo sanitize_title($item['title']); ?>', <?php echo $user_id; ?>)">Mark Complete</button>
                        <?php else: ?>
                            <button class="status-btn pending" onclick="startStep('<?php echo sanitize_title($item['title']); ?>', <?php echo $user_id; ?>)">Start</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <style>
    .roadmap-section {
        background: white;
        padding: 30px;
        border-radius: 16px;
        margin: 30px 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .roadmap-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    .roadmap-header h3 {
        margin: 0;
        color: #2d5016;
        font-size: 24px;
        font-weight: 700;
    }
    .roadmap-progress {
        font-size: 14px;
        font-weight: 600;
    }
    .completed { color: #4caf50; }
    .remaining { color: #ff9800; }
    
    .roadmap-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 20px;
    }
    .roadmap-card {
        background: white;
        border: 2px solid var(--card-color);
        border-radius: 12px;
        padding: 20px;
        position: relative;
        transition: all 0.3s;
    }
    .roadmap-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }
    .card-number {
        position: absolute;
        top: -10px;
        left: 20px;
        background: var(--card-color);
        color: white;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    .roadmap-card.completed .card-number {
        background: #4caf50 !important;
        color: white;
    }
    .roadmap-card.completed .card-number::before {
        content: '✓';
        font-size: 16px;
        font-weight: bold;
    }
    .card-content {
        margin-top: 10px;
    }
    .card-content h4 {
        margin: 0 0 8px 0;
        color: #333;
        font-size: 18px;
        font-weight: 600;
    }
    .card-content p {
        margin: 0 0 15px 0;
        color: #666;
        font-size: 14px;
        line-height: 1.4;
    }
    .status-btn {
        border: none;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .status-btn.completed {
        background: #4caf50;
        color: white;
    }
    .status-btn.in-progress {
        background: #ff9800;
        color: white;
    }
    .status-btn.pending {
        background: #e0e0e0;
        color: #666;
    }
    .detail-input {
        width: 100%;
        margin-top: 10px;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
    }
    .roadmap-card.completed {
        opacity: 0.8;
        background: #f8f9fa;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        window.startStep = function(step, userId) {
            if (!confirm('Start working on this step?')) return;
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                method: 'POST',
                data: {
                    action: 'tfsp_update_progress',
                    step: step,
                    status: 'in_progress',
                    user_id: userId,
                    nonce: '<?php echo wp_create_nonce('tfsp_progress'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Step started!');
                        location.reload();
                    } else {
                        alert('Error: ' + (response.data || 'Failed to update'));
                    }
                },
                error: function() {
                    alert('Connection error. Please try again.');
                }
            });
        };
        
        window.markComplete = function(step, userId) {
            if (!confirm('Mark this step as complete?')) return;
            
            // Find the button and card
            const button = event.target;
            const card = button.closest('.roadmap-card');
            const cardNumber = card.querySelector('.card-number');
            
            // Immediate visual feedback
            card.classList.add('completed');
            card.classList.remove('in-progress');
            button.textContent = '✓ Complete';
            button.className = 'status-btn completed';
            button.disabled = true;
            cardNumber.textContent = '';
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                method: 'POST',
                data: {
                    action: 'tfsp_update_progress',
                    step: step,
                    status: 'completed',
                    user_id: userId,
                    nonce: '<?php echo wp_create_nonce('tfsp_progress'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Success - keep the visual changes
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        // Revert changes on error
                        card.classList.remove('completed');
                        card.classList.add('in-progress');
                        button.textContent = 'Mark Complete';
                        button.className = 'status-btn in-progress';
                        button.disabled = false;
                        cardNumber.textContent = card.dataset.originalNumber;
                        alert('Error: ' + (response.data || 'Failed to update'));
                    }
                },
                error: function() {
                    // Revert changes on error
                    card.classList.remove('completed');
                    card.classList.add('in-progress');
                    button.textContent = 'Mark Complete';
                    button.className = 'status-btn in-progress';
                    button.disabled = false;
                    cardNumber.textContent = card.dataset.originalNumber;
                    alert('Connection error. Please try again.');
                }
            });
        };
    });
    </script>
    <?php
}
?>
