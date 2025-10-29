<?php
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

$current_user = wp_get_current_user();
global $wpdb;

// Get user's applications
$applications = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tfsp_applications WHERE user_id = %d ORDER BY created_at DESC",
    get_current_user_id()
));
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.tfsp-tracker {
    font-family: 'Inter', sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}
.tfsp-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 30px;
    text-align: center;
}
.tfsp-add-form {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}
.tfsp-applications-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}
.tfsp-app-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border-left: 4px solid #667eea;
}
.tfsp-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
}
.tfsp-form-group {
    margin-bottom: 20px;
}
.tfsp-input, .tfsp-select {
    width: 100%;
    padding: 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-family: inherit;
    box-sizing: border-box;
}
.tfsp-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
}
.status-pending { color: #f59e0b; }
.status-submitted { color: #10b981; }
.status-accepted { color: #059669; }
.status-rejected { color: #ef4444; }
</style>

<div class="tfsp-tracker">
    <div class="tfsp-header">
        <h1>🎯 College Application Tracker</h1>
        <p>Track your college applications and deadlines</p>
    </div>

    <!-- Add New Application -->
    <div class="tfsp-add-form">
        <h3>Add New College Application</h3>
        <form id="add-application-form">
            <div class="tfsp-form-row">
                <div class="tfsp-form-group">
                    <input type="text" name="college_name" placeholder="College Name" class="tfsp-input" required>
                </div>
                <div class="tfsp-form-group">
                    <input type="date" name="deadline" placeholder="Application Deadline" class="tfsp-input" required>
                </div>
            </div>
            <div class="tfsp-form-row">
                <div class="tfsp-form-group">
                    <select name="application_type" class="tfsp-select" required>
                        <option value="">Application Type</option>
                        <option value="early_decision">Early Decision</option>
                        <option value="early_action">Early Action</option>
                        <option value="regular_decision">Regular Decision</option>
                        <option value="rolling">Rolling Admission</option>
                    </select>
                </div>
                <div class="tfsp-form-group">
                    <select name="status" class="tfsp-select" required>
                        <option value="">Status</option>
                        <option value="planning">Planning</option>
                        <option value="in_progress">In Progress</option>
                        <option value="submitted">Submitted</option>
                        <option value="accepted">Accepted</option>
                        <option value="rejected">Rejected</option>
                        <option value="waitlisted">Waitlisted</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="tfsp-btn">Add Application</button>
        </form>
        <div id="add-message" style="margin-top: 15px; display: none;"></div>
    </div>

    <!-- Applications List -->
    <div class="tfsp-applications-grid">
        <?php if (empty($applications)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #6b7280;">
                <h3>No applications yet</h3>
                <p>Add your first college application above to get started!</p>
            </div>
        <?php else: ?>
            <?php foreach ($applications as $app): ?>
                <div class="tfsp-app-card">
                    <h4 style="margin: 0 0 15px 0; color: #1a202c;"><?php echo esc_html($app->college_name); ?></h4>
                    <div style="margin-bottom: 10px;">
                        <strong>Type:</strong> <?php echo esc_html(ucwords(str_replace('_', ' ', $app->application_type))); ?>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <strong>Deadline:</strong> <?php echo date('M j, Y', strtotime($app->deadline)); ?>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <strong>Status:</strong> 
                        <span class="status-<?php echo esc_attr($app->status); ?>">
                            <?php echo esc_html(ucwords(str_replace('_', ' ', $app->status))); ?>
                        </span>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button class="tfsp-btn" onclick="updateStatus(<?php echo $app->id; ?>)" style="font-size: 12px; padding: 8px 12px;">Update Status</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('add-application-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'tfsp_add_application');
        formData.append('nonce', '<?php echo wp_create_nonce('tfsp_nonce'); ?>');
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const messageDiv = document.getElementById('add-message');
            messageDiv.style.display = 'block';
            if (data.success) {
                messageDiv.style.color = '#155724';
                messageDiv.style.background = '#d4edda';
                messageDiv.style.padding = '10px';
                messageDiv.style.borderRadius = '8px';
                messageDiv.innerHTML = data.data;
                setTimeout(() => location.reload(), 1500);
            } else {
                messageDiv.style.color = '#721c24';
                messageDiv.style.background = '#f8d7da';
                messageDiv.style.padding = '10px';
                messageDiv.style.borderRadius = '8px';
                messageDiv.innerHTML = data.data;
            }
        });
    });
});

function updateStatus(appId) {
    const newStatus = prompt('Enter new status (planning, in_progress, submitted, accepted, rejected, waitlisted):');
    if (newStatus) {
        const formData = new FormData();
        formData.append('action', 'tfsp_update_application_status');
        formData.append('application_id', appId);
        formData.append('status', newStatus);
        formData.append('nonce', '<?php echo wp_create_nonce('tfsp_nonce'); ?>');
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Update failed: ' + data.data);
            }
        });
    }
}
</script>
