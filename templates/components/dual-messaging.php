<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_dual_messaging($user_id) {
    global $wpdb;
    $recent_messages = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM {$wpdb->prefix}tfsp_messages 
        WHERE student_id = %d 
        ORDER BY created_at DESC 
        LIMIT 5
    ", $user_id));
    ?>
    
    <div class="messaging-section">
        <h2>💬 Communication Center</h2>
        <p>Stay connected with your support team through dedicated channels</p>
        
        <div class="messaging-tabs">
            <button class="tab-btn active" onclick="switchTab('coach')" id="coach-tab">
                📧 Message College & Career Coach
            </button>
            <button class="tab-btn" onclick="switchTab('admin')" id="admin-tab">
                🛠️ Message Program Admin
            </button>
        </div>
        
        <!-- Coach Messaging -->
        <div class="message-panel" id="coach-panel">
            <div class="message-info">
                <div class="info-card">
                    <h4>📧 College & Career Coach</h4>
                    <p>For academic guidance, college applications, career planning, and personal support. Messages are linked to email for immediate response.</p>
                </div>
            </div>
            
            <div class="message-form">
                <input type="text" id="coach-subject" placeholder="Subject (e.g., 'Help with Personal Essay')" class="form-input">
                <textarea id="coach-message" placeholder="Describe your question or concern in detail..." rows="4" class="form-textarea"></textarea>
                <button class="btn-send" onclick="sendMessage('coach')">
                    📧 Send to Coach
                </button>
            </div>
        </div>
        
        <!-- Admin Messaging -->
        <div class="message-panel hidden" id="admin-panel">
            <div class="message-info">
                <div class="info-card">
                    <h4>🛠️ Program Admin</h4>
                    <p>For portal technical issues, account problems, or program-related questions. Separate from GroupMe for important administrative matters.</p>
                </div>
            </div>
            
            <div class="forum-style-chat">
                <div class="chat-messages" id="admin-chat">
                    <?php 
                    $admin_messages = $wpdb->get_results($wpdb->prepare("
                        SELECT m.*, u.display_name 
                        FROM {$wpdb->prefix}tfsp_messages m 
                        JOIN {$wpdb->users} u ON m.student_id = u.ID 
                        WHERE m.message_type = 'admin' AND m.student_id = %d 
                        ORDER BY m.created_at ASC
                    ", $user_id));
                    
                    if (empty($admin_messages)): ?>
                        <div class="no-messages">
                            <p>No admin messages yet. Start a conversation about portal or program issues.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($admin_messages as $msg): ?>
                            <div class="chat-message <?php echo $msg->sender_type === 'admin' ? 'admin-message' : 'student-message'; ?>">
                                <div class="message-header">
                                    <strong><?php echo $msg->sender_type === 'admin' ? 'Program Admin' : $msg->display_name; ?></strong>
                                    <span class="timestamp"><?php echo date('M j, g:i A', strtotime($msg->created_at)); ?></span>
                                </div>
                                <div class="message-content"><?php echo nl2br(esc_html($msg->message)); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="chat-input">
                    <input type="text" id="admin-message" placeholder="Type your message..." class="chat-input-field">
                    <button class="btn-send-chat" onclick="sendAdminMessage()">Send</button>
                </div>
            </div>
        </div>
        
        <!-- Recent Messages Summary -->
        <?php if (!empty($recent_messages)): ?>
        <div class="recent-messages">
            <h4>📬 Recent Messages</h4>
            <div class="message-list">
                <?php foreach ($recent_messages as $msg): ?>
                    <div class="message-item">
                        <div class="message-meta">
                            <span class="message-type"><?php echo $msg->message_type === 'coach' ? '📧 Coach' : '🛠️ Admin'; ?></span>
                            <span class="message-date"><?php echo date('M j', strtotime($msg->created_at)); ?></span>
                        </div>
                        <div class="message-subject"><?php echo esc_html($msg->subject ?: 'Chat Message'); ?></div>
                        <div class="message-status <?php echo $msg->status; ?>"><?php echo ucfirst($msg->status); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <style>
    .messaging-section {
        background: white;
        padding: 30px;
        border-radius: 16px;
        margin: 30px 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .messaging-section h2 {
        margin: 0 0 8px 0;
        color: #2d5016;
        font-size: 24px;
        font-weight: 700;
    }
    .messaging-section p {
        margin: 0 0 25px 0;
        color: #666;
    }
    
    .messaging-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 25px;
        border-bottom: 1px solid #e0e0e0;
    }
    .tab-btn {
        background: transparent;
        border: none;
        padding: 12px 20px;
        cursor: pointer;
        font-weight: 600;
        color: #666;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
    }
    .tab-btn.active {
        color: #8BC34A;
        border-bottom-color: #8BC34A;
    }
    .tab-btn:hover {
        color: #8BC34A;
    }
    
    .message-panel {
        margin-top: 20px;
    }
    .message-panel.hidden {
        display: none;
    }
    
    .info-card {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #8BC34A;
    }
    .info-card h4 {
        margin: 0 0 8px 0;
        color: #333;
        font-size: 16px;
    }
    .info-card p {
        margin: 0;
        color: #666;
        font-size: 14px;
        line-height: 1.5;
    }
    
    .message-form {
        display: grid;
        gap: 15px;
    }
    .form-input, .form-textarea {
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.3s;
    }
    .form-input:focus, .form-textarea:focus {
        outline: none;
        border-color: #8BC34A;
    }
    .btn-send {
        background: #8BC34A;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: background 0.3s;
        justify-self: start;
    }
    .btn-send:hover {
        background: #7CB342;
    }
    
    .forum-style-chat {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
    }
    .chat-messages {
        height: 300px;
        overflow-y: auto;
        padding: 15px;
        background: #fafafa;
    }
    .chat-message {
        margin-bottom: 15px;
        padding: 12px;
        border-radius: 8px;
        max-width: 80%;
    }
    .student-message {
        background: #e3f2fd;
        margin-left: auto;
        text-align: right;
    }
    .admin-message {
        background: #f1f8e9;
        margin-right: auto;
    }
    .message-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-size: 12px;
    }
    .message-content {
        font-size: 14px;
        line-height: 1.4;
    }
    .timestamp {
        color: #666;
        font-size: 11px;
    }
    .no-messages {
        text-align: center;
        padding: 40px 20px;
        color: #666;
    }
    
    .chat-input {
        display: flex;
        padding: 15px;
        background: white;
        border-top: 1px solid #e0e0e0;
    }
    .chat-input-field {
        flex: 1;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 20px;
        margin-right: 10px;
    }
    .btn-send-chat {
        background: #8BC34A;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 20px;
        cursor: pointer;
        font-weight: 600;
    }
    
    .recent-messages {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e0e0e0;
    }
    .recent-messages h4 {
        margin: 0 0 15px 0;
        color: #333;
    }
    .message-list {
        display: grid;
        gap: 10px;
    }
    .message-item {
        display: flex;
        align-items: center;
        padding: 12px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    .message-meta {
        display: flex;
        flex-direction: column;
        margin-right: 15px;
        min-width: 80px;
    }
    .message-type {
        font-size: 12px;
        font-weight: 600;
    }
    .message-date {
        font-size: 11px;
        color: #666;
    }
    .message-subject {
        flex: 1;
        font-weight: 500;
        color: #333;
    }
    .message-status {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .message-status.unread {
        background: #fff3cd;
        color: #856404;
    }
    .message-status.read {
        background: #d1ecf1;
        color: #0c5460;
    }
    </style>
    
    <script>
    function switchTab(type) {
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById(type + '-tab').classList.add('active');
        
        // Update panels
        document.querySelectorAll('.message-panel').forEach(panel => panel.classList.add('hidden'));
        document.getElementById(type + '-panel').classList.remove('hidden');
    }
    
    function sendMessage(type) {
        const subject = document.getElementById(type + '-subject').value;
        const message = document.getElementById(type + '-message').value;
        
        if (!subject || !message) {
            alert('Please fill in both subject and message fields.');
            return;
        }
        
        jQuery.post(ajaxurl, {
            action: 'send_message',
            message_type: type,
            subject: subject,
            message: message,
            nonce: '<?php echo wp_create_nonce('message_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                alert('Message sent successfully! You will receive a response via email.');
                document.getElementById(type + '-subject').value = '';
                document.getElementById(type + '-message').value = '';
            } else {
                alert('Error sending message: ' + response.data);
            }
        });
    }
    
    function sendAdminMessage() {
        const message = document.getElementById('admin-message').value;
        
        if (!message.trim()) {
            alert('Please enter a message.');
            return;
        }
        
        jQuery.post(ajaxurl, {
            action: 'send_message',
            message_type: 'admin',
            subject: 'Chat Message',
            message: message,
            nonce: '<?php echo wp_create_nonce('message_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                // Add message to chat
                const chatMessages = document.getElementById('admin-chat');
                const messageDiv = document.createElement('div');
                messageDiv.className = 'chat-message student-message';
                messageDiv.innerHTML = `
                    <div class="message-header">
                        <strong><?php echo $current_user->display_name; ?></strong>
                        <span class="timestamp">Just now</span>
                    </div>
                    <div class="message-content">${message}</div>
                `;
                chatMessages.appendChild(messageDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                document.getElementById('admin-message').value = '';
            } else {
                alert('Error sending message: ' + response.data);
            }
        });
    }
    
    // Auto-scroll chat to bottom
    document.addEventListener('DOMContentLoaded', function() {
        const chatMessages = document.getElementById('admin-chat');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    });
    </script>
    <?php
}
?>
