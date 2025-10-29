<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

// Handle resource save (file upload or external link)
if (isset($_POST['upload_resource']) && wp_verify_nonce($_POST['resource_nonce'], 'upload_resource')) {
    $resource_name = sanitize_text_field($_POST['resource_name']);
    $resource_type = sanitize_text_field($_POST['resource_type']);
    $resource_description = sanitize_textarea_field($_POST['resource_description']);
    $resource_link = isset($_POST['resource_link']) ? esc_url_raw(trim($_POST['resource_link'])) : '';

    // Save external link if provided
    if (!empty($resource_link)) {
        $wpdb->insert(
            $wpdb->prefix . 'tfsp_resources',
            array(
                'name' => $resource_name,
                'type' => $resource_type,
                'description' => $resource_description,
                'url' => $resource_link,
                'uploaded_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            )
        );
        echo '<div class="notice notice-success"><p>Resource link saved successfully!</p></div>';
    } elseif (!empty($_FILES['resource_file']['name'])) {
        $upload_dir = wp_upload_dir();
        $tfsp_dir = $upload_dir['basedir'] . '/tfsp-resources/';
        
        // Create directory if it doesn't exist
        if (!file_exists($tfsp_dir)) {
            wp_mkdir_p($tfsp_dir);
            // Add .htaccess for security
            file_put_contents($tfsp_dir . '.htaccess', "Options -Indexes\nDeny from all");
        }
        
        $file_extension = pathinfo($_FILES['resource_file']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = array('pdf', 'doc', 'docx', 'txt');
        
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $filename = sanitize_file_name($resource_name . '.' . $file_extension);
            $file_path = $tfsp_dir . $filename;
            
            if (move_uploaded_file($_FILES['resource_file']['tmp_name'], $file_path)) {
                // Save to database
                $wpdb->insert(
                    $wpdb->prefix . 'tfsp_resources',
                    array(
                        'name' => $resource_name,
                        'type' => $resource_type,
                        'description' => $resource_description,
                        'file_path' => $filename,
                        'uploaded_by' => get_current_user_id(),
                        'created_at' => current_time('mysql')
                    )
                );
                echo '<div class="notice notice-success"><p>Resource uploaded successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Failed to upload file.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>Invalid file type. Only PDF, DOC, DOCX, and TXT files are allowed.</p></div>';
        }
    } else {
        echo '<div class="notice notice-warning"><p>Please provide either a file or a URL.</p></div>';
    }
}

// Handle resource deletion
if (isset($_POST['delete_resource']) && wp_verify_nonce($_POST['delete_nonce'], 'delete_resource')) {
    $resource_id = intval($_POST['resource_id']);
    $resource = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tfsp_resources WHERE id = %d", $resource_id));
    
    if ($resource) {
        // Delete file
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/tfsp-resources/' . $resource->file_path;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete from database
        $wpdb->delete($wpdb->prefix . 'tfsp_resources', array('id' => $resource_id));
        echo '<div class="notice notice-success">Resource deleted successfully!</div>';
    }
}

// Get all resources
$resources = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tfsp_resources ORDER BY type, name");
?>

<style>
.resources-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
.upload-section { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.resources-list { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #374151; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
.form-group textarea { height: 80px; resize: vertical; }
.upload-btn { background: #8ebb79; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; }
.upload-btn:hover { background: #7aa86a; }
.resource-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 12px; }
.resource-info h4 { margin: 0 0 4px; color: #1f2937; font-size: 16px; }
.resource-info p { margin: 0; color: #6b7280; font-size: 13px; }
.resource-type { background: #f3f4f6; color: #374151; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
.resource-actions { display: flex; gap: 8px; }
.btn-small { padding: 6px 12px; font-size: 12px; border-radius: 4px; text-decoration: none; font-weight: 500; }
.btn-download { background: #3b82f6; color: white; }
.btn-delete { background: #ef4444; color: white; border: none; cursor: pointer; }
.empty-state { text-align: center; padding: 40px; color: #6b7280; }
</style>

<div class="section">
    <h2>ðŸ“š Resource Documents Management</h2>
    <p style="color: #6b7280; margin-bottom: 30px;">Upload and manage resource documents for students (Resume Template, Essay Guide, Application Checklist)</p>
    
    <div class="resources-grid">
        <!-- Upload Section -->
        <div class="upload-section">
            <h3 style="margin: 0 0 20px; color: #1f2937;">Upload New Resource</h3>
            
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('upload_resource', 'resource_nonce'); ?>
                
                <div class="form-group">
                    <label>Resource Name</label>
                    <input type="text" name="resource_name" required placeholder="e.g., Resume Template">
                </div>
                
                <div class="form-group">
                    <label>Resource Type</label>
                    <select name="resource_type" required>
                        <option value="">Select Type</option>
                        <option value="template">Template</option>
                        <option value="guide">Guide</option>
                        <option value="checklist">Checklist</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="resource_description" placeholder="Brief description of this resource..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Resource Link (URL)</label>
                    <input type="url" name="resource_link" placeholder="https://example.com/resource.pdf">
                    <small style="color:#6b7280;">Optional. If provided, students will be taken to this link.</small>
                </div>

                <div class="form-group">
                    <label>File (PDF, DOC, DOCX, TXT)</label>
                    <input type="file" name="resource_file" accept=".pdf,.doc,.docx,.txt">
                    <small style="color:#6b7280;">Optional. Upload a file instead of a link.</small>
                </div>
                
                <button type="submit" name="upload_resource" class="upload-btn">ðŸ“¤ Upload Resource</button>
            </form>
        </div>
        
        <!-- Resources List -->
        <div class="resources-list">
            <h3 style="margin: 0 0 20px; color: #1f2937;">Available Resources</h3>
            
            <?php if (empty($resources)): ?>
                <div class="empty-state">
                    <h4>No Resources Yet</h4>
                    <p>Upload your first resource document to get started.</p>
                    <p style="font-size: 12px; margin-top: 15px;"><strong>Pending from Client:</strong><br>
                    â€¢ Resume Template<br>
                    â€¢ Essay Guide<br>
                    â€¢ Application Checklist</p>
                </div>
            <?php else: ?>
                <?php foreach ($resources as $resource): ?>
                <div class="resource-item">
                    <div class="resource-info">
                        <h4><?php echo esc_html($resource->name); ?></h4>
                        <p><?php echo esc_html($resource->description); ?></p>
                        <span class="resource-type"><?php echo esc_html($resource->type); ?><?php echo !empty($resource->url) ? ' • LINK' : ' • FILE'; ?></span>
                    </div>
                    <div class="resource-actions">
                        <?php if (!empty($resource->url)) : ?>
                            <a href="<?php echo esc_url($resource->url); ?>" target="_blank" class="btn-small btn-download">Open Link</a>
                        <?php else : ?>
                            <a href="<?php echo admin_url('admin-post.php?action=download_resource&id=' . $resource->id); ?>" class="btn-small btn-download">Download</a>
                        <?php endif; ?>
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field('delete_resource', 'delete_nonce'); ?>
                            <input type="hidden" name="resource_id" value="<?php echo $resource->id; ?>">
                            <button type="submit" name="delete_resource" class="btn-small btn-delete" onclick="return confirm('Are you sure you want to delete this resource?')">Delete</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Add download handler for admin
add_action('admin_post_download_resource', function() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    $resource_id = intval($_GET['id']);
    $resource = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tfsp_resources WHERE id = %d", $resource_id));
    
    if ($resource && $resource->file_path) {
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/tfsp-resources/' . $resource->file_path;
        
        if (file_exists($file_path)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . sanitize_file_name($resource->name) . '"');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        }
    }
    
    wp_die('File not found');
});

// Add download handler for students
add_action('admin_post_download_student_resource', function() {
    if (!is_user_logged_in()) {
        wp_die('Please log in to download resources.');
    }
    
    global $wpdb;
    $resource_id = intval($_GET['id']);
    $resource = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tfsp_resources WHERE id = %d AND is_active = 1", $resource_id));
    
    if ($resource && $resource->file_path) {
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/tfsp-resources/' . $resource->file_path;
        
        if (file_exists($file_path)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . sanitize_file_name($resource->name) . '"');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        }
    }
    
    wp_die('Resource not found or unavailable.');
});

add_action('admin_post_nopriv_download_student_resource', function() {
    wp_redirect(wp_login_url());
    exit;
});
?>


