<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$messages_table = $wpdb->prefix . 'tfsp_messages';

// Create messages table
$wpdb->query("CREATE TABLE IF NOT EXISTS $messages_table (
    id int(11) NOT NULL AUTO_INCREMENT,
    student_id int(11) NOT NULL,
    message_type enum('coach','admin') NOT NULL,
    subject varchar(255) NOT NULL,
    message text NOT NULL,
    status enum('unread','read','replied') DEFAULT 'unread',
    priority enum('low','normal','high','urgent') DEFAULT 'normal',
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status),
    INDEX idx_type (message_type),
    INDEX idx_created (created_at),
    FOREIGN KEY (student_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Get messages
$messages = $wpdb->get_results("
    SELECT m.*, s.first_name, s.last_name, s.email 
    FROM $messages_table m 
    LEFT JOIN {$wpdb->prefix}tfsp_students s ON m.student_id = s.user_id 
    ORDER BY m.created_at DESC
");

// Handle status updates
if (isset($_POST['update_status']) && wp_verify_nonce($_POST['_wpnonce'], 'update_message_status')) {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }
    
    $message_id = intval($_POST['message_id']);
    $new_status = sanitize_text_field($_POST['new_status']);
    
    if ($message_id > 0 && in_array($new_status, ['unread', 'read', 'replied'])) {
        $wpdb->update($messages_table, 
            array('status' => $new_status), 
            array('id' => $message_id),
            array('%s'),
            array('%d')
        );
        echo '<div class="notice notice-success"><p>Message status updated!</p></div>';
    }
}
?>

<style>
.messages-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin: 20px 0;
}

.message-item {
    border-bottom: 1px solid #eee;
    padding: 20px;
}

.message-item:last-child {
    border-bottom: none;
}

.message-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 10px;
}

.message-type-coach {
    background: #8BC34A;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.message-type-admin {
    background: #FFC107;
    color: black;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.message-status {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.status-unread { background: #f8d7da; color: #721c24; }
.status-read { background: #d4edda; color: #155724; }
.status-replied { background: #cce7ff; color: #004085; }

.message-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}
</style>

<div class="wrap">
    <h1>💬 Messages</h1>
    <p>Manage student communications</p>

    <?php
    $total_messages = count($messages);
    $unread_count = count(array_filter($messages, function($m) { return $m->status === 'unread'; }));
    $coach_messages = count(array_filter($messages, function($m) { return $m->message_type === 'coach'; }));
    $admin_messages = count(array_filter($messages, function($m) { return $m->message_type === 'admin'; }));
    ?>

    <div class="message-stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_messages; ?></div>
            <div>Total Messages</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $unread_count; ?></div>
            <div>Unread</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $coach_messages; ?></div>
            <div>To Coach</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $admin_messages; ?></div>
            <div>To Admin</div>
        </div>
    </div>

    <div class="messages-container">
        <?php if (empty($messages)): ?>
            <div style="padding: 40px; text-align: center; color: #666;">
                <p>No messages yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $message): ?>
                <div class="message-item">
                    <div class="message-header">
                        <div>
                            <strong><?php echo esc_html($message->first_name . ' ' . $message->last_name); ?></strong>
                            <span class="message-type-<?php echo $message->message_type; ?>">
                                To <?php echo ucfirst($message->message_type); ?>
                            </span>
                            <span class="message-status status-<?php echo $message->status; ?>">
                                <?php echo ucfirst($message->status); ?>
                            </span>
                        </div>
                        <div>
                            <small><?php echo date('M j, Y g:i A', strtotime($message->created_at)); ?></small>
                        </div>
                    </div>
                    
                    <h4><?php echo esc_html($message->subject); ?></h4>
                    <p><?php echo nl2br(esc_html($message->message)); ?></p>
                    
                    <div style="margin-top: 15px;">
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field('update_message_status'); ?>
                            <input type="hidden" name="message_id" value="<?php echo intval($message->id); ?>">
                            <select name="new_status" onchange="this.form.submit()">
                                <option value="unread" <?php selected($message->status, 'unread'); ?>>Unread</option>
                                <option value="read" <?php selected($message->status, 'read'); ?>>Read</option>
                                <option value="replied" <?php selected($message->status, 'replied'); ?>>Replied</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </form>
                        
                        <a href="mailto:<?php echo esc_attr($message->email); ?>?subject=Re: <?php echo esc_attr($message->subject); ?>" 
                           class="button button-primary" style="margin-left: 10px;">
                            Reply via Email
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
