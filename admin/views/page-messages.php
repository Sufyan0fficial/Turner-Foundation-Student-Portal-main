<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

// Get coach email setting
$coach_email = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}tfsp_advisor_settings WHERE setting_key = 'coach_email'");

// Get all students
$students = get_users(array('role' => 'subscriber'));

// Get selected student
$selected_student = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

// Get messages
if ($selected_student) {
    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT m.*, u.display_name as sender_name
         FROM {$wpdb->prefix}tfsp_messages m
         LEFT JOIN {$wpdb->users} u ON u.ID = m.sender_id
         WHERE (m.sender_id = %d OR m.recipient_id = %d)
         AND (m.message_type != 'coach' OR m.message_type IS NULL)
         AND m.subject NOT LIKE '[Coach]%%'
         ORDER BY m.created_at ASC",
        $selected_student, $selected_student
    ));
} else {
    $messages = array();
}

// Get coach messages
$coach_messages = $wpdb->get_results(
    "SELECT m.*, u.display_name as sender_name, u.user_email
     FROM {$wpdb->prefix}tfsp_messages m
     LEFT JOIN {$wpdb->users} u ON u.ID = m.sender_id
     WHERE (m.subject LIKE '[Coach]%' OR m.message_type = 'coach')
     ORDER BY m.created_at DESC"
);
?>

