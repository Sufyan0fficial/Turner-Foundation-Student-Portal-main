<?php
if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in
if (!is_user_logged_in()) {
    echo '<div class="login-required"><p>Please log in to access your student portal.</p></div>';
    return;
}

$current_user = wp_get_current_user();

// Meeting settings
$meeting_title = 'Schedule Your Success Session';
$meeting_description = 'Connect with your dedicated College and Career Coach for personalized guidance on your journey to higher education.';

// Handle message submission
if (isset($_POST['send_message']) && wp_verify_nonce($_POST['_wpnonce'], 'send_message_nonce')) {
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to send messages');
    }
    
    global $wpdb;
    $messages_table = $wpdb->prefix . 'tfsp_messages';
    $user_id = get_current_user_id();
    
    // Rate limiting: Check if user sent a message in the last 5 minutes
    $recent_message = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $messages_table WHERE student_id = %d AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
        $user_id
    ));
    
    if ($recent_message > 0) {
        echo '<div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 20px 0;">Please wait 5 minutes between messages to prevent spam.</div>';
    } else {
        $message_type = sanitize_text_field($_POST['message_type']);
        $subject = sanitize_text_field($_POST['subject']);
        $message = sanitize_textarea_field($_POST['message']);
        
        // Validate inputs
        if (empty($subject) || empty($message)) {
            echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px 0;">Please fill in all fields.</div>';
        } elseif (!in_array($message_type, ['coach', 'admin'])) {
            echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px 0;">Invalid message type.</div>';
        } else {
            $result = $wpdb->insert($messages_table, array(
                'student_id' => $user_id,
                'message_type' => $message_type,
                'subject' => $subject,
                'message' => $message
            ), array('%d', '%s', '%s', '%s'));
            
            if ($result) {
                echo '<div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 20px 0;">Message sent successfully!</div>';
            } else {
                echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px 0;">Error sending message. Please try again.</div>';
            }
        }
    }
}

// Student Requirements Checklist
$checklist_items = array();

// Item 1
$checklist_items['academic_resume'] = array(
    'number' => '1',
    'title' => 'Academic Resume',
    'question' => 'What is the goal?',
    'status' => 'No',
    'requirement' => 'Resume completed',
    'additional_fields' => array()
);

// Item 2
$checklist_items['personal_essay'] = array(
    'number' => '2',
    'title' => 'Personal Essay',
    'question' => 'How will you present yourself?',
    'status' => 'No',
    'requirement' => 'Essay completed',
    'additional_fields' => array()
);

// Item 3
$checklist_items['recommendation_letters'] = array(
    'number' => '3',
    'title' => 'Recommendation Letters',
    'question' => 'How will others present you?',
    'status' => 'No',
    'requirement' => 'Letter 1, Letter 2',
    'additional_fields' => array()
);

// Item 4
$checklist_items['transcript'] = array(
    'number' => '4',
    'title' => 'Transcript',
    'question' => 'Why is this important?',
    'status' => 'No',
    'requirement' => 'Official transcript obtained',
    'additional_fields' => array()
);

// Item 5
$checklist_items['financial_aid'] = array(
    'number' => '5',
    'title' => 'Financial Aid',
    'question' => 'What is the goal?',
    'status' => 'No',
    'requirement' => 'FAFSA completed',
    'additional_fields' => array()
);

// Item 6
$checklist_items['community_service'] = array(
    'number' => '6',
    'title' => 'Community Service',
    'question' => 'What is the goal?',
    'status' => 'No',
    'requirement' => 'Service hours documented',
    'additional_fields' => array()
);

// Item 7
$checklist_items['college_list'] = array(
    'number' => '7',
    'title' => 'Create Interest List of Colleges',
    'question' => 'How are you showing progress?',
    'status' => 'No',
    'requirement' => 'College list created',
    'additional_fields' => array()
);

// Item 8
$checklist_items['college_tours'] = array(
    'number' => '8',
    'title' => 'College Tours',
    'question' => 'How are you showing the goal?',
    'status' => 'No',
    'requirement' => 'Tours scheduled/completed',
    'additional_fields' => array()
);

// Item 9
$checklist_items['fafsa'] = array(
    'number' => '9',
    'title' => 'FAFSA',
    'question' => 'Why is this important?',
    'status' => 'No',
    'requirement' => 'FAFSA submitted',
    'additional_fields' => array()
);

// Item 10
$checklist_items['college_admissions_tests'] = array(
    'number' => '10',
    'title' => 'College Admissions Tests',
    'question' => 'What do you want to achieve?',
    'status' => 'No',
    'requirement' => 'SAT/ACT completed',
    'additional_fields' => array()
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Turner Foundation Student Portal</title>
</head>
<body>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: #FFFFFF;
    color: #000000;
    line-height: 1.5;
}

.dashboard-container {
    width: 100%;
    min-height: 100vh;
    background: #FFFFFF;
}

.header {
    background: #8BC34A;
    color: #FFFFFF;
    padding: 20px 0;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.header-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header h1 {
    color: #FFFFFF;
    font-size: 24px;
    font-weight: 700;
    margin: 0;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #FFFFFF;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: #FFFFFF;
}

.btn {
    background: #8BC34A;
    color: #FFFFFF;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s ease;
}

.btn:hover {
    background: #689F38;
    color: #FFFFFF;
    text-decoration: none;
}

.btn-secondary {
    background: transparent;
    color: #FFFFFF;
    border: 2px solid #FFFFFF;
}

.btn-secondary:hover {
    background: #FFFFFF;
    color: #8BC34A;
}

.main-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px;
}

.card {
    background: #FFFFFF;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.card h3 {
    color: #000000;
    font-weight: 600;
    margin-bottom: 16px;
}

