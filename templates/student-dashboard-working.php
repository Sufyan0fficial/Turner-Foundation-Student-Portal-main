<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();
if (!$current_user->ID) {
    wp_redirect(home_url('/student-login/'));
    exit;
}

// Get student data
global $wpdb;
$student_progress = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d", $current_user->ID));
$student_documents = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tfsp_documents WHERE user_id = %d ORDER BY upload_date DESC", $current_user->ID));
$student_meetings = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tfsp_meetings WHERE user_id = %d AND meeting_date >= CURDATE() ORDER BY meeting_date, meeting_time", $current_user->ID));

// Calculate progress
$roadmap_steps = array(
    'academic_resume' => 'Academic Resume',
    'personal_essay' => 'Personal Essay', 
    'recommendation_letters' => 'Recommendation Letters',
    'transcript' => 'Transcript',
    'financial_aid' => 'Financial Aid',
    'community_service' => 'Community Service',
    'college_list' => 'Create Interest List of Colleges',
    'college_tours' => 'College Tours',
    'fafsa' => 'FAFSA'
);

$completed_steps = array();
foreach ($student_progress as $progress) {
    if ($progress->status === 'completed') {
        $completed_steps[] = $progress->step_key;
    }
}

$progress_percentage = count($roadmap_steps) > 0 ? round((count($completed_steps) / count($roadmap_steps)) * 100) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard - Turner Foundation</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; }
        
        .header { background: linear-gradient(135deg, #2d5016 0%, #3f5340 100%); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; }
        .progress-circle { width: 80px; height: 80px; border-radius: 50%; background: conic-gradient(#8BC34A <?php echo $progress_percentage * 3.6; ?>deg, rgba(255,255,255,0.2) <?php echo $progress_percentage * 3.6; ?>deg); display: flex; align-items: center; justify-content: center; position: relative; }
        .progress-circle::before { content: ''; width: 60px; height: 60px; border-radius: 50%; background: #2d5016; position: absolute; }
        .progress-text { position: relative; z-index: 1; text-align: center; font-weight: bold; font-size: 14px; }
        .user-info { display: flex; align-items: center; gap: 20px; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
        .section { background: white; margin: 20px 0; padding: 30px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .section h2 { margin: 0 0 20px; color: #2d5016; font-size: 24px; }
        
        .roadmap-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-top: 20px; }
        .roadmap-card { border: 2px solid; border-radius: 12px; padding: 20px; position: relative; transition: all 0.3s; }
        .roadmap-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
        .card-number { position: absolute; top: -10px; left: 20px; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; }
        .card-content { margin-top: 10px; }
        .card-content h4 { margin: 0 0 8px; color: #333; font-size: 18px; }
        .card-content p { margin: 0 0 15px; color: #666; font-size: 14px; }
        
        .status-btn { border: none; padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 12px; cursor: pointer; transition: all 0.3s; }
        .status-btn.completed { background: #4caf50; color: white; }
        .status-btn.in-progress { background: #ff9800; color: white; }
        .status-btn.pending { background: #e0e0e0; color: #666; }
        
        .upload-area { border: 2px dashed #8BC34A; border-radius: 12px; padding: 40px 20px; text-align: center; background: #f8fff8; cursor: pointer; transition: all 0.3s; margin: 20px 0; }
        .upload-area:hover { border-color: #7CB342; background: #f0fff0; }
        .upload-controls { display: flex; gap: 15px; margin: 20px 0; }
        .upload-controls select, .upload-controls button { padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; }
        .btn { background: #8BC34A; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn:hover { background: #7CB342; }
        
        .document-item { display: flex; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px; margin: 10px 0; }
        .status-badge { color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        
        .message-form { display: grid; gap: 15px; margin: 20px 0; }
        .message-form input, .message-form textarea, .message-form select { padding: 12px; border: 1px solid #ddd; border-radius: 8px; }
        
        .alert { padding: 15px; border-radius: 8px; margin: 15px 0; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

<div class="header">
    <h1>🎓 Student Portal</h1>
    <div class="user-info">
        <div class="progress-circle">
            <div class="progress-text"><?php echo $progress_percentage; ?>%<br><small>Complete</small></div>
        </div>
        <div>
            <div style="font-weight: 600;"><?php echo $current_user->display_name; ?></div>
            <div style="font-size: 12px; opacity: 0.8;">Student Scholar</div>
        </div>
    </div>
</div>

<div class="container">
    <div id="alerts"></div>
    
    <!-- Welcome Section -->
    <div class="section">
        <h2>Welcome back, <?php echo $current_user->display_name; ?>! 👋</h2>
        <p>Track your college application progress, upload important documents, and stay connected with your dedicated College and Career Coach. Every step you complete brings you closer to your dream college!</p>
    </div>
    
    <!-- College Application Roadmap -->
    <div class="section">
        <h2>🗺️ Your College Application Roadmap</h2>
        <p><span style="color: #4caf50; font-weight: 600;"><?php echo count($completed_steps); ?> Completed</span> | <span style="color: #ff9800; font-weight: 600;"><?php echo count($roadmap_steps) - count($completed_steps); ?> Remaining</span></p>
        
        <div class="roadmap-grid">
            <?php 
            $colors = array('#9c27b0', '#e91e63', '#f44336', '#ff9800', '#009688', '#2196f3', '#673ab7', '#ff5722', '#1a237e');
            $index = 0;
            foreach ($roadmap_steps as $step_key => $step_title): 
                $is_completed = in_array($step_key, $completed_steps);
                $progress_item = array_filter($student_progress, function($p) use ($step_key) { return $p->step_key === $step_key; });
                $current_status = !empty($progress_item) ? reset($progress_item)->status : 'pending';
            ?>
                <div class="roadmap-card" style="border-color: <?php echo $colors[$index]; ?>;" data-step="<?php echo $step_key; ?>">
                    <div class="card-number" style="background: <?php echo $colors[$index]; ?>;"><?php echo $index + 1; ?></div>
                    <div class="card-content">
                        <h4><?php echo $step_title; ?></h4>
                        <p>Complete this important step in your college application journey.</p>
                        
                        <?php if ($current_status === 'completed'): ?>
                            <button class="status-btn completed">✓ Complete</button>
                        <?php elseif ($current_status === 'in_progress'): ?>
                            <button class="status-btn in-progress">⏳ In Progress</button>
                            <input type="text" placeholder="Enter details..." class="detail-input" style="width: 100%; margin-top: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                        <?php else: ?>
                            <button class="status-btn pending" onclick="startStep('<?php echo $step_key; ?>')">⭕ Start</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php 
                $index++;
            endforeach; 
            ?>
        </div>
    </div>
    
    <!-- Document Management -->
    <div class="section">
        <h2>📄 Document Management Hub</h2>
        
        <div class="upload-area" onclick="document.getElementById('fileInput').click()">
            <div style="font-size: 48px; margin-bottom: 15px;">📁</div>
            <p><strong>Drag & drop files here</strong> or <span style="color: #8BC34A; font-weight: 600;">choose files</span></p>
            <input type="file" id="fileInput" multiple style="display: none;">
        </div>
        
        <div class="upload-controls">
            <select id="documentType">
                <option value="">Select document type</option>
                <option value="transcript">Official Transcript</option>
                <option value="essay">Personal Essay</option>
                <option value="resume">Academic Resume</option>
                <option value="recommendation">Recommendation Letter</option>
                <option value="test_scores">Test Scores</option>
                <option value="financial">Financial Documents</option>
                <option value="other">Other</option>
            </select>
            <button class="btn" onclick="uploadDocument()">Upload</button>
        </div>
        
        <h4>📋 Your Documents</h4>
        <div id="documents-list">
            <?php if (empty($student_documents)): ?>
                <p style="text-align: center; color: #666; padding: 40px;">No documents uploaded yet.</p>
            <?php else: ?>
                <?php foreach ($student_documents as $doc): ?>
                    <div class="document-item">
                        <div style="font-size: 24px; margin-right: 15px;">📄</div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600;"><?php echo esc_html($doc->file_name); ?></div>
                            <div style="font-size: 12px; color: #666;"><?php echo esc_html($doc->document_type); ?></div>
                        </div>
                        <span class="status-badge" style="background: <?php 
                            echo $doc->status === 'accepted' ? '#4caf50' : 
                                ($doc->status === 'needs_revision' ? '#f44336' : '#ff9800'); 
                        ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $doc->status)); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Messaging -->
    <div class="section">
        <h2>💬 Send Message</h2>
        <div class="message-form">
            <select id="messageType">
                <option value="coach">College and Career Coach</option>
                <option value="admin">Program Admin</option>
            </select>
            <input type="text" id="messageSubject" placeholder="Subject">
            <textarea id="messageContent" placeholder="Your message..." rows="4"></textarea>
            <button class="btn" onclick="sendMessage()">Send Message</button>
        </div>
    </div>
    
    <!-- Upcoming Meetings -->
    <?php if (!empty($student_meetings)): ?>
    <div class="section">
        <h2>📅 Upcoming Sessions</h2>
        <?php foreach ($student_meetings as $meeting): ?>
            <div style="border-left: 4px solid #8BC34A; padding: 15px; background: #f8f9fa; margin: 10px 0; border-radius: 8px;">
                <div style="font-weight: 600;"><?php echo date('M j, Y', strtotime($meeting->meeting_date)); ?> at <?php echo date('g:i A', strtotime($meeting->meeting_time)); ?></div>
                <div style="color: #666;"><?php echo esc_html($meeting->meeting_type); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Schedule Meeting -->
    <div class="section">
        <h2>📅 Schedule Your Success Session</h2>
        <p>Connect with your dedicated College and Career Coach for personalized guidance</p>
        <a href="<?php echo esc_url(get_option('tfsp_calendly_url', 'https://calendly.com/abraizbashir/30min')); ?>" target="_blank" class="btn" style="text-decoration: none; display: inline-block;">
            📅 Schedule Meeting Now
        </a>
    </div>
</div>

<script>
// Global variables
const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
const userId = <?php echo $current_user->ID; ?>;

// Roadmap functionality
function startStep(stepKey) {
    if (confirm('Start working on this step?')) {
        updateRoadmapStatus(stepKey, 'in_progress');
    }
}

function updateRoadmapStatus(stepKey, status, notes = '') {
    jQuery.post(ajaxurl, {
        action: 'update_roadmap_status',
        step_key: stepKey,
        status: status,
        notes: notes,
        nonce: '<?php echo wp_create_nonce('roadmap_nonce'); ?>'
    }, function(response) {
        if (response.success) {
            showAlert('Progress updated successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('Error updating progress: ' + response.data, 'error');
        }
    });
}

// Document upload
function uploadDocument() {
    const fileInput = document.getElementById('fileInput');
    const docType = document.getElementById('documentType').value;
    
    if (fileInput.files.length === 0) {
        showAlert('Please select a file to upload', 'error');
        return;
    }
    
    if (!docType) {
        showAlert('Please select document type', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'upload_document');
    formData.append('document', fileInput.files[0]);
    formData.append('document_type', docType);
    formData.append('nonce', '<?php echo wp_create_nonce('upload_nonce'); ?>');
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showAlert('Document uploaded successfully!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('Error uploading document: ' + response.data, 'error');
            }
        }
    });
}

// Send message
function sendMessage() {
    const messageType = document.getElementById('messageType').value;
    const subject = document.getElementById('messageSubject').value;
    const message = document.getElementById('messageContent').value;
    
    if (!subject || !message) {
        showAlert('Please fill in all fields', 'error');
        return;
    }
    
    jQuery.post(ajaxurl, {
        action: 'send_message',
        message_type: messageType,
        subject: subject,
        message: message,
        nonce: '<?php echo wp_create_nonce('message_nonce'); ?>'
    }, function(response) {
        if (response.success) {
            showAlert('Message sent successfully!', 'success');
            document.getElementById('messageSubject').value = '';
            document.getElementById('messageContent').value = '';
        } else {
            showAlert('Error sending message: ' + response.data, 'error');
        }
    });
}

// Show alerts
function showAlert(message, type) {
    const alertsDiv = document.getElementById('alerts');
    const alert = document.createElement('div');
    alert.className = `alert ${type}`;
    alert.textContent = message;
    alertsDiv.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// Auto-save for detail inputs
jQuery(document).on('input', '.detail-input', function() {
    const input = this;
    const stepKey = jQuery(input).closest('.roadmap-card').data('step');
    
    clearTimeout(input.saveTimeout);
    input.saveTimeout = setTimeout(() => {
        updateRoadmapStatus(stepKey, 'in_progress', input.value);
    }, 2000);
});
</script>

</body>
</html>
