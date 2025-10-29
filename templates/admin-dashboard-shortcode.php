<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check admin permissions
if (!current_user_can('manage_options')) {
    return '<div style="padding: 20px; background: #fff3cd; border-radius: 8px; text-align: center;"><p>Access denied. Admin privileges required.</p></div>';
}

global $wpdb;

// Get statistics with null checks
$students_count = intval($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_students")) ?: 0;
$documents_count = intval($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_documents")) ?: 0;
$meetings_count = intval($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_meetings")) ?: 0;
$applications_count = intval($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_applications")) ?: 0;

// Get recent activity
$recent_students = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tfsp_students ORDER BY created_at DESC LIMIT 5");
$pending_documents = $wpdb->get_results("
    SELECT d.*, u.display_name 
    FROM {$wpdb->prefix}tfsp_documents d 
    LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID 
    WHERE d.status = 'pending'
    ORDER BY d.upload_date DESC LIMIT 10
");
?>

<style>
.tfsp-admin-dashboard {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
}

.tfsp-admin-header {
    background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 30px;
}

.tfsp-admin-header h1 {
    margin: 0 0 10px 0;
    font-size: 28px;
    font-weight: 700;
}

.tfsp-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.tfsp-stat-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    text-align: center;
    border-top: 4px solid;
}

.tfsp-stat-card.students { border-top-color: #3b82f6; }
.tfsp-stat-card.documents { border-top-color: #10b981; }
.tfsp-stat-card.meetings { border-top-color: #f59e0b; }
.tfsp-stat-card.applications { border-top-color: #ef4444; }

.tfsp-stat-number {
    font-size: 32px;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 8px;
}

.tfsp-stat-label {
    color: #64748b;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.tfsp-admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 25px;
}

.tfsp-admin-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.tfsp-admin-card h3 {
    color: #1e293b;
    margin-bottom: 20px;
    font-size: 18px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

.tfsp-table {
    width: 100%;
    border-collapse: collapse;
}

.tfsp-table th,
.tfsp-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
}

.tfsp-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #374151;
}

.tfsp-status-badge {
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.tfsp-status-pending {
    background: #fef3c7;
    color: #92400e;
}

.tfsp-btn {
    background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%);
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
    margin: 2px;
}

.tfsp-btn:hover {
    transform: translateY(-1px);
    color: white;
}

.tfsp-btn-approve { background: #10b981; }
.tfsp-btn-reject { background: #ef4444; }

.tfsp-empty-state {
    text-align: center;
    padding: 30px;
    color: #64748b;
}

@media (max-width: 768px) {
    .tfsp-admin-grid {
        grid-template-columns: 1fr;
    }
    .tfsp-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="tfsp-admin-dashboard">
    <div class="tfsp-admin-header">
        <h1>🎓 Turner Foundation Admin Dashboard</h1>
        <p>Manage students, documents, and portal settings</p>
    </div>

    <!-- Statistics Overview -->
    <div class="tfsp-stats-grid">
        <div class="tfsp-stat-card students">
            <div class="tfsp-stat-number"><?php echo $students_count; ?></div>
            <div class="tfsp-stat-label">Total Students</div>
        </div>
        <div class="tfsp-stat-card documents">
            <div class="tfsp-stat-number"><?php echo $documents_count; ?></div>
            <div class="tfsp-stat-label">Documents Uploaded</div>
        </div>
        <div class="tfsp-stat-card meetings">
            <div class="tfsp-stat-number"><?php echo $meetings_count; ?></div>
            <div class="tfsp-stat-label">Meetings Scheduled</div>
        </div>
        <div class="tfsp-stat-card applications">
            <div class="tfsp-stat-number"><?php echo $applications_count; ?></div>
            <div class="tfsp-stat-label">Applications Tracked</div>
        </div>
    </div>

    <div class="tfsp-admin-grid">
        <!-- Pending Documents for Review -->
        <div class="tfsp-admin-card">
            <h3>📋 Documents Pending Review</h3>
            <?php if (empty($pending_documents)): ?>
                <div class="tfsp-empty-state">
                    <p>No documents pending review.</p>
                </div>
            <?php else: ?>
                <table class="tfsp-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Document</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_documents as $doc): ?>
                            <tr>
                                <td><?php echo esc_html($doc->display_name); ?></td>
                                <td><?php echo esc_html($doc->file_name); ?></td>
                                <td><?php echo date('M j', strtotime($doc->upload_date)); ?></td>
                                <td>
                                    <button class="tfsp-btn tfsp-btn-approve" onclick="updateDocStatus(<?php echo $doc->id; ?>, 'approved')">Approve</button>
                                    <button class="tfsp-btn tfsp-btn-reject" onclick="updateDocStatus(<?php echo $doc->id; ?>, 'rejected')">Reject</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Recent Students -->
        <div class="tfsp-admin-card">
            <h3>👥 Recent Students</h3>
            <?php if (empty($recent_students)): ?>
                <div class="tfsp-empty-state">
                    <p>No students registered yet.</p>
                </div>
            <?php else: ?>
                <table class="tfsp-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_students as $student): ?>
                            <tr>
                                <td><?php echo esc_html($student->first_name . ' ' . $student->last_name); ?></td>
                                <td><?php echo esc_html($student->email); ?></td>
                                <td><?php echo date('M j', strtotime($student->created_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="tfsp-admin-card">
            <h3>⚡ Quick Actions</h3>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="<?php echo admin_url('admin.php?page=tfsp-students'); ?>" class="tfsp-btn">Manage Students</a>
                <a href="<?php echo admin_url('admin.php?page=tfsp-documents'); ?>" class="tfsp-btn">Review Documents</a>
                <a href="<?php echo admin_url('admin.php?page=tfsp-meetings'); ?>" class="tfsp-btn">Manage Meetings</a>
                <a href="<?php echo admin_url('admin.php?page=tfsp-settings'); ?>" class="tfsp-btn">Portal Settings</a>
            </div>
        </div>

        <!-- System Status -->
        <div class="tfsp-admin-card">
            <h3>🔧 System Status</h3>
            <table class="tfsp-table">
                <tbody>
                    <tr>
                        <td><strong>Plugin Version:</strong></td>
                        <td><?php echo TFSP_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong>WordPress:</strong></td>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>PHP Version:</strong></td>
                        <td><?php echo PHP_VERSION; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function updateDocStatus(documentId, status) {
    if (!confirm('Are you sure you want to ' + status + ' this document?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'tfsp_update_document_status');
    formData.append('document_id', documentId);
    formData.append('status', status);
    formData.append('nonce', '<?php echo wp_create_nonce('tfsp_admin_nonce'); ?>');
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Document status updated successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.data);
        }
    })
    .catch(error => {
        alert('Error updating document status.');
        console.error('Error:', error);
    });
}
</script>