<style>
.messaging-container { display: flex; gap: 20px; height: calc(100vh - 200px); }
.student-list { width: 300px; background: white; border-radius: 8px; padding: 20px; overflow-y: auto; }
.chat-area { flex: 1; background: white; border-radius: 8px; display: flex; flex-direction: column; }
.chat-header { padding: 20px; border-bottom: 1px solid #eee; }
.chat-messages { flex: 1; padding: 20px; overflow-y: auto; }
.chat-input { padding: 20px; border-top: 1px solid #eee; }
.student-item { padding: 12px; margin-bottom: 8px; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
.student-item:hover { background: #f3f4f6; }
.student-item.active { background: #8ebb79; color: white; }
.message-bubble { margin-bottom: 16px; max-width: 70%; }
.message-bubble.admin { margin-left: auto; background: #8ebb79; color: white; padding: 12px 16px; border-radius: 12px 12px 0 12px; }
.message-bubble.student { background: #f3f4f6; padding: 12px 16px; border-radius: 12px 12px 12px 0; }
.message-meta { font-size: 11px; opacity: 0.7; margin-top: 4px; }
.tabs { display: flex; border-bottom: 1px solid #eee; }
.tab-btn { padding: 15px 30px; border: none; background: none; cursor: pointer; font-weight: 500; border-bottom: 2px solid transparent; }
.tab-btn.active { border-bottom-color: #8ebb79; color: #8ebb79; }
.tab-content { display: none; }
.tab-content.active { display: block; }
.coach-message { background: white; padding: 16px; margin-bottom: 12px; border-radius: 8px; border-left: 4px solid #f59e0b; }

@media (max-width: 768px) {
    .messaging-container { flex-direction: column; height: auto; gap: 15px; }
    .student-list { width: 100%; max-height: 300px; }
    .chat-area { min-height: 500px; }
    .chat-header { padding: 15px; }
    .chat-messages { padding: 15px; }
    .chat-input { padding: 15px; }
    .chat-input form { flex-direction: column !important; }
    .chat-input textarea { width: 100%; }
    .chat-input button { width: 100%; align-self: stretch !important; }
    .message-bubble { max-width: 85%; }
    .tabs { overflow-x: auto; }
    .tab-btn { padding: 12px 20px; white-space: nowrap; }
    .coach-message { padding: 12px; }
    .coach-message > div:first-child { flex-direction: column !important; gap: 5px; }
}
</style>

<div class="section">
    <h2>💬 Message Center</h2>
    
    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('portal')">Admin Messages</button>
        <button class="tab-btn" onclick="switchTab('coach')">Coach Messages</button>
    </div>
    
    <!-- Admin Messages Tab -->
    <div id="portalTab" class="tab-content active">
        <div class="messaging-container">
            <div class="student-list">
                <h3 style="margin: 0 0 16px;">Students</h3>
                <?php foreach ($students as $student): ?>
                    <div class="student-item <?php echo $selected_student == $student->ID ? 'active' : ''; ?>" 
                         onclick="window.location.href='?view=messages&student_id=<?php echo $student->ID; ?>'">
                        <strong><?php echo esc_html($student->display_name); ?></strong>
                        <div style="font-size: 12px; opacity: 0.8;"><?php echo esc_html($student->user_email); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="chat-area">
                <?php if ($selected_student): 
                    $student = get_userdata($selected_student);
                ?>
                    <div class="chat-header">
                        <h3 style="margin: 0;"><?php echo esc_html($student->display_name); ?></h3>
                        <p style="margin: 4px 0 0; color: #6b7280;"><?php echo esc_html($student->user_email); ?></p>
                    </div>
                    
                    <div class="chat-messages" id="chatMessages">
                        <?php foreach ($messages as $msg): 
                            $is_admin = $msg->recipient_id == $selected_student;
                        ?>
                            <div class="message-bubble <?php echo $is_admin ? 'admin' : 'student'; ?>">
                                <div><?php echo nl2br(esc_html($msg->message)); ?></div>
                                <div class="message-meta">
                                    <?php echo date('M j, g:i A', strtotime($msg->created_at)); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($messages)): ?>
                            <p style="text-align: center; color: #9ca3af;">No messages yet. Start the conversation!</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="chat-input">
                        <form method="POST" style="display: flex; gap: 12px;">
                            <input type="hidden" name="recipient_id" value="<?php echo $selected_student; ?>">
                            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('send_admin_message'); ?>">
                            <textarea name="message" required placeholder="Type your message..." 
                                      style="flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 6px; resize: none;" 
                                      rows="2"></textarea>
                            <button type="submit" name="send_admin_reply" class="btn" style="align-self: flex-end;">Send</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #9ca3af;">
                        <p>Select a student to view conversation</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Coach Messages Tab -->
    <div id="coachTab" class="tab-content">
        <div style="padding: 20px;">
            <?php if ($coach_email): ?>
                <div style="background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <span style="font-size: 18px;">📧</span>
                        <strong style="color: #0369a1;">Coach Email Address</strong>
                    </div>
                    <p style="margin: 0; color: #0369a1;">
                        Student messages are sent to: <strong><?php echo esc_html($coach_email); ?></strong>
                    </p>
                    <p style="margin: 8px 0 0; font-size: 13px; color: #0369a1;">
                        <a href="?view=advisor" style="color: #0369a1; text-decoration: underline;">Update email address in Advisor Settings</a>
                    </p>
                </div>
            <?php else: ?>
                <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <span style="font-size: 18px;">⚠️</span>
                        <strong style="color: #92400e;">Coach Email Not Set</strong>
                    </div>
                    <p style="margin: 0; color: #92400e;">
                        Please set the coach email address in 
                        <a href="?view=advisor" style="color: #92400e; text-decoration: underline;">Advisor Settings</a>
                        to receive student messages.
                    </p>
                </div>
            <?php endif; ?>
            
            <h3>Messages to Career Coach</h3>
            <p style="color: #6b7280;">These messages were sent to the career coach via email.</p>
            
            <?php if (empty($coach_messages)): ?>
                <p style="text-align: center; color: #9ca3af; padding: 40px;">No coach messages yet.</p>
            <?php else: ?>
                <?php foreach ($coach_messages as $msg): ?>
                    <div class="coach-message">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <strong><?php echo esc_html($msg->sender_name); ?></strong>
                            <span style="color: #6b7280; font-size: 13px;">
                                <?php echo date('M j, Y g:i A', strtotime($msg->created_at)); ?>
                            </span>
                        </div>
                        <div style="font-size: 14px; color: #6b7280; margin-bottom: 8px;">
                            <?php echo esc_html($msg->user_email); ?>
                        </div>
                        <div style="font-weight: 500; margin-bottom: 8px;">
                            <?php echo esc_html(str_replace('[Coach] ', '', $msg->subject)); ?>
                        </div>
                        <div><?php echo nl2br(esc_html($msg->message)); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Handle admin reply
if (isset($_POST['send_admin_reply']) && wp_verify_nonce($_POST['nonce'], 'send_admin_message')) {
    $recipient_id = intval($_POST['recipient_id']);
    $message = sanitize_textarea_field($_POST['message']);
    $sender_id = get_current_user_id();
    
    $wpdb->insert(
        $wpdb->prefix . 'tfsp_messages',
        array(
            'sender_id' => $sender_id,
            'recipient_id' => $recipient_id,
            'subject' => 'Admin Reply',
            'message' => $message,
            'status' => 'unread',
            'created_at' => current_time('mysql')
        ),
        array('%d', '%d', '%s', '%s', '%s', '%s')
    );
    
    echo '<script>window.location.href = "?view=messages&student_id=' . $recipient_id . '";</script>';
}
?>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    event.target.classList.add('active');
    document.getElementById(tab + 'Tab').classList.add('active');
}

// Auto-scroll to bottom of chat
const chatMessages = document.getElementById('chatMessages');
if (chatMessages) {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}
</script>
