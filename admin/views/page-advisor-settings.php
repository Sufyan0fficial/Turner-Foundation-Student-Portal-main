<?php
if (!defined('ABSPATH')) {
    exit;
}

// Create advisor settings table if it doesn't exist
global $wpdb;
$advisor_table = $wpdb->prefix . 'tfsp_advisor_settings';
if ($wpdb->get_var("SHOW TABLES LIKE '$advisor_table'") != $advisor_table) {
    $sql = "CREATE TABLE $advisor_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        PRIMARY KEY (id)
    ) {$wpdb->get_charset_collate()};";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Insert default settings
    $default_settings = array(
        array('setting_key' => 'advisor_title', 'setting_value' => 'College and Career Coach'),
        array('setting_key' => 'advisor_tagline', 'setting_value' => 'Dedicated to your success'),
        array('setting_key' => 'stat1_number', 'setting_value' => '500+'),
        array('setting_key' => 'stat1_label', 'setting_value' => 'Students Helped'),
        array('setting_key' => 'stat2_number', 'setting_value' => '95%'),
        array('setting_key' => 'stat2_label', 'setting_value' => 'Success Rate'),
        array('setting_key' => 'meeting_link', 'setting_value' => '#'),
        array('setting_key' => 'recommendation_link', 'setting_value' => ''),
        array('setting_key' => 'recommendation_email', 'setting_value' => ''),
        array('setting_key' => 'coach_email', 'setting_value' => '')
    );
    
    foreach ($default_settings as $setting) {
        $wpdb->insert($advisor_table, $setting);
    }
}

// Create upcoming sessions table
$sessions_table = $wpdb->prefix . 'tfsp_upcoming_sessions';
if ($wpdb->get_var("SHOW TABLES LIKE '$sessions_table'") != $sessions_table) {
    $sql = "CREATE TABLE $sessions_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        date_time DATETIME NOT NULL,
        description TEXT,
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) {$wpdb->get_charset_collate()};";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Handle form submissions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'update_settings' && wp_verify_nonce($_POST['advisor_nonce'], 'manage_advisor')) {
        $settings = array('advisor_title', 'advisor_tagline', 'stat1_number', 'stat1_label', 'stat2_number', 'stat2_label', 'meeting_link', 'recommendation_link', 'recommendation_email', 'coach_email');
        
        foreach ($settings as $key) {
            if (isset($_POST[$key])) {
                $wpdb->replace($advisor_table, array(
                    'setting_key' => $key,
                    'setting_value' => sanitize_text_field($_POST[$key])
                ));
            }
        }
        $success_message = 'College and Career Coach settings updated successfully!';
    }
    
    if ($_POST['action'] === 'add_session' && wp_verify_nonce($_POST['advisor_nonce'], 'manage_advisor')) {
        $wpdb->insert($sessions_table, array(
            'title' => sanitize_text_field($_POST['session_title']),
            'date_time' => sanitize_text_field($_POST['session_datetime']),
            'description' => sanitize_textarea_field($_POST['session_description'])
        ));
        $success_message = 'Session added successfully!';
    }
    
    if ($_POST['action'] === 'delete_session' && wp_verify_nonce($_POST['advisor_nonce'], 'manage_advisor')) {
        $wpdb->delete($sessions_table, array('id' => intval($_POST['session_id'])));
        $success_message = 'Session deleted successfully!';
    }
}

// Get current settings
$settings = $wpdb->get_results("SELECT setting_key, setting_value FROM $advisor_table", OBJECT_K);
$upcoming_sessions = $wpdb->get_results("SELECT * FROM $sessions_table WHERE is_active = 1 ORDER BY date_time ASC");
?>

<style>
.advisor-admin-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 25px; }
.advisor-admin-header h1 { margin: 0 0 8px 0; font-size: 28px; font-weight: 700; }
.settings-form { background: white; padding: 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
.form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
.btn-primary { background: #667eea; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
.btn-primary:hover { background: #764ba2; }
.sessions-list { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
.session-item { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
.session-info h3 { margin: 0 0 5px 0; color: #333; }
.session-info p { margin: 0; color: #666; font-size: 14px; }
.btn-delete { background: #e74c3c; color: white; padding: 6px 12px; font-size: 12px; border: none; border-radius: 4px; cursor: pointer; }
.success-message { background: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
</style>

<div class="advisor-admin-header">
    <h1>👨‍🏫 College and Career Coach Settings</h1>
    <p>Manage advisor section content and upcoming sessions</p>
</div>

<?php if (isset($success_message)): ?>
<div class="success-message"><?php echo $success_message; ?></div>
<?php endif; ?>

<div class="settings-form">
    <h2>Advisor Section Settings</h2>
    <form method="post">
        <?php wp_nonce_field('manage_advisor', 'advisor_nonce'); ?>
        <input type="hidden" name="action" value="update_settings">
        
        <div class="form-grid">
            <div class="form-group">
                <label>Section Title</label>
                <input type="text" name="advisor_title" value="<?php echo esc_attr($settings['advisor_title']->setting_value ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Tagline</label>
                <input type="text" name="advisor_tagline" value="<?php echo esc_attr($settings['advisor_tagline']->setting_value ?? ''); ?>" required>
            </div>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label>First Stat Number</label>
                <input type="text" name="stat1_number" value="<?php echo esc_attr($settings['stat1_number']->setting_value ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>First Stat Label</label>
                <input type="text" name="stat1_label" value="<?php echo esc_attr($settings['stat1_label']->setting_value ?? ''); ?>" required>
            </div>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label>Second Stat Number</label>
                <input type="text" name="stat2_number" value="<?php echo esc_attr($settings['stat2_number']->setting_value ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Second Stat Label</label>
                <input type="text" name="stat2_label" value="<?php echo esc_attr($settings['stat2_label']->setting_value ?? ''); ?>" required>
            </div>
        </div>
        
        <div class="form-group">
            <label>Schedule Meeting Link</label>
            <input type="url" name="meeting_link" value="<?php echo esc_attr($settings['meeting_link']->setting_value ?? ''); ?>" placeholder="https://calendly.com/your-link">
        </div>
        
        <h3 style="margin: 30px 0 15px; color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px;">📝 Recommendation Process Settings</h3>
        
        <div class="form-grid">
            <div class="form-group">
                <label>Staff Recommendation Form Link</label>
                <input type="url" name="recommendation_link" value="<?php echo esc_attr($settings['recommendation_link']->setting_value ?? ''); ?>" placeholder="https://forms.google.com/your-form">
                <small style="color: #666;">Google Form or other platform for staff to submit recommendation letters</small>
            </div>
            <div class="form-group">
                <label>Recommendation Email Address</label>
                <input type="email" name="recommendation_email" value="<?php echo esc_attr($settings['recommendation_email']->setting_value ?? ''); ?>" placeholder="recommendations@turnerfoundation.org">
                <small style="color: #666;">Email address for staff to send recommendation letters directly</small>
            </div>
        </div>
        
        <div class="form-group">
            <label>Coach Email Address</label>
            <input type="email" name="coach_email" value="<?php echo esc_attr($settings['coach_email']->setting_value ?? ''); ?>" placeholder="coach@turnerfoundation.org">
            <small style="color: #666;">Email address where student coach messages are sent</small>
        </div>
        
        <button type="submit" class="btn-primary">Update Settings</button>
    </form>
</div>