.checklist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.checklist-item {
    background: #FFFFFF;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.checklist-item h4 {
    color: #000000;
    font-weight: 600;
    margin-bottom: 8px;
}

.checklist-item p {
    color: #666666;
    font-size: 14px;
    margin-bottom: 12px;
}

.status-indicator {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-yes {
    background: #10b981;
    color: #FFFFFF;
}

.status-no {
    background: #e5e7eb;
    color: #333333;
}

.message-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.tab-button {
    padding: 10px 20px;
    background: #f5f5f5;
    color: #333;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
}

.tab-button.active {
    background: #8BC34A;
    color: #FFFFFF;
}

.message-form {
    display: none;
}

.message-form.active {
    display: block;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: 'Inter', sans-serif;
}

.form-group textarea {
    height: 100px;
    resize: vertical;
}

@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
    
    .checklist-grid {
        grid-template-columns: 1fr;
    }
    
    .main-content {
        padding: 20px 15px;
    }
}
</style>

<div class="dashboard-container">
    <header class="header">
        <div class="header-content">
            <h1>Turner Foundation Student Portal</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($current_user->display_name, 0, 1)); ?>
                </div>
                <span>Welcome, <?php echo esc_html($current_user->display_name); ?></span>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <!-- Meeting Section -->
        <div class="card">
            <h3>📅 <?php echo esc_html($meeting_title); ?></h3>
            <p><?php echo esc_html($meeting_description); ?></p>
            <a href="<?php echo esc_url(get_option('tfsp_calendly_url', 'https://calendly.com/abraizbashir/30min')); ?>" 
               target="_blank" class="btn">
                Schedule Meeting Now
            </a>
        </div>

        <!-- Recommendation Letters Section -->
        <div class="card">
            <h3>📝 Recommendation Letters</h3>
            <p>Teachers and coaches can submit recommendation letters using the form below:</p>
            
            <?php 
            $recommendation_form_url = get_option('tfsp_recommendation_form_url', '');
            if (!empty($recommendation_form_url)): 
            ?>
                <div style="background: #f0f9ff; padding: 16px; border-radius: 8px; margin: 16px 0;">
                    <h4 style="margin: 0 0 8px 0; color: #1565c0;">For Teachers & Coaches:</h4>
                    <p style="margin: 0 0 12px 0; font-size: 14px;">Please use this form to submit recommendation letters for <?php echo esc_html($current_user->display_name); ?>:</p>
                    <a href="<?php echo esc_url($recommendation_form_url); ?>" 
                       target="_blank" 
                       class="btn"
                       style="background: #1565c0;">
                        📝 Submit Recommendation Letter
                    </a>
                </div>
            <?php else: ?>
                <div style="background: #fff3cd; padding: 16px; border-radius: 8px; margin: 16px 0;">
                    <p style="margin: 0; color: #856404;">Recommendation form link not configured. Please contact your program administrator.</p>
                </div>
            <?php endif; ?>
            
            <p style="font-size: 14px; color: #666; margin-top: 16px;">
                <strong>Note:</strong> Share this link with your teachers, coaches, or mentors who will be writing your recommendation letters.
            </p>
        </div>

        <!-- Communication Section -->
        <div class="card">
            <h3>💬 Send a Message</h3>
            <div class="message-tabs">
                <button type="button" class="tab-button active" onclick="showMessageForm('coach')">
                    Message College & Career Coach
                </button>
                <button type="button" class="tab-button" onclick="showMessageForm('admin')">
                    Message Program Admin
                </button>
            </div>

            <form method="post" class="message-form active" id="coach-form">
                <?php wp_nonce_field('send_message_nonce'); ?>
                <input type="hidden" name="message_type" value="coach">
                <div class="form-group">
                    <label>Subject:</label>
                    <input type="text" name="subject" maxlength="255" required>
                </div>
                <div class="form-group">
                    <label>Message:</label>
                    <textarea name="message" maxlength="5000" required placeholder="Type your message to your College & Career Coach..."></textarea>
                </div>
                <button type="submit" name="send_message" class="btn">Send Message</button>
            </form>

            <form method="post" class="message-form" id="admin-form">
                <?php wp_nonce_field('send_message_nonce'); ?>
                <input type="hidden" name="message_type" value="admin">
                <div class="form-group">
                    <label>Subject:</label>
                    <input type="text" name="subject" maxlength="255" required>
                </div>
                <div class="form-group">
                    <label>Message:</label>
                    <textarea name="message" maxlength="5000" required placeholder="Type your message about portal issues or questions..."></textarea>
                </div>
                <button type="submit" name="send_message" class="btn">Send Message</button>
            </form>
        </div>

        <!-- College Preparation Checklist -->
        <div class="card">
            <h3>🎓 College Preparation Checklist</h3>
            <p>Track your progress through the college application process</p>
            
            <div class="checklist-grid">
                <?php foreach ($checklist_items as $key => $item): ?>
                    <div class="checklist-item">
                        <h4><?php echo $item['number']; ?>. <?php echo esc_html($item['title']); ?></h4>
                        <p><?php echo esc_html($item['question']); ?></p>
                        <p><strong>Requirement:</strong> <?php echo esc_html($item['requirement']); ?></p>
                        <span class="status-indicator status-<?php echo strtolower($item['status']); ?>">
                            <?php echo $item['status']; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>

<script>
function showMessageForm(type) {
    // Hide all forms
    document.querySelectorAll('.message-form').forEach(form => {
        form.classList.remove('active');
    });
    
    // Remove active class from all tabs
    document.querySelectorAll('.tab-button').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Show selected form and activate tab
    document.getElementById(type + '-form').classList.add('active');
    event.target.classList.add('active');
}
</script>

</body>
</html>
