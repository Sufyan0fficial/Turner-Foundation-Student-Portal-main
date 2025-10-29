<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$challenges_table = $wpdb->prefix . 'tfsp_challenges';

// Create challenges table
$wpdb->query("CREATE TABLE IF NOT EXISTS $challenges_table (
    id int(11) NOT NULL AUTO_INCREMENT,
    title varchar(255) NOT NULL,
    description text,
    grade_level enum('sophomore','junior','senior','all') DEFAULT 'all',
    difficulty enum('easy','medium','hard') DEFAULT 'medium',
    category varchar(100),
    points int(11) DEFAULT 10,
    is_active tinyint(1) DEFAULT 1,
    order_index int(11) DEFAULT 0,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_grade_level (grade_level),
    INDEX idx_active (is_active),
    INDEX idx_order (order_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Handle form submissions
if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'manage_challenges')) {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }
    
    $action = sanitize_text_field($_POST['action']);
    
    if ($action === 'add_challenge') {
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $grade_level = sanitize_text_field($_POST['grade_level']);
        $difficulty = sanitize_text_field($_POST['difficulty']);
        $category = sanitize_text_field($_POST['category']);
        $points = intval($_POST['points']);
        
        if (!empty($title) && in_array($grade_level, ['sophomore','junior','senior','all']) && in_array($difficulty, ['easy','medium','hard'])) {
            $wpdb->insert($challenges_table, array(
                'title' => $title,
                'description' => $description,
                'grade_level' => $grade_level,
                'difficulty' => $difficulty,
                'category' => $category,
                'points' => $points
            ), array('%s', '%s', '%s', '%s', '%s', '%d'));
            
            echo '<div class="notice notice-success"><p>Challenge added successfully!</p></div>';
        }
    } elseif ($action === 'update_challenge') {
        $challenge_id = intval($_POST['challenge_id']);
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $grade_level = sanitize_text_field($_POST['grade_level']);
        $difficulty = sanitize_text_field($_POST['difficulty']);
        $category = sanitize_text_field($_POST['category']);
        $points = intval($_POST['points']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $wpdb->update($challenges_table, array(
            'title' => $title,
            'description' => $description,
            'grade_level' => $grade_level,
            'difficulty' => $difficulty,
            'category' => $category,
            'points' => $points,
            'is_active' => $is_active
        ), array('id' => $challenge_id), array('%s', '%s', '%s', '%s', '%s', '%d', '%d'), array('%d'));
        
        echo '<div class="notice notice-success"><p>Challenge updated successfully!</p></div>';
    } elseif ($action === 'delete_challenge') {
        $challenge_id = intval($_POST['challenge_id']);
        $wpdb->delete($challenges_table, array('id' => $challenge_id), array('%d'));
        echo '<div class="notice notice-success"><p>Challenge deleted successfully!</p></div>';
    }
}

// Get all challenges
$challenges = $wpdb->get_results("SELECT * FROM $challenges_table ORDER BY order_index ASC, created_at DESC");
?>

<style>
.challenges-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin: 20px 0;
}

.challenge-item {
    border-bottom: 1px solid #eee;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.challenge-item:last-child {
    border-bottom: none;
}

.challenge-info {
    flex: 1;
}

.challenge-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #000;
}

.challenge-meta {
    display: flex;
    gap: 12px;
    margin-bottom: 8px;
}

.challenge-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}

.badge-sophomore { background: #e3f2fd; color: #1565c0; }
.badge-junior { background: #fff3e0; color: #ef6c00; }
.badge-senior { background: #fce4ec; color: #c2185b; }
.badge-all { background: #f3e5f5; color: #7b1fa2; }

.badge-easy { background: #e8f5e8; color: #2e7d32; }
.badge-medium { background: #fff8e1; color: #f57c00; }
.badge-hard { background: #ffebee; color: #d32f2f; }

.challenge-actions {
    display: flex;
    gap: 8px;
}

.challenge-form {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 4px;
    font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: inherit;
}

.points-display {
    background: #8BC34A;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}
</style>

<div class="wrap">
    <h1>🎯 Challenge Management</h1>
    <p>Create and manage challenges with difficulty adjusted by grade level</p>

    <!-- Add New Challenge Form -->
    <div class="challenge-form">
        <h3>Add New Challenge</h3>
        <form method="post">
            <?php wp_nonce_field('manage_challenges'); ?>
            <input type="hidden" name="action" value="add_challenge">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Challenge Title *</label>
                    <input type="text" name="title" required maxlength="255">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" placeholder="e.g., Academic, Financial, Personal">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Grade Level</label>
                    <select name="grade_level">
                        <option value="all">All Grades</option>
                        <option value="sophomore">Sophomore</option>
                        <option value="junior">Junior</option>
                        <option value="senior">Senior</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Difficulty</label>
                    <select name="difficulty">
                        <option value="easy">Easy</option>
                        <option value="medium">Medium</option>
                        <option value="hard">Hard</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Points</label>
                    <input type="number" name="points" value="10" min="1" max="100">
                </div>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Describe the challenge and what students need to accomplish..."></textarea>
            </div>
            
            <button type="submit" class="button button-primary">Add Challenge</button>
        </form>
    </div>

    <!-- Existing Challenges -->
    <div class="challenges-container">
        <h3 style="padding: 20px 20px 0;">Current Challenges (<?php echo count($challenges); ?>)</h3>
        
        <?php if (empty($challenges)): ?>
            <div style="padding: 40px; text-align: center; color: #666;">
                <p>No challenges created yet. Add your first challenge above!</p>
            </div>
        <?php else: ?>
            <?php foreach ($challenges as $challenge): ?>
                <div class="challenge-item">
                    <div class="challenge-info">
                        <div class="challenge-title"><?php echo esc_html($challenge->title); ?></div>
                        <div class="challenge-meta">
                            <span class="challenge-badge badge-<?php echo $challenge->grade_level; ?>">
                                <?php echo ucfirst($challenge->grade_level); ?>
                            </span>
                            <span class="challenge-badge badge-<?php echo $challenge->difficulty; ?>">
                                <?php echo ucfirst($challenge->difficulty); ?>
                            </span>
                            <?php if ($challenge->category): ?>
                                <span class="challenge-badge" style="background: #e9ecef; color: #495057;">
                                    <?php echo esc_html($challenge->category); ?>
                                </span>
                            <?php endif; ?>
                            <span class="points-display"><?php echo $challenge->points; ?> pts</span>
                        </div>
                        <?php if ($challenge->description): ?>
                            <p style="margin: 0; color: #666; font-size: 14px;">
                                <?php echo esc_html($challenge->description); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="challenge-actions">
                        <button class="button" onclick="editChallenge(<?php echo $challenge->id; ?>)">Edit</button>
                        <form method="post" style="display: inline;" onsubmit="return confirm('Delete this challenge?')">
                            <?php wp_nonce_field('manage_challenges'); ?>
                            <input type="hidden" name="action" value="delete_challenge">
                            <input type="hidden" name="challenge_id" value="<?php echo $challenge->id; ?>">
                            <button type="submit" class="button button-link-delete">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function editChallenge(challengeId) {
    // In a real implementation, this would open an edit modal or redirect to edit page
    alert('Edit functionality would open a modal or redirect to edit page for challenge ID: ' + challengeId);
}
</script>
