<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_document_management() {
    global $wpdb;
    $documents = $wpdb->get_results("
        SELECT d.*, u.display_name 
        FROM {$wpdb->prefix}tfsp_documents d 
        JOIN {$wpdb->users} u ON d.user_id = u.ID 
        ORDER BY d.upload_date DESC
    ");
    ?>
    
    <div class="section">
        <h2>📄 Document Status Management</h2>
        <p>Updated status options: Submitted, Accepted, Sent back for development</p>
        
        <?php if (empty($documents)): ?>
            <div class="empty-state">
                <p>No documents uploaded yet.</p>
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Document</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td><?php echo $doc->display_name; ?></td>
                            <td><?php echo $doc->file_name; ?></td>
                            <td><?php echo $doc->document_type; ?></td>
                            <td>
                                <select onchange="updateDocumentStatus(<?php echo $doc->id; ?>, this.value)" style="padding: 4px 8px;">
                                    <option value="submitted" <?php selected($doc->status, 'submitted'); ?>>Submitted</option>
                                    <option value="accepted" <?php selected($doc->status, 'accepted'); ?>>Accepted</option>
                                    <option value="needs_revision" <?php selected($doc->status, 'needs_revision'); ?>>Sent back for development</option>
                                </select>
                            </td>
                            <td>
                                <a href="<?php echo $doc->file_path; ?>" target="_blank" class="btn">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
?>
