<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get all applications with correct column names
global $wpdb;
$applications_table = $wpdb->prefix . 'tfsp_applications';

$applications = $wpdb->get_results("
    SELECT a.*, u.display_name
    FROM $applications_table a
    LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
    ORDER BY a.deadline ASC
");

// Get counts by status
$total_apps = $wpdb->get_var("SELECT COUNT(*) FROM $applications_table");
$not_started = $wpdb->get_var("SELECT COUNT(*) FROM $applications_table WHERE status = 'planning'");
$in_progress = $wpdb->get_var("SELECT COUNT(*) FROM $applications_table WHERE status = 'in_progress'");
$under_review = $wpdb->get_var("SELECT COUNT(*) FROM $applications_table WHERE status = 'submitted'");
$completed = $wpdb->get_var("SELECT COUNT(*) FROM $applications_table WHERE status IN ('accepted', 'rejected')");

// Handle application status updates
if (isset($_POST['update_application_status']) && wp_verify_nonce($_POST['_wpnonce'], 'update_application_status')) {
    $application_id = intval($_POST['application_id']);
    $new_status = sanitize_text_field($_POST['status']);
    
    $wpdb->update(
        $applications_table,
        array('status' => $new_status),
        array('id' => $application_id),
        array('%s'),
        array('%d')
    );
    
    echo '<div class="notice notice-success"><p>Application status updated successfully!</p></div>';
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">📋 Applications Management</h1>
    
    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin: 20px 0;">
        <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #6c757d;">
            <h4 style="margin: 0 0 5px 0; color: #666;">Not Started</h4>
            <p style="font-size: 20px; font-weight: bold; color: #6c757d; margin: 0;"><?php echo $not_started; ?></p>
        </div>
        <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #f56e28;">
            <h4 style="margin: 0 0 5px 0; color: #666;">In Progress</h4>
            <p style="font-size: 20px; font-weight: bold; color: #f56e28; margin: 0;"><?php echo $in_progress; ?></p>
        </div>
        <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #0073aa;">
            <h4 style="margin: 0 0 5px 0; color: #666;">Under Review</h4>
            <p style="font-size: 20px; font-weight: bold; color: #0073aa; margin: 0;"><?php echo $under_review; ?></p>
        </div>
        <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #00a32a;">
            <h4 style="margin: 0 0 5px 0; color: #666;">Completed</h4>
            <p style="font-size: 20px; font-weight: bold; color: #00a32a; margin: 0;"><?php echo $completed; ?></p>
        </div>
        <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #0073aa;">
            <h4 style="margin: 0 0 5px 0; color: #666;">Total</h4>
            <p style="font-size: 20px; font-weight: bold; color: #0073aa; margin: 0;"><?php echo $total_apps; ?></p>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="nav-tab-wrapper" style="margin-bottom: 20px;">
        <a href="#" class="nav-tab nav-tab-active">All Applications (<?php echo $total_apps; ?>)</a>
        <a href="#" class="nav-tab">Not Started (<?php echo $not_started; ?>)</a>
        <a href="#" class="nav-tab">In Progress (<?php echo $in_progress; ?>)</a>
        <a href="#" class="nav-tab">Under Review (<?php echo $under_review; ?>)</a>
        <a href="#" class="nav-tab">Completed (<?php echo $completed; ?>)</a>
    </div>

    <!-- Applications Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column">Student</th>
                <th scope="col" class="manage-column">College/Program</th>
                <th scope="col" class="manage-column">Application Type</th>
                <th scope="col" class="manage-column">Status</th>
                <th scope="col" class="manage-column">Deadline</th>
                <th scope="col" class="manage-column">Last Updated</th>
                <th scope="col" class="manage-column">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($applications)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px;">
                        <div style="color: #666;">
                            <p style="font-size: 18px; margin: 0;">📋</p>
                            <p><strong>No Applications Found</strong></p>
                            <p>Applications will appear here when students start their college application process.</p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($applications as $application): ?>
                    <tr>
                        <td><strong><?php echo esc_html($application->display_name ?: 'Unknown'); ?></strong></td>
                        <td><?php echo esc_html($application->college_name); ?></td>
                        <td><?php echo esc_html(ucwords(str_replace('_', ' ', $application->application_type))); ?></td>
                        <td>
                            <span class="status-<?php echo esc_attr($application->status); ?>" style="
                                padding: 4px 8px; 
                                border-radius: 4px; 
                                font-size: 12px; 
                                font-weight: 500;
                                <?php 
                                switch($application->status) {
                                    case 'planning': echo 'background: #e2e3e5; color: #383d41;'; break;
                                    case 'in_progress': echo 'background: #fff3cd; color: #856404;'; break;
                                    case 'submitted': echo 'background: #d1ecf1; color: #0c5460;'; break;
                                    case 'accepted': echo 'background: #d4edda; color: #155724;'; break;
                                    case 'rejected': echo 'background: #f8d7da; color: #721c24;'; break;
                                    case 'waitlisted': echo 'background: #ffeaa7; color: #6c5ce7;'; break;
                                    default: echo 'background: #e2e3e5; color: #383d41;';
                                }
                                ?>
                            ">
                                <?php echo esc_html(ucwords(str_replace('_', ' ', $application->status))); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $deadline = strtotime($application->deadline);
                            $days_left = ceil(($deadline - time()) / (60 * 60 * 24));
                            echo date('M j, Y', $deadline);
                            if ($days_left > 0) {
                                echo '<br><small style="color: #f56e28;">(' . $days_left . ' days left)</small>';
                            } elseif ($days_left == 0) {
                                echo '<br><small style="color: #d63638; font-weight: bold;">Due Today!</small>';
                            } else {
                                echo '<br><small style="color: #d63638;">Overdue</small>';
                            }
                            ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($application->created_at)); ?></td>
                        <td>
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('update_application_status'); ?>
                                <input type="hidden" name="application_id" value="<?php echo $application->id; ?>">
                                <select name="status" onchange="this.form.submit()">
                                    <option value="planning" <?php selected($application->status, 'planning'); ?>>Planning</option>
                                    <option value="in_progress" <?php selected($application->status, 'in_progress'); ?>>In Progress</option>
                                    <option value="submitted" <?php selected($application->status, 'submitted'); ?>>Submitted</option>
                                    <option value="accepted" <?php selected($application->status, 'accepted'); ?>>Accepted</option>
                                    <option value="rejected" <?php selected($application->status, 'rejected'); ?>>Rejected</option>
                                    <option value="waitlisted" <?php selected($application->status, 'waitlisted'); ?>>Waitlisted</option>
                                </select>
                                <input type="hidden" name="update_application_status" value="1">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
