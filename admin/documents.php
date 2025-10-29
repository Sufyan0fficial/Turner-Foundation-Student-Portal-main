<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get all documents with correct column names
global $wpdb;
$documents_table = $wpdb->prefix . 'tfsp_documents';

$documents = $wpdb->get_results("
    SELECT d.*, u.display_name
    FROM $documents_table d
    LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID
    ORDER BY d.upload_date DESC
");

// Get counts by status
$total_docs = $wpdb->get_var("SELECT COUNT(*) FROM $documents_table");
$pending_docs = $wpdb->get_var("SELECT COUNT(*) FROM $documents_table WHERE status = 'pending'");
$approved_docs = $wpdb->get_var("SELECT COUNT(*) FROM $documents_table WHERE status = 'approved'");
$rejected_docs = $wpdb->get_var("SELECT COUNT(*) FROM $documents_table WHERE status = 'needs_revision'");

// Handle document status updates
if (isset($_POST['update_document_status']) && wp_verify_nonce($_POST['_wpnonce'], 'update_document_status')) {
    $document_id = intval($_POST['document_id']);
    $new_status = sanitize_text_field($_POST['status']);
    
    $wpdb->update(
        $documents_table,
        array('status' => $new_status),
        array('id' => $document_id),
        array('%s'),
        array('%d')
    );
    
    echo '<div class="notice notice-success"><p>Document status updated successfully!</p></div>';
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">📄 Documents Management</h1>
    
    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0;">
        <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #0073aa;">
            <h4 style="margin: 0 0 5px 0; color: #666;">Total Documents</h4>
            <p style="font-size: 24px; font-weight: bold; color: #0073aa; margin: 0;"><?php echo $total_docs; ?></p>
        </div>
        <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #f56e28;">
            <h4 style="margin: 0 0 5px 0; color: #666;">Pending Review</h4>
            <p style="font-size: 24px; font-weight: bold; color: #f56e28; margin: 0;"><?php echo $pending_docs; ?></p>
        </div>
        <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #00a32a;">
            <h4 style="margin: 0 0 5px 0; color: #666;">Approved</h4>
            <p style="font-size: 24px; font-weight: bold; color: #00a32a; margin: 0;"><?php echo $approved_docs; ?></p>
        </div>
        <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #d63638;">
            <h4 style="margin: 0 0 5px 0; color: #666;">Needs Revision</h4>
            <p style="font-size: 24px; font-weight: bold; color: #d63638; margin: 0;"><?php echo $rejected_docs; ?></p>
        </div>
    </div>

    <!-- Documents Table -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <select name="bulk-action" id="bulk-action-selector-top">
                <option value="-1">Bulk Actions</option>
                <option value="approve">Approve</option>
                <option value="reject">Reject</option>
            </select>
            <input type="submit" id="doaction" class="button action" value="Apply">
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-cb check-column">
                    <input type="checkbox" />
                </th>
                <th scope="col" class="manage-column">Document</th>
                <th scope="col" class="manage-column">Student</th>
                <th scope="col" class="manage-column">Category</th>
                <th scope="col" class="manage-column">Status</th>
                <th scope="col" class="manage-column">Uploaded</th>
                <th scope="col" class="manage-column">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($documents)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px;">
                        <div style="color: #666;">
                            <p style="font-size: 18px; margin: 0;">📄</p>
                            <p><strong>No Documents Found</strong></p>
                            <p>Student document uploads will appear here for review.</p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($documents as $document): ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="document[]" value="<?php echo $document->id; ?>" />
                        </th>
                        <td>
                            <strong><?php echo esc_html($document->file_name); ?></strong>
                        </td>
                        <td><?php echo esc_html($document->display_name ?: 'Unknown'); ?></td>
                        <td><?php echo esc_html(ucwords(str_replace('_', ' ', $document->document_type))); ?></td>
                        <td>
                            <span class="status-<?php echo esc_attr($document->status); ?>" style="
                                padding: 4px 8px; 
                                border-radius: 4px; 
                                font-size: 12px; 
                                font-weight: 500;
                                <?php 
                                switch($document->status) {
                                    case 'pending': echo 'background: #fff3cd; color: #856404;'; break;
                                    case 'approved': echo 'background: #d4edda; color: #155724;'; break;
                                    case 'rejected': echo 'background: #f8d7da; color: #721c24;'; break;
                                    default: echo 'background: #e2e3e5; color: #383d41;';
                                }
                                ?>
                            ">
                                <?php echo esc_html(ucfirst($document->status)); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y g:i A', strtotime($document->upload_date)); ?></td>
                        <td>
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('update_document_status'); ?>
                                <input type="hidden" name="document_id" value="<?php echo $document->id; ?>">
                                <select name="status" onchange="this.form.submit()">
                                    <option value="pending" <?php selected($document->status, 'pending'); ?>>Pending</option>
                                    <option value="approved" <?php selected($document->status, 'approved'); ?>>Approved</option>
                                    <option value="rejected" <?php selected($document->status, 'rejected'); ?>>Rejected</option>
                                </select>
                                <input type="hidden" name="update_document_status" value="1">
                            </form>
                            <a href="<?php echo wp_upload_dir()['baseurl'] . '/tfsp-documents/' . $document->file_path; ?>" target="_blank" class="button button-small">Download</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
