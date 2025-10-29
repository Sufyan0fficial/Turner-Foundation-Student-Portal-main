<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_dashboard_overview() {
    $students = get_users(array('role' => 'subscriber'));
    $total_students = count($students);
    
    global $wpdb;
    $total_challenges = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_challenges WHERE active = 1");
    $total_documents = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_documents");
    $unread_messages = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_messages WHERE status = 'unread'");
    ?>
    <div class="section">
        <h2>📊 Dashboard Overview</h2>
        <div class="dashboard-cards">
            <div class="card">
                <h3>👥 Total Students</h3>
                <div class="number"><?php echo $total_students; ?></div>
            </div>
            <div class="card">
                <h3>🎯 Active Challenges</h3>
                <div class="number"><?php echo $total_challenges; ?></div>
            </div>
            <div class="card">
                <h3>📄 Documents</h3>
                <div class="number"><?php echo $total_documents; ?></div>
            </div>
            <div class="card">
                <h3>💬 Unread Messages</h3>
                <div class="number"><?php echo $unread_messages; ?></div>
            </div>
        </div>
    </div>
    <?php
}
?>
