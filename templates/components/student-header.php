<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_student_header($user) {
    $progress = get_student_overall_progress($user->ID);
    ?>
    <div class="student-header">
        <div class="header-left">
            <h1>🎓 Student Portal</h1>
        </div>
        <div class="header-right">
            <div class="progress-circle" data-progress="<?php echo $progress; ?>">
                <div class="progress-text"><?php echo $progress; ?>%<br><small>Complete</small></div>
            </div>
            <div class="user-info">
                <div class="user-avatar">👤</div>
                <div class="user-details">
                    <div class="user-name"><?php echo $user->display_name; ?></div>
                    <div class="user-role">Student Scholar</div>
                </div>
            </div>
            <div class="header-actions">
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="logout-btn">⚙️</a>
            </div>
        </div>
    </div>
    
    <style>
    .student-header {
        background: linear-gradient(135deg, #2d5016 0%, #3f5340 100%);
        color: white;
        padding: 20px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .header-left h1 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
    }
    .header-right {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .progress-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: conic-gradient(#8BC34A 0deg, #8BC34A calc(var(--progress) * 3.6deg), rgba(255,255,255,0.2) calc(var(--progress) * 3.6deg));
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    .progress-circle::before {
        content: '';
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #2d5016;
        position: absolute;
    }
    .progress-text {
        position: relative;
        z-index: 1;
        text-align: center;
        font-weight: bold;
        font-size: 14px;
    }
    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    .user-name {
        font-weight: 600;
        font-size: 16px;
    }
    .user-role {
        font-size: 12px;
        opacity: 0.8;
    }
    .logout-btn {
        color: white;
        text-decoration: none;
        font-size: 20px;
        padding: 8px;
        border-radius: 6px;
        transition: background 0.3s;
    }
    .logout-btn:hover {
        background: rgba(255,255,255,0.1);
    }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const circle = document.querySelector('.progress-circle');
        const progress = circle.dataset.progress;
        circle.style.setProperty('--progress', progress);
    });
    </script>
    <?php
}

function get_student_overall_progress($student_id) {
    global $wpdb;
    $total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d", $student_id));
    $completed = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d AND status = 'completed'", $student_id));
    return $total > 0 ? round(($completed / $total) * 100) : 0;
}
?>
