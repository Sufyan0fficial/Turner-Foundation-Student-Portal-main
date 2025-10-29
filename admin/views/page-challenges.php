<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$challenges_table = $wpdb->prefix . 'tfsp_challenges';

// Handle form submissions
if (isset($_POST['add_challenge']) && check_admin_referer('manage_challenges', 'challenge_nonce')) {
    $result = $wpdb->insert($challenges_table, array(
        'title' => sanitize_text_field($_POST['title']),
        'description' => sanitize_textarea_field($_POST['description']),
        'difficulty' => sanitize_text_field($_POST['difficulty']),
        'roadmap_step' => sanitize_text_field($_POST['roadmap_step']),
        'target_percentage' => intval($_POST['target_percentage']),
        'is_active' => 1,
        'created_at' => current_time('mysql')
    ));
    
    if ($result) {
        echo '<div class="notice notice-success"><p>✓ Challenge added successfully!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>✗ Error: ' . $wpdb->last_error . '</p></div>';
    }
}

if (isset($_POST['delete_challenge']) && check_admin_referer('manage_challenges', 'challenge_nonce')) {
    $wpdb->delete($challenges_table, array('id' => intval($_POST['challenge_id'])));
    echo '<div class="notice notice-success"><p>✓ Challenge deleted!</p></div>';
}

// Get all challenges
$challenges = $wpdb->get_results("SELECT * FROM $challenges_table ORDER BY created_at DESC");

$roadmap_steps = array(
    'academic_resume' => 'Academic Resume',
    'personal_essay' => 'Personal Essay', 
    'recommendation' => 'Recommendation Letters',
    'transcript' => 'Transcript',
    'financial_aid' => 'Financial Aid',
    'community_service' => 'Community Service',
    'college_list' => 'College List',
    'college_tours' => 'College Tours',
    'fafsa' => 'FAFSA',
    'college_admissions_tests' => 'Admission Tests'
);
?>

<div class="section">
    <h2>🎯 Challenge Management</h2>
    <p>Create challenges linked to roadmap steps. Progress updates automatically when students complete tasks.</p>
    
    <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin: 0 0 15px;">Add New Challenge</h3>
        <form method="post">
            <?php wp_nonce_field('manage_challenges', 'challenge_nonce'); ?>
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Challenge Title *</label>
                    <input type="text" name="title" required placeholder="e.g., Complete Academic Resume">
                </div>
                <div class="form-group">
                    <label>Difficulty Level *</label>
                    <select name="difficulty" required>
                        <option value="agnostic">Grade-level agnostic</option>
                        <option value="freshman">Freshman Pace</option>
                        <option value="sophomore">Sophomore Pace</option>
                        <option value="junior">Junior Pace</option>
                        <option value="senior">Senior Pace</option>
                    </select>
                </div>
            </div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Linked Roadmap Step</label>
                    <select name="roadmap_step">
                        <option value="">-- No Link --</option>
                        <?php foreach ($roadmap_steps as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Target Percentage</label>
                    <input type="number" name="target_percentage" value="100" min="0" max="100">
                </div>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Describe what students need to accomplish"></textarea>
            </div>
            
            <button type="submit" name="add_challenge" class="btn">Add Challenge</button>
        </form>
    </div>
    
    <h3 style="margin: 30px 0 15px;">Active Challenges (<?php echo count($challenges); ?>)</h3>
    
    <?php if (empty($challenges)): ?>
        <div class="empty-state">
            <h3>No Challenges Yet</h3>
            <p>Create your first challenge above</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Difficulty</th>
                        <th>Roadmap Link</th>
                        <th>Target</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($challenges as $challenge): ?>
                        <tr>
                            <td data-label="Title"><strong><?php echo esc_html($challenge->title); ?></strong></td>
                            <td data-label="Description"><?php echo esc_html($challenge->description); ?></td>
                            <td data-label="Difficulty"><span class="badge badge-year"><?php echo ucfirst($challenge->difficulty); ?></span></td>
                            <td data-label="Roadmap Link">
                                <?php if ($challenge->roadmap_step): ?>
                                    <span class="badge badge-difficulty"><?php echo $roadmap_steps[$challenge->roadmap_step] ?? 'Unknown'; ?></span>
                                <?php else: ?>
                                    <span style="color: #9ca3af;">No Link</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Target"><?php echo $challenge->target_percentage ?? 100; ?>%</td>
                            <td data-label="Actions">
                                <form method="post" style="display: inline;" onsubmit="return confirm('Delete this challenge?')">
                                    <?php wp_nonce_field('manage_challenges', 'challenge_nonce'); ?>
                                    <input type="hidden" name="challenge_id" value="<?php echo $challenge->id; ?>">
                                    <button type="submit" name="delete_challenge" class="action-btn action-btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
