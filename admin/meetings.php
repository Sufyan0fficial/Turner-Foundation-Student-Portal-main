<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get all meetings with correct column names
global $wpdb;
$meetings_table = $wpdb->prefix . 'tfsp_meetings';

$meetings = $wpdb->get_results("
    SELECT m.*, u.display_name,
           CONCAT(m.meeting_date, ' ', m.meeting_time) as full_datetime
    FROM $meetings_table m
    LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
    ORDER BY m.meeting_date DESC, m.meeting_time DESC
");

// Get counts by status
$total_meetings = $wpdb->get_var("SELECT COUNT(*) FROM $meetings_table");
$upcoming_meetings = $wpdb->get_var("SELECT COUNT(*) FROM $meetings_table WHERE meeting_date >= CURDATE() AND status = 'pending'");
$completed_meetings = $wpdb->get_var("SELECT COUNT(*) FROM $meetings_table WHERE status = 'completed'");
$cancelled_meetings = $wpdb->get_var("SELECT COUNT(*) FROM $meetings_table WHERE status = 'cancelled'");

// Handle meeting status updates
if (isset($_POST['update_meeting_status']) && wp_verify_nonce($_POST['_wpnonce'], 'update_meeting_status')) {
    $meeting_id = intval($_POST['meeting_id']);
    $new_status = sanitize_text_field($_POST['status']);
    
    $wpdb->update(
        $meetings_table,
        array('status' => $new_status),
        array('id' => $meeting_id),
        array('%s'),
        array('%d')
    );
    
    echo '<div class="notice notice-success"><p>Meeting status updated successfully!</p></div>';
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">📅 Meetings Management</h1>
    
    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0;">
        <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #0073aa;">
            <h4 style="margin: 0 0 5px 0; color: #666;">Total Meetings</h4>
            <p style="font-size: 24px; font-weight: bold; color: #0073aa; margin: 0;"><?php echo $total_meetings; ?></p>
        </div>
        <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #f56e28;">
            <h4 style="margin: 0 0 5px 0; color: #666;">Upcoming</h4>
            <p style="font-size: 24px; font-weight: bold; color: #f56e28; margin: 0;"><?php echo $upcoming_meetings; ?></p>
        </div>
        <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #00a32a;">
            <h4 style="margin: 0 0 5px 0; color: #666;">Completed</h4>
            <p style="font-size: 24px; font-weight: bold; color: #00a32a; margin: 0;"><?php echo $completed_meetings; ?></p>
        </div>
        <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #d63638;">
            <h4 style="margin: 0 0 5px 0; color: #666;">Cancelled</h4>
            <p style="font-size: 24px; font-weight: bold; color: #d63638; margin: 0;"><?php echo $cancelled_meetings; ?></p>
        </div>
    </div>

    <!-- Meetings Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column">Student</th>
                <th scope="col" class="manage-column">Meeting Type</th>
                <th scope="col" class="manage-column">Date & Time</th>
                <th scope="col" class="manage-column">Status</th>
                <th scope="col" class="manage-column">Requested</th>
                <th scope="col" class="manage-column">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($meetings)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px;">
                        <div style="color: #666;">
                            <p style="font-size: 18px; margin: 0;">📅</p>
                            <p><strong>No Meetings Scheduled</strong></p>
                            <p>Student meetings will appear here when scheduled.</p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($meetings as $meeting): ?>
                    <tr>
                        <td><strong><?php echo esc_html($meeting->display_name ?: 'Unknown'); ?></strong></td>
                        <td><?php echo esc_html(ucwords(str_replace('_', ' ', $meeting->meeting_type))); ?></td>
                        <td>
                            <?php echo date('M j, Y', strtotime($meeting->meeting_date)); ?><br>
                            <small><?php echo date('g:i A', strtotime($meeting->meeting_time)); ?></small>
                        </td>
                        <td>
                            <span class="status-<?php echo esc_attr($meeting->status); ?>" style="
                                padding: 4px 8px; 
                                border-radius: 4px; 
                                font-size: 12px; 
                                font-weight: 500;
                                <?php 
                                switch($meeting->status) {
                                    case 'pending': echo 'background: #fff3cd; color: #856404;'; break;
                                    case 'confirmed': echo 'background: #d1ecf1; color: #0c5460;'; break;
                                    case 'completed': echo 'background: #d4edda; color: #155724;'; break;
                                    case 'cancelled': echo 'background: #f8d7da; color: #721c24;'; break;
                                    default: echo 'background: #e2e3e5; color: #383d41;';
                                }
                                ?>
                            ">
                                <?php echo esc_html(ucfirst($meeting->status)); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($meeting->created_at)); ?></td>
                        <td>
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('update_meeting_status'); ?>
                                <input type="hidden" name="meeting_id" value="<?php echo $meeting->id; ?>">
                                <select name="status" onchange="this.form.submit()">
                                    <option value="pending" <?php selected($meeting->status, 'pending'); ?>>Pending</option>
                                    <option value="confirmed" <?php selected($meeting->status, 'confirmed'); ?>>Confirmed</option>
                                    <option value="completed" <?php selected($meeting->status, 'completed'); ?>>Completed</option>
                                    <option value="cancelled" <?php selected($meeting->status, 'cancelled'); ?>>Cancelled</option>
                                </select>
                                <input type="hidden" name="update_meeting_status" value="1">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
