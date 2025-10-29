<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

// Handle form submission
if (isset($_POST['save_calendly_settings']) && wp_verify_nonce($_POST['calendly_nonce'], 'save_calendly_settings')) {
    $settings = array(
        'api_token' => sanitize_text_field($_POST['api_token']),
        'webhook_url' => sanitize_url($_POST['webhook_url']),
        'event_type_uuid' => sanitize_text_field($_POST['event_type_uuid']),
        'enabled' => isset($_POST['enabled']) ? 1 : 0
    );
    
    foreach ($settings as $key => $value) {
        $wpdb->replace(
            $wpdb->prefix . 'tfsp_calendly_settings',
            array(
                'setting_key' => $key,
                'setting_value' => $value
            )
        );
    }
    
    echo '<div class="notice notice-success">Calendly settings saved successfully!</div>';
}

// Get current settings with defaults
$current_settings = array(
    'api_token' => 'eyJraWQiOiIxY2UxZTEzNjE3ZGNmNzY2YjNjZWJjY2Y4ZGM1YmFmYThhNjVlNjg0MDIzZjczMmJlODM0OWIyMzgwMTM1YjQiLCJ0eXAiOiJQQVQiLCJhbGciOiJFUzI1NiJ9.eyJpc3MiOiJodHRwczovL2F1dGguY2FsZW5kbHkuY29tIiwiaWF0IjoxNzYwNjE3NTU5LCJqdGkiOiI2NDRiOWVjZS0yOWQ3LTQ4NmUtOTM4ZS1hNmM1ZTQ4NzM4OTEiLCJ1c2VyX3V1aWQiOiI5YTVjZGE2YS1jYWI0LTRhNzEtYTIzZi03MjRlNDBjYWIzOGUifQ.I9K7RgdNClep5pzr2xD11v14HN0q3n9pCfBhHsrcFv2xI4-e4KxAiWnIIPZ4ajDYkcrI_38RNyOBr59mYzPRJQ',
    'event_type_uuid' => '50dfc9ce-0765-41f0-992e-fc6b351fa270',
    'user_uri' => 'https://api.calendly.com/users/9a5cda6a-cab4-4a71-a23f-724e40cab38e',
    'organization_uri' => 'https://api.calendly.com/organizations/3f9ce3ca-e5bc-42a5-bbbf-5f56f90b7040',
    'enabled' => '1'
);

$settings_data = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}tfsp_calendly_settings");
foreach ($settings_data as $setting) {
    $current_settings[$setting->setting_key] = $setting->setting_value;
}

// Test connection
$connection_status = 'Not Configured';
$connection_class = 'error';
if (!empty($current_settings['api_token'])) {
    // Simple test - in real implementation, this would test the API
    $connection_status = 'Ready to Test';
    $connection_class = 'warning';
}
?>

