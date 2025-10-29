<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_challenge_management() {
    global $wpdb;
    $challenges = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tfsp_challenges ORDER BY target_year, difficulty");
    ?>
    
    <div class="section">
        <h2>🎯 Challenge Management</h2>
        <p>Create and manage challenges based on student year level</p>
        
        <div class="controls">
            <button class="btn" onclick="createNewChallenge()">Create New Challenge</button>
            <select id="year-filter">
                <option value="">All Years</option>
                <option value="sophomore">Sophomore</option>
                <option value="junior">Junior</option>
                <option value="senior">Senior</option>
            </select>
        </div>
        
        <?php if (empty($challenges)): ?>
            <div class="empty-state">
                <p>No challenges created yet. Click "Create New Challenge" to get started.</p>
            </div>
        <?php else: ?>
            <?php foreach ($challenges as $challenge): ?>
                <div class="challenge-item" data-year="<?php echo $challenge->target_year; ?>">
                    <h4><?php echo esc_html($challenge->title); ?></h4>
                    <p><?php echo esc_html($challenge->description); ?></p>
                    <div class="challenge-meta">
                        <span class="year-badge">Target: <?php echo ucfirst($challenge->target_year); ?></span>
                        <span class="difficulty-badge">Difficulty: <?php echo $challenge->difficulty; ?>/5</span>
                    </div>
                    <div style="margin-top: 10px;">
                        <button class="btn" onclick="editChallenge(<?php echo $challenge->id; ?>)">Edit</button>
                        <button class="btn" onclick="deleteChallenge(<?php echo $challenge->id; ?>)" style="background: #ef4444;">Delete</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}
?>
