<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_messaging_center() {
    global $wpdb;
    ?>
    
    <div class="section">
        <h2>💬 Messaging Center</h2>
        <p>Dual messaging system: Coach messages (email-tied) and Admin messages (portal-specific)</p>
        
        <div class="tab-buttons">
            <button class="tab-button active" onclick="showMessages('coach')" id="coach-tab">Coach Messages</button>
            <button class="tab-button" onclick="showMessages('admin')" id="admin-tab">Admin Messages</button>
        </div>
        
        <div id="coach-messages">
            <?php 
            $coach_messages = $wpdb->get_results("
                SELECT m.*, u.display_name 
                FROM {$wpdb->prefix}tfsp_messages m 
                JOIN {$wpdb->users} u ON m.student_id = u.ID 
                WHERE m.message_type = 'coach' 
                ORDER BY m.created_at DESC
            ");
            
            if (empty($coach_messages)): ?>
                <div class="empty-state">
                    <p>No coach messages yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($coach_messages as $message): ?>
                    <div class="message-item coach-message" onclick="toggleMessage(this)">
                        <div class="message-header">
                            <strong><?php echo esc_html($message->display_name); ?></strong>
                            <span class="message-date"><?php echo date('M j, Y g:i A', strtotime($message->created_at)); ?></span>
                            <span class="status-badge status-<?php echo esc_attr($message->status); ?>"><?php echo ucfirst($message->status); ?></span>
                        </div>
                        <div class="message-subject"><strong>Subject:</strong> <?php echo esc_html($message->subject); ?></div>
                        <div class="message-content" style="display: none;">
                            <div class="message-text"><?php echo nl2br(esc_html($message->message)); ?></div>
                        </div>
                        <div class="expand-indicator">Click to expand message</div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div id="admin-messages" class="hidden">
            <?php 
            // Get all admin message conversations
            $conversations = $wpdb->get_results("
                SELECT DISTINCT m.student_id, u.display_name, u.user_email,
                       MAX(m.created_at) as last_message_date,
                       COUNT(CASE WHEN m.status = 'unread' AND m.sender_type = 'student' THEN 1 END) as unread_count
                FROM {$wpdb->prefix}tfsp_messages m 
                JOIN {$wpdb->users} u ON m.student_id = u.ID 
                WHERE m.message_type = 'admin' 
                GROUP BY m.student_id, u.display_name, u.user_email
                ORDER BY last_message_date DESC
            ");
            
            if (empty($conversations)): ?>
                <div class="empty-state">
                    <p>No admin messages yet.</p>
                </div>
            <?php else: ?>
                <div class="conversations-list">
                    <?php foreach ($conversations as $conv): ?>
                        <div class="conversation-item" onclick="openConversation(<?php echo $conv->student_id; ?>)">
                            <div class="conv-header">
                                <strong><?php echo esc_html($conv->display_name); ?></strong>
                                <?php if ($conv->unread_count > 0): ?>
                                    <span class="unread-badge"><?php echo $conv->unread_count; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="conv-email"><?php echo esc_html($conv->user_email); ?></div>
                            <div class="conv-date"><?php echo date('M j, Y g:i A', strtotime($conv->last_message_date)); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Conversation Modal -->
    <div id="conversationModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="conversationTitle">Conversation</h3>
                <span class="close" onclick="closeConversation()">&times;</span>
            </div>
            <div class="conversation-messages" id="conversationMessages">
                <!-- Messages will be loaded here -->
            </div>
            <div class="reply-form">
                <textarea id="replyMessage" placeholder="Type your reply..." rows="3"></textarea>
                <button onclick="sendReply()" class="btn-primary">Send Reply</button>
            </div>
        </div>
    </div>
    
    <style>
    .tab-buttons {
        display: flex;
        margin-bottom: 20px;
        border-bottom: 1px solid #ddd;
    }
    
    .tab-button {
        padding: 10px 20px;
        border: none;
        background: #f8f9fa;
        cursor: pointer;
        border-bottom: 2px solid transparent;
    }
    
    .tab-button.active {
        background: white;
        border-bottom-color: #8BC34A;
        color: #8BC34A;
    }
    
    .hidden {
        display: none;
    }
    
    .message-item, .conversation-item {
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        cursor: pointer;
    }
    
    .conversation-item:hover {
        background: #f8f9fa;
    }
    
    .message-header, .conv-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 5px;
    }
    
    .unread-badge {
        background: #dc3545;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 12px;
    }
    
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .modal-content {
        background: white;
        border-radius: 8px;
        width: 90%;
        max-width: 600px;
        max-height: 80vh;
        display: flex;
        flex-direction: column;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid #ddd;
    }
    
    .conversation-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        max-height: 400px;
    }
    
    .reply-form {
        padding: 20px;
        border-top: 1px solid #ddd;
    }
    
    .reply-form textarea {
        width: 100%;
        margin-bottom: 10px;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .btn-primary {
        background: #8BC34A;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .message-bubble {
        margin-bottom: 15px;
        padding: 10px;
        border-radius: 8px;
    }
    
    .message-bubble.student {
        background: #e3f2fd;
        margin-left: 20px;
    }
    
    .message-bubble.admin {
        background: #f1f8e9;
        margin-right: 20px;
    }
    
    .message-meta {
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
    }
    
    .status-badge {
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
    }
    
    .status-sent {
        background: #d4edda;
        color: #155724;
    }
    
    .status-failed {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .expand-indicator {
        font-size: 12px;
        color: #007cba;
        margin-top: 5px;
        font-style: italic;
    }
    
    .coach-message {
        transition: all 0.3s ease;
    }
    
    .coach-message:hover {
        background: #f8f9fa;
    }
    
    .coach-message.expanded .expand-indicator {
        display: none;
    }
    
    .message-text {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        margin-top: 10px;
        border-left: 3px solid #8BC34A;
    }
    </style>
    
    <script>
    let currentStudentId = null;
    
    function toggleMessage(element) {
        const content = element.querySelector('.message-content');
        const indicator = element.querySelector('.expand-indicator');
        
        if (content.style.display === 'none') {
            content.style.display = 'block';
            indicator.style.display = 'none';
            element.classList.add('expanded');
        } else {
            content.style.display = 'none';
            indicator.style.display = 'block';
            element.classList.remove('expanded');
        }
    }
    
    function showMessages(type) {
        // Update tabs
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.getElementById(type + '-tab').classList.add('active');
        
        // Show/hide content
        document.getElementById('coach-messages').classList.toggle('hidden', type !== 'coach');
        document.getElementById('admin-messages').classList.toggle('hidden', type !== 'admin');
    }
    
    function openConversation(studentId) {
        currentStudentId = studentId;
        document.getElementById('conversationModal').style.display = 'flex';
        loadConversation(studentId);
    }
    
    function closeConversation() {
        document.getElementById('conversationModal').style.display = 'none';
        currentStudentId = null;
    }
    
    function loadConversation(studentId) {
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'tfsp_get_conversation',
                student_id: studentId,
                nonce: '<?php echo wp_create_nonce('tfsp_admin_nonce'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayConversation(data.data);
            }
        });
    }
    
    function displayConversation(data) {
        document.getElementById('conversationTitle').textContent = 'Conversation with ' + data.student_name;
        
        let html = '';
        data.messages.forEach(msg => {
            const isAdmin = msg.sender_type === 'admin';
            html += `
                <div class="message-bubble ${isAdmin ? 'admin' : 'student'}">
                    <div class="message-meta">
                        <strong>${isAdmin ? 'You (Admin)' : data.student_name}</strong> - ${msg.created_at}
                    </div>
                    <div>${msg.message}</div>
                </div>
            `;
        });
        
        document.getElementById('conversationMessages').innerHTML = html;
        document.getElementById('conversationMessages').scrollTop = document.getElementById('conversationMessages').scrollHeight;
    }
    
    function sendReply() {
        const message = document.getElementById('replyMessage').value;
        if (!message.trim() || !currentStudentId) return;
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'tfsp_send_admin_reply',
                student_id: currentStudentId,
                message: message,
                nonce: '<?php echo wp_create_nonce('tfsp_admin_nonce'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('replyMessage').value = '';
                loadConversation(currentStudentId);
            }
        });
    }
    </script>
    
    <?php
}
?>
