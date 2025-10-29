<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get all students
global $wpdb;
$students_table = $wpdb->prefix . 'tfsp_students';

$students = $wpdb->get_results("
    SELECT s.*, u.user_registered
    FROM $students_table s
    LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
    ORDER BY s.created_at DESC
");

// Get counts
$total_students = $wpdb->get_var("SELECT COUNT(*) FROM $students_table");
$total_applications = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_applications");
$completed_applications = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_applications WHERE status IN ('accepted', 'rejected')");
?>

<div class="wrap">
    <h1 class="wp-heading-inline">👥 Students Management</h1>
    <button id="add-student-btn" class="page-title-action">Add New Student</button>
    
    <!-- Add Student Modal -->
    <div id="add-student-modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 500px; border-radius: 8px;">
            <span id="close-modal" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            <h2>Add New Student</h2>
            <form id="add-student-form">
                <table class="form-table">
                    <tr>
                        <th><label>First Name</label></th>
                        <td><input type="text" name="first_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label>Last Name</label></th>
                        <td><input type="text" name="last_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label>Email</label></th>
                        <td><input type="email" name="email" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label>Password</label></th>
                        <td><input type="password" name="password" class="regular-text" required minlength="6"></td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button-primary">Add Student</button>
                    <button type="button" id="cancel-add" class="button">Cancel</button>
                </p>
            </form>
            <div id="add-student-message" style="margin-top: 15px; padding: 10px; border-radius: 4px; display: none;"></div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 20px 0;">
        <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #0073aa;">
            <h4 style="margin: 0 0 5px 0; color: #666;">Total Students</h4>
            <p style="font-size: 24px; font-weight: bold; color: #0073aa; margin: 0;"><?php echo $total_students; ?></p>
        </div>
        <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #f56e28;">
            <h4 style="margin: 0 0 5px 0; color: #666;">Total Applications</h4>
            <p style="font-size: 24px; font-weight: bold; color: #f56e28; margin: 0;"><?php echo $total_applications; ?></p>
        </div>
        <div style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #00a32a;">
            <h4 style="margin: 0 0 5px 0; color: #666;">Completed Applications</h4>
            <p style="font-size: 24px; font-weight: bold; color: #00a32a; margin: 0;"><?php echo $completed_applications; ?></p>
        </div>
    </div>

    <!-- Students Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column">ID</th>
                <th scope="col" class="manage-column">Student Name</th>
                <th scope="col" class="manage-column">Email</th>
                <th scope="col" class="manage-column">Status</th>
                <th scope="col" class="manage-column">Registered</th>
                <th scope="col" class="manage-column">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($students)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px;">
                        <div style="color: #666;">
                            <p style="font-size: 18px; margin: 0;">👥</p>
                            <p><strong>No Students Found</strong></p>
                            <p>Students will appear here when they register for the portal.</p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo esc_html($student->student_id); ?></td>
                        <td><strong><?php echo esc_html($student->first_name . ' ' . $student->last_name); ?></strong></td>
                        <td><?php echo esc_html($student->email); ?></td>
                        <td>
                            <span style="
                                padding: 4px 8px; 
                                border-radius: 4px; 
                                font-size: 12px; 
                                font-weight: 500;
                                background: #d4edda; 
                                color: #155724;
                            ">
                                <?php echo esc_html(ucfirst($student->status)); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($student->created_at)); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=tfsp-student-detail&student_id=' . $student->user_id); ?>" 
                               class="button button-small">
                                👁️ View Details
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const modal = document.getElementById("add-student-modal");
    const btn = document.getElementById("add-student-btn");
    const span = document.getElementById("close-modal");
    const cancel = document.getElementById("cancel-add");
    const form = document.getElementById("add-student-form");
    const message = document.getElementById("add-student-message");
    
    btn.onclick = function() { modal.style.display = "block"; }
    span.onclick = function() { modal.style.display = "none"; }
    cancel.onclick = function() { modal.style.display = "none"; }
    
    form.addEventListener("submit", function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append("action", "tfsp_add_student");
        formData.append("nonce", "<?php echo wp_create_nonce('tfsp_add_student_nonce'); ?>");
        
        fetch(ajaxurl, {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            message.style.display = "block";
            if (data.success) {
                message.style.background = "#d4edda";
                message.style.color = "#155724";
                message.style.border = "1px solid #c3e6cb";
                message.textContent = data.data;
                form.reset();
                setTimeout(() => {
                    modal.style.display = "none";
                    location.reload();
                }, 1500);
            } else {
                message.style.background = "#f8d7da";
                message.style.color = "#721c24";
                message.style.border = "1px solid #f5c6cb";
                message.textContent = data.data;
            }
        })
        .catch(error => {
            message.style.display = "block";
            message.style.background = "#f8d7da";
            message.style.color = "#721c24";
            message.style.border = "1px solid #f5c6cb";
            message.textContent = "Error: " + error.message;
        });
    });
});
</script>
