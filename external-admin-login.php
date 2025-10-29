<?php
if (!defined('ABSPATH')) {
    $wp_load = dirname(__FILE__, 4) . '/wp-load.php';
    if (file_exists($wp_load)) {
        require_once $wp_load;
    } else {
        require_once dirname(__FILE__) . '/../../../../wp-load.php';
    }
}

// Check if already logged in
session_start();
if (isset($_SESSION['tfsp_admin_id']) && isset($_SESSION['tfsp_admin_username'])) {
    wp_safe_redirect(home_url('/portal-admin/'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tfsp_login_nonce'])) {
    if (!wp_verify_nonce($_POST['tfsp_login_nonce'], 'tfsp_external_admin_login')) {
        $error = 'Security check failed. Please try again.';
    } else {
        global $wpdb;
        
        $username = sanitize_text_field($_POST['log']);
        $password = $_POST['pwd'];
        
        $admin = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tfsp_portal_admins WHERE username = %s AND is_active = 1",
            $username
        ));
        
        if ($admin && password_verify($password, $admin->password)) {
            // Update last login
            $wpdb->update(
                $wpdb->prefix . 'tfsp_portal_admins',
                array('last_login' => current_time('mysql')),
                array('id' => $admin->id)
            );
            
            // Set session
            $_SESSION['tfsp_admin_id'] = $admin->id;
            $_SESSION['tfsp_admin_username'] = $admin->username;
            $_SESSION['tfsp_admin_name'] = $admin->full_name;
            $_SESSION['tfsp_admin_email'] = $admin->email;
            
            wp_safe_redirect(home_url('/portal-admin/'));
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>YCAM Admin Portal Login</title>
    <style>
        :root { --brand:#3f5340; --brand2:#8ebb79; }
        body { margin:0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif; background:linear-gradient(135deg,#3f5340 0%,#8ebb79 100%); color:#111827; min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .login-container { background:#fff; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.2); width:90%; max-width:400px; padding:40px; }
        .login-header { text-align:center; margin-bottom:30px; }
        .login-header h1 { margin:0 0 8px; color:var(--brand); font-size:24px; }
        .login-header p { margin:0; color:#6b7280; font-size:14px; }
        .form-group { margin-bottom:20px; }
        .form-group label { display:block; margin-bottom:6px; font-weight:500; color:#374151; font-size:14px; }
        .form-group input { width:100%; padding:12px; border:1px solid #d1d5db; border-radius:6px; font-size:15px; box-sizing:border-box; }
        .form-group input:focus { outline:none; border-color:var(--brand2); box-shadow:0 0 0 3px rgba(142,187,121,0.1); }
        .error { background:#fee2e2; color:#991b1b; padding:12px; border-radius:6px; margin-bottom:20px; font-size:14px; }
        .btn-login { width:100%; background:var(--brand2); color:#fff; border:none; padding:14px; border-radius:6px; font-size:16px; font-weight:600; cursor:pointer; transition:background 0.2s; }
        .btn-login:hover { background:var(--brand); }
        .login-footer { text-align:center; margin-top:20px; font-size:13px; color:#6b7280; }
        .checkbox-group { display:flex; align-items:center; gap:8px; }
        .checkbox-group input { width:auto; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🎓 YCAM Admin Portal</h1>
            <p>Mentorship Program Administration</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo esc_html($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <?php wp_nonce_field('tfsp_external_admin_login', 'tfsp_login_nonce'); ?>
            
            <div class="form-group">
                <label for="log">Username</label>
                <input type="text" name="log" id="log" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="pwd">Password</label>
                <input type="password" name="pwd" id="pwd" required>
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" name="rememberme" id="rememberme" value="1">
                    <label for="rememberme" style="margin:0; font-weight:normal;">Remember Me</label>
                </div>
            </div>
            
            <button type="submit" class="btn-login">Sign In</button>
        </form>
        
        <div class="login-footer">
            <p>YCAM Mentorship Program Student Portal v3.0</p>
        </div>
    </div>
</body>
</html>
