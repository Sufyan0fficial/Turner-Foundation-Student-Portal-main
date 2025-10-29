<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_welcome_section($user) {
    ?>
    <div class="welcome-section">
        <h2>Welcome back, <?php echo $user->display_name; ?>! 👋</h2>
        <p>Track your college application progress, upload important documents, and stay connected with your dedicated College and Career Coach. Every step you complete brings you closer to your dream college!</p>
    </div>
    
    <style>
    .welcome-section {
        background: linear-gradient(135deg, #f8fafc 0%, #e3f2fd 100%);
        padding: 30px;
        border-radius: 16px;
        margin: 30px 0;
        border-left: 5px solid #8BC34A;
    }
    .welcome-section h2 {
        margin: 0 0 15px 0;
        color: #2d5016;
        font-size: 28px;
        font-weight: 700;
    }
    .welcome-section p {
        margin: 0;
        color: #555;
        font-size: 16px;
        line-height: 1.6;
    }
    </style>
    <?php
}
?>
