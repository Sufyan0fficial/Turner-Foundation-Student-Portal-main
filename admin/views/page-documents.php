<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

// Include WordPress upload functions
require_once(ABSPATH . 'wp-admin/includes/file.php');

// Handle CS letter upload
if (isset($_POST['upload_cs_letter']) && check_admin_referer('upload_cs_letter', 'cs_nonce')) {
    $student_id = intval($_POST['student_id']);
    
    if (!empty($_FILES['cs_letter']['name'])) {
        $upload = wp_handle_upload($_FILES['cs_letter'], array('test_form' => false));
        
        if (!isset($upload['error'])) {
            $wpdb->insert(
                $wpdb->prefix . 'tfsp_documents',
                array(
                    'user_id' => $student_id,
                    'document_type' => 'community_service_letter',
                    'file_path' => $upload['file'],
                    'file_url' => $upload['url'],
                    'status' => 'pending',
                    'approval_status' => 'pending',
                    'is_community_service_letter' => 1,
                    'upload_date' => current_time('mysql')
                )
            );
            echo '<div class="notice notice-success"><p>✓ Community Service Letter uploaded! Awaiting approval.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Upload error: ' . $upload['error'] . '</p></div>';
        }
    }
}

// Handle approval
if (isset($_POST['approve_cs_letter']) && check_admin_referer('approve_cs_letter', 'approve_nonce')) {
    $doc_id = intval($_POST['doc_id']);
    
    $wpdb->update(
        $wpdb->prefix . 'tfsp_documents',
        array(
            'approval_status' => 'approved',
            'approved_by' => get_current_user_id(),
            'approved_at' => current_time('mysql'),
            'status' => 'approved'
        ),
        array('id' => $doc_id)
    );
    
    echo '<div class="notice notice-success"><p>✓ Community Service Letter approved! Now visible to student.</p></div>';
}

// Handle rejection
if (isset($_POST['reject_cs_letter']) && check_admin_referer('reject_cs_letter', 'reject_nonce')) {
    $doc_id = intval($_POST['doc_id']);
    
    $wpdb->update(
        $wpdb->prefix . 'tfsp_documents',
        array(
            'approval_status' => 'sent_back',
            'status' => 'sent_back'
        ),
        array('id' => $doc_id)
    );
    
    echo '<div class="notice notice-warning"><p>Community Service Letter sent back to student for further development.</p></div>';
}

// Handle regular document status updates
if (isset($_POST['update_status']) && check_admin_referer('update_doc_status', 'doc_nonce')) {
    $doc_id = intval($_POST['doc_id']);
    $status = sanitize_text_field($_POST['status']);
    
    $wpdb->update(
        $wpdb->prefix . 'tfsp_documents',
        array('status' => $status),
        array('id' => $doc_id)
    );
    
    echo '<div class="notice notice-success"><p>✓ Document status updated!</p></div>';
}

