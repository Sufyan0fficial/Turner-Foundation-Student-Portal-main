<?php
if (!defined('ABSPATH')) {
    exit;
}

// Check admin permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['tfsp_settings_nonce'], 'tfsp_settings')) {
    update_option('tfsp_calendly_url', sanitize_url($_POST['calendly_url']));
    update_option('tfsp_advisor_name', sanitize_text_field($_POST['advisor_name']));
    update_option('tfsp_advisor_email', sanitize_email($_POST['advisor_email']));
    update_option('tfsp_portal_title', sanitize_text_field($_POST['portal_title']));
    update_option('tfsp_welcome_message', sanitize_textarea_field($_POST['welcome_message']));
    update_option('tfsp_recommendation_form_url', sanitize_url($_POST['recommendation_form_url']));
    
    echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
}

// Get current settings
$calendly_url = get_option('tfsp_calendly_url', '');
$advisor_name = get_option('tfsp_advisor_name', 'College and Career Coach');
$advisor_email = get_option('tfsp_advisor_email', '');
$portal_title = get_option('tfsp_portal_title', 'Turner Foundation Student Portal');
$welcome_message = get_option('tfsp_welcome_message', 'Welcome to your college application journey!');
$recommendation_form_url = get_option('tfsp_recommendation_form_url', '');
?>

<div class="wrap">
    <h1>🔧 Portal Settings</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('tfsp_settings', 'tfsp_settings_nonce'); ?>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <h2>General Settings</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Portal Title</th>
                    <td>
                        <input type="text" name="portal_title" value="<?php echo esc_attr($portal_title); ?>" class="regular-text" />
                        <p class="description">The main title displayed in the student portal</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Welcome Message</th>
                    <td>
                        <textarea name="welcome_message" rows="3" cols="50" class="large-text"><?php echo esc_textarea($welcome_message); ?></textarea>
                        <p class="description">Welcome message shown to students on their dashboard</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <h2>College and Career Coach</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Advisor Name</th>
                    <td>
                        <input type="text" name="advisor_name" value="<?php echo esc_attr($advisor_name); ?>" class="regular-text" />
                        <p class="description">Name of the college advisor</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Advisor Email</th>
                    <td>
                        <input type="email" name="advisor_email" value="<?php echo esc_attr($advisor_email); ?>" class="regular-text" />
                        <p class="description">Email address for advisor communications</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Recommendation Form URL</th>
                    <td>
                        <input type="url" name="recommendation_form_url" value="<?php echo esc_attr($recommendation_form_url); ?>" class="regular-text" />
                        <p class="description">Google Form URL for teachers/coaches to submit recommendation letters</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Calendly URL</th>
                    <td>
                        <input type="url" name="calendly_url" value="<?php echo esc_attr($calendly_url); ?>" class="regular-text" />
                        <p class="description">Your Calendly scheduling link for student meetings</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <h2>System Information</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Plugin Version</th>
                    <td><?php echo TFSP_VERSION; ?></td>
                </tr>
                
                <tr>
                    <th scope="row">WordPress Version</th>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                
                <tr>
                    <th scope="row">PHP Version</th>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                
                <tr>
                    <th scope="row">Database Tables</th>
                    <td>
                        <?php
                        global $wpdb;
                        $tables = array(
                            'tfsp_students',
                            'tfsp_applications', 
                            'tfsp_documents',
                            'tfsp_meetings',
                            'tfsp_checklist_progress'
                        );
                        
                        foreach ($tables as $table) {
                            $table_name = $wpdb->prefix . $table;
                            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
                            $status = $exists ? '✅' : '❌';
                            echo $status . ' ' . $table . '<br>';
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <h2>Database Tools</h2>
            <p>Use these tools to manage the plugin database tables.</p>
            
            <button type="button" class="button button-secondary" onclick="recreateTables()">
                Recreate Database Tables
            </button>
            <p class="description">This will recreate all plugin database tables. Use if you're experiencing database issues.</p>
            
            <div id="db-message" style="margin-top: 15px; display: none;"></div>
        </div>
        
        <?php submit_button('Save Settings', 'primary', 'submit'); ?>
    </form>
</div>

<script>
function recreateTables() {
    if (!confirm('Are you sure you want to recreate the database tables? This action cannot be undone.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'tfsp_recreate_tables');
    formData.append('nonce', '<?php echo wp_create_nonce('tfsp_admin_nonce'); ?>');
    
    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const messageDiv = document.getElementById('db-message');
        messageDiv.style.display = 'block';
        
        if (data.success) {
            messageDiv.style.background = '#d4edda';
            messageDiv.style.color = '#155724';
            messageDiv.style.padding = '10px';
            messageDiv.style.borderRadius = '4px';
            messageDiv.textContent = data.data;
            
            setTimeout(() => location.reload(), 2000);
        } else {
            messageDiv.style.background = '#f8d7da';
            messageDiv.style.color = '#721c24';
            messageDiv.style.padding = '10px';
            messageDiv.style.borderRadius = '4px';
            messageDiv.textContent = 'Error: ' + data.data;
        }
    });
}
</script>