<style>
.calendly-settings { max-width: 800px; }
.form-section { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #374151; }
.form-group input, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
.form-group textarea { height: 80px; resize: vertical; }
.form-group small { color: #6b7280; font-size: 12px; margin-top: 4px; display: block; }
.save-btn { background: #8ebb79; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; }
.save-btn:hover { background: #7aa86a; }
.status-badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
.status-success { background: #d1fae5; color: #065f46; }
.status-warning { background: #fef3c7; color: #92400e; }
.status-error { background: #fee2e2; color: #991b1b; }
.checkbox-group { display: flex; align-items: center; gap: 8px; }
.checkbox-group input[type="checkbox"] { width: auto; }
.info-box { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
.info-box h4 { margin: 0 0 8px; color: #0369a1; }
.info-box p { margin: 0; color: #0c4a6e; font-size: 14px; }
</style>

<div class="section calendly-settings">
    <h2>🗓️ Calendly Integration Settings</h2>
    <p style="color: #6b7280; margin-bottom: 30px;">Configure Calendly API integration for automatic session tracking</p>
    
    <div class="info-box">
        <h4>⚠️ Implementation Status</h4>
        <p>This is the configuration interface for Calendly integration. The webhook handler and API integration code needs to be implemented based on the feasibility study. Current status: <strong>Configuration Ready</strong></p>
    </div>
    
    <form method="post">
        <?php wp_nonce_field('save_calendly_settings', 'calendly_nonce'); ?>
        
        <div class="form-section">
            <h3 style="margin: 0 0 20px; color: #1f2937;">API Configuration</h3>
            
            <div class="form-group">
                <label>Calendly API Token</label>
                <input type="password" name="api_token" value="<?php echo esc_attr($current_settings['api_token'] ?? ''); ?>" placeholder="Enter your Calendly API token">
                <small>Get your API token from Calendly Developer Settings</small>
            </div>
            
            <div class="form-group">
                <label>Event Type UUID</label>
                <input type="text" name="event_type_uuid" value="<?php echo esc_attr($current_settings['event_type_uuid'] ?? ''); ?>" placeholder="e.g., AAAAAAAAAAAAAAAA">
                <small>The UUID of your coaching session event type in Calendly</small>
            </div>
            
            <div class="form-group">
                <label>Webhook URL</label>
                <input type="url" name="webhook_url" value="<?php echo esc_attr($current_settings['webhook_url'] ?? home_url('/wp-admin/admin-post.php?action=calendly_webhook')); ?>" readonly>
                <small>This URL will receive webhook notifications from Calendly</small>
            </div>
        </div>
        
        <div class="form-section">
            <h3 style="margin: 0 0 20px; color: #1f2937;">Integration Status</h3>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" name="enabled" id="enabled" <?php checked($current_settings['enabled'] ?? 0, 1); ?>>
                    <label for="enabled">Enable Calendly Integration</label>
                </div>
                <small>When enabled, new appointments will automatically create session records</small>
            </div>
            
            <div class="form-group">
                <label>Connection Status</label>
                <span class="status-badge status-<?php echo $connection_class; ?>"><?php echo $connection_status; ?></span>
            </div>
        </div>
        
        <button type="submit" name="save_calendly_settings" class="save-btn">💾 Save Settings</button>
    </form>
    
    <div class="form-section" style="margin-top: 30px;">
        <h3 style="margin: 0 0 15px; color: #1f2937;">🔄 Manual Sync (Basic Plan Alternative)</h3>
        <p style="color: #6b7280; margin-bottom: 15px;">Since webhooks require a Standard Calendly plan, use manual sync to import scheduled events.</p>
        
        <button type="button" id="syncCalendlyBtn" class="save-btn" style="background: #3b82f6;">
            🔄 Sync Calendly Events Now
        </button>
        
        <div id="syncStatus" style="margin-top: 15px; display: none;"></div>
    </div>
    
    <div class="form-section" style="margin-top: 30px;">
        <h3 style="margin: 0 0 15px; color: #1f2937;">📋 Setup Instructions</h3>
        <ol style="color: #374151; line-height: 1.6;">
            <li><strong>API Token:</strong> Already configured with your token</li>
            <li><strong>Event Type:</strong> Using "30 Minute Meeting" (50dfc9ce-0765-41f0-992e-fc6b351fa270)</li>
            <li><strong>Manual Sync:</strong> Click "Sync Calendly Events Now" to import scheduled appointments</li>
            <li><strong>Webhook Alternative:</strong> For automatic sync, upgrade to Calendly Standard plan</li>
            <li><strong>Monitor Sessions:</strong> Check Coach Sessions page to see imported appointments</li>
        </ol>
    </div>
</div>

<script>
// Define ajaxurl for external admin portal
var ajaxurl = '<?php echo esc_url( admin_url('admin-ajax.php') ); ?>';
document.getElementById('syncCalendlyBtn').addEventListener('click', function() {
    const btn = this;
    const status = document.getElementById('syncStatus');
    
    btn.disabled = true;
    btn.textContent = '🔄 Syncing...';
    status.style.display = 'block';
    status.innerHTML = '<div style="color: #3b82f6;">Fetching events from Calendly...</div>';
    
    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=sync_calendly_events&_ajax_nonce=<?php echo wp_create_nonce('sync_calendly_events'); ?>'
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.textContent = '🔄 Sync Calendly Events Now';
        
        if (data.success) {
            status.innerHTML = '<div style="color: #10b981; padding: 10px; background: #d1fae5; border-radius: 6px;">✅ ' + data.data.message + '</div>';
        } else {
            status.innerHTML = '<div style="color: #ef4444; padding: 10px; background: #fee2e2; border-radius: 6px;">❌ ' + data.data + '</div>';
        }
        
        setTimeout(() => {
            status.style.display = 'none';
        }, 5000);
    })
    .catch(error => {
        btn.disabled = false;
        btn.textContent = '🔄 Sync Calendly Events Now';
        status.innerHTML = '<div style="color: #ef4444; padding: 10px; background: #fee2e2; border-radius: 6px;">❌ Sync failed: ' + error.message + '</div>';
    });
});
</script>