// Get CS letters
$cs_letters = $wpdb->get_results("
    SELECT d.*, u.display_name as student_name, u.user_email,
           approver.display_name as approver_name
    FROM {$wpdb->prefix}tfsp_documents d 
    LEFT JOIN {$wpdb->users} u ON u.ID = d.user_id 
    LEFT JOIN {$wpdb->users} approver ON approver.ID = d.approved_by
    WHERE d.is_community_service_letter = 1
    ORDER BY d.upload_date DESC
");

// Get regular documents
$documents = $wpdb->get_results("
    SELECT d.*, u.display_name as student_name, u.user_email
    FROM {$wpdb->prefix}tfsp_documents d 
    LEFT JOIN {$wpdb->users} u ON u.ID = d.user_id 
    WHERE d.is_community_service_letter = 0 OR d.is_community_service_letter IS NULL
    ORDER BY d.upload_date DESC
");

// Get students for upload form
$students = get_users(array('role' => 'subscriber'));
?>

<style>
.cs-letter-section { background: #fffbeb; border: 2px solid #f59e0b; border-radius: 12px; padding: 24px; margin-bottom: 24px; }
.cs-letter-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.cs-letter-card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 16px; border-left: 4px solid #f59e0b; }
.approval-badge { padding: 6px 16px; border-radius: 16px; font-size: 13px; font-weight: 600; }
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-approved { background: #d1fae5; color: #065f46; }
.badge-sent_back { background: #fef3c7; color: #92400e; }
.upload-form { background: white; padding: 20px; border-radius: 8px; margin-top: 16px; }

@media (max-width: 768px) {
    .cs-letter-section { padding: 15px; }
    .cs-letter-header { flex-direction: column; align-items: flex-start; gap: 12px; }
    .cs-letter-header h3 { font-size: 18px; }
    .cs-letter-header button { width: 100%; }
    .cs-letter-card > div { flex-direction: column !important; }
    .cs-letter-card > div > div:last-child { text-align: left !important; margin-top: 15px; }
    .upload-form { padding: 15px; }
    .upload-form > div:last-child { flex-direction: column !important; }
    .upload-form button { width: 100%; }
}
</style>

<div class="section">
    <h2>📄 Document Management</h2>
    
    <!-- Community Service Letters Section -->
    <div class="cs-letter-section">
        <div class="cs-letter-header">
            <div>
                <h3 style="margin: 0; color: #92400e;">🏆 Community Service Verification Letters</h3>
                <p style="margin: 4px 0 0; color: #78350f;">50-hour program completion letters requiring approval</p>
            </div>
            <button onclick="document.getElementById('uploadForm').style.display='block'" class="btn">+ Upload Letter</button>
        </div>
        
        <!-- Upload Form -->
        <div id="uploadForm" class="upload-form" style="display: none;">
            <h4>Upload Community Service Letter</h4>
            <form method="POST" enctype="multipart/form-data">
                <?php wp_nonce_field('upload_cs_letter', 'cs_nonce'); ?>
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 500;">Select Student:</label>
                    <select name="student_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="">Choose student...</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student->ID; ?>"><?php echo esc_html($student->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 500;">Upload Letter (PDF):</label>
                    <input type="file" name="cs_letter" accept=".pdf" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div style="display: flex; gap: 12px;">
                    <button type="submit" name="upload_cs_letter" class="btn">Upload Letter</button>
                    <button type="button" onclick="document.getElementById('uploadForm').style.display='none'" class="btn" style="background: #6b7280;">Cancel</button>
                </div>
            </form>
        </div>
        
        <!-- CS Letters List -->
        <?php if (empty($cs_letters)): ?>
            <p style="text-align: center; color: #78350f; padding: 40px 0;">No community service letters uploaded yet</p>
        <?php else: ?>
            <?php foreach ($cs_letters as $letter): ?>
                <div class="cs-letter-card">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 8px; color: #1f2937;">
                                <?php echo esc_html($letter->student_name); ?>
                            </h4>
                            <p style="margin: 0 0 8px; color: #6b7280; font-size: 14px;">
                                <?php echo esc_html($letter->user_email); ?>
                            </p>
                            <p style="margin: 0; color: #6b7280; font-size: 13px;">
                                Uploaded: <?php echo date('M j, Y g:i A', strtotime($letter->upload_date)); ?>
                            </p>
                            <?php if ($letter->approval_status === 'approved'): ?>
                                <p style="margin: 8px 0 0; color: #059669; font-size: 13px;">
                                    ✓ Approved by <?php echo esc_html($letter->approver_name); ?> on <?php echo date('M j, Y', strtotime($letter->approved_at)); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div style="text-align: right;">
                            <span class="approval-badge badge-<?php echo $letter->approval_status; ?>">
                                <?php echo strtoupper($letter->approval_status); ?>
                            </span>
                            <div style="margin-top: 12px;">
                                <a href="<?php echo esc_url($letter->file_url); ?>" target="_blank" class="btn" style="padding: 6px 12px; font-size: 13px; margin-right: 8px;">View</a>
                                <?php if ($letter->approval_status === 'pending'): ?>
                                    <form method="POST" style="display: inline;">
                                        <?php wp_nonce_field('approve_cs_letter', 'approve_nonce'); ?>
                                        <input type="hidden" name="doc_id" value="<?php echo $letter->id; ?>">
                                        <button type="submit" name="approve_cs_letter" class="btn" style="padding: 6px 12px; font-size: 13px; background: #10b981;">Approve</button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <?php wp_nonce_field('reject_cs_letter', 'reject_nonce'); ?>
                                        <input type="hidden" name="doc_id" value="<?php echo $letter->id; ?>">
                                        <button type="submit" name="reject_cs_letter" class="btn" style="padding: 6px 12px; font-size: 13px; background: #ef4444;">Reject</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Regular Documents Section -->
    <h3>Regular Documents</h3>
    <p style="color: #6b7280; margin-bottom: 20px;">Student-uploaded application documents</p>
    
    <?php if (empty($documents)): ?>
        <p style="text-align: center; color: #9ca3af; padding: 40px 0;">No documents uploaded yet</p>
    <?php else: ?>
        <table class="data-table" style="width: 100%; background: white; border-radius: 8px;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="padding: 12px; text-align: left;">Student</th>
                    <th style="padding: 12px; text-align: left;">Document Type</th>
                    <th style="padding: 12px; text-align: left;">Upload Date</th>
                    <th style="padding: 12px; text-align: left;">Status</th>
                    <th style="padding: 12px; text-align: left;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $doc): ?>
                    <tr style="border-top: 1px solid #eee;">
                        <td data-label="Student" style="padding: 12px;"><?php echo esc_html($doc->student_name); ?></td>
                        <td data-label="Document Type" style="padding: 12px;"><?php echo ucwords(str_replace('_', ' ', $doc->document_type)); ?></td>
                        <td data-label="Upload Date" style="padding: 12px;"><?php echo date('M j, Y', strtotime($doc->upload_date)); ?></td>
                        <td data-label="Status" style="padding: 12px;">
                            <form method="POST" style="display: inline;">
                                <?php wp_nonce_field('update_doc_status', 'doc_nonce'); ?>
                                <input type="hidden" name="doc_id" value="<?php echo $doc->id; ?>">
                                <input type="hidden" name="update_status" value="1">
                                <select name="status" onchange="this.form.submit()" style="padding: 4px 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    <option value="submitted" <?php selected($doc->status, 'submitted'); ?>>Submitted</option>
                                    <option value="accepted" <?php selected($doc->status, 'accepted'); ?>>Accepted</option>
                                    <option value="sent_back" <?php selected($doc->status, 'sent_back'); ?>>Sent back to student for further development</option>
                                </select>
                                <noscript><button type="submit">Update</button></noscript>
                            </form>
                        </td>
                        <td data-label="Actions" style="padding: 12px;">
                            <a href="<?php echo esc_url($doc->file_url); ?>" target="_blank" class="btn" style="padding: 6px 12px; font-size: 13px;">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
