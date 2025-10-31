<?php
if (!defined('ABSPATH')) exit;
if (is_user_logged_in()) {
    echo '<div style="max-width: 500px; margin: 40px auto; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
        <h3 style="color: #3f5340; margin: 0 0 16px 0;">Welcome Back!</h3>
        <p style="margin: 0 0 20px 0; color: #666;">You are already logged in.</p>
        <a href="' . home_url('/student-dashboard/') . '" style="display: inline-block; background: #8ebb79; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px;">Go to Dashboard</a>
    </div>';
    return;
}
?>
<style>
.tfsp-registration-container { max-width: 500px; margin: 40px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.tfsp-reg-card { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
.tfsp-reg-header { background: #3f5340; color: white; padding: 30px; text-align: center; }
.tfsp-reg-header h2 { margin: 0; font-size: 24px; color: white; }
.tfsp-reg-form { padding: 30px; }
.tfsp-form-group { margin-bottom: 16px; }
.tfsp-label { display: block; margin-bottom: 6px; font-weight: 500; color: #333; font-size: 14px; }
.tfsp-input { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 15px; box-sizing: border-box; }
.tfsp-input:focus { outline: none; border-color: #8ebb79; }
.tfsp-submit { width: 100%; background: #8ebb79; color: white; border: none; padding: 12px; border-radius: 4px; font-size: 16px; font-weight: 500; cursor: pointer; }
.tfsp-submit:hover { background: #3a543f; }
.tfsp-message { margin-top: 16px; padding: 12px; border-radius: 4px; display: none; }
.tfsp-message.success { background: #d4edda; color: #155724; }
.tfsp-message.error { background: #f8d7da; color: #721c24; }
</style>

<div class="tfsp-registration-container">
    <div class="tfsp-reg-card">
        <div class="tfsp-reg-header">
            <h2 style="color: white !important;">Welcome to the YCAM Mentorship Program Student Portal</h2>
        </div>
        <div class="tfsp-reg-form">
            <form id="student-registration-form">
                <div class="tfsp-form-group">
                    <label class="tfsp-label">First Name</label>
                    <input type="text" name="first_name" class="tfsp-input" required>
                </div>
                <div class="tfsp-form-group">
                    <label class="tfsp-label">Last Name</label>
                    <input type="text" name="last_name" class="tfsp-input" required>
                </div>
                <div class="tfsp-form-group">
                    <label class="tfsp-label">Student Email</label>
                    <input type="email" name="email" class="tfsp-input" required>
                </div>
                <div class="tfsp-form-group">
                    <label class="tfsp-label">Student Phone</label>
                    <input type="tel" name="student_phone" class="tfsp-input" required>
                </div>
                <div class="tfsp-form-group">
                    <label class="tfsp-label">Parent Name</label>
                    <input type="text" name="parent_name" class="tfsp-input" required>
                </div>
                <div class="tfsp-form-group">
                    <label class="tfsp-label">Parent Email</label>
                    <input type="email" name="parent_email" class="tfsp-input" required>
                </div>
                <div class="tfsp-form-group">
                    <label class="tfsp-label">Parent Phone</label>
                    <input type="tel" name="parent_phone" class="tfsp-input" required>
                </div>
                <div class="tfsp-form-group">
                    <label class="tfsp-label">Classification</label>
                    <select name="classification" class="tfsp-input" required>
                        <option value="">Select Grade</option>
                        <option value="freshman">Freshman</option>
                        <option value="sophomore">Sophomore</option>
                        <option value="junior">Junior</option>
                        <option value="senior">Senior</option>
                    </select>
                </div>
                <div class="tfsp-form-group">
                    <label class="tfsp-label">Cohort Year</label>
                    <select name="cohort_year" class="tfsp-input" required>
                        <option value="">Select Cohort</option>
                        <option value="Group One, 2025 to 2026">Group One, 2025 to 2026</option>
                        <option value="Group Two, 2026 to 2027">Group Two, 2026 to 2027</option>
                        <option value="Group Three, 2027 to 2028">Group Three, 2027 to 2028</option>
                    </select>
                </div>                <div class="tfsp-form-group">
                    <label class="tfsp-label">Polo Shirt Size</label>
                    <select name="shirt_size" class="tfsp-input" required>
                        <option value="">Select Size</option>
                        <option value="XS">XS</option>
                        <option value="S">S</option>
                        <option value="M">M</option>
                        <option value="L">L</option>
                        <option value="XL">XL</option>
                        <option value="XXL">XXL</option>
                    </select>
                </div>
                <div class="tfsp-form-group">
                    <label class="tfsp-label">Password</label>
                    <input type="password" name="password" class="tfsp-input" required minlength="6">
                </div>
                <button type="submit" class="tfsp-submit">Create Account</button>
                <div id="registration-message" class="tfsp-message"></div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('student-registration-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('.tfsp-submit');
    const msg = document.getElementById('registration-message');
    btn.disabled = true;
    btn.textContent = 'Creating Account...';
    
    const formData = new FormData(this);
    formData.append('action', 'tfsp_register_student');
    formData.append('nonce', '<?php echo wp_create_nonce('tfsp_register_nonce'); ?>');
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        msg.style.display = 'block';
        if (data.success) {
            msg.className = 'tfsp-message success';
            msg.textContent = data.data.message;
            setTimeout(() => window.location.href = data.data.redirect_url, 1500);
        } else {
            msg.className = 'tfsp-message error';
            msg.textContent = data.data;
            btn.disabled = false;
            btn.textContent = 'Create Account';
        }
    })
    .catch(() => {
        msg.style.display = 'block';
        msg.className = 'tfsp-message error';
        msg.textContent = 'Registration failed. Please try again.';
        btn.disabled = false;
        btn.textContent = 'Create Account';
    });
});
</script>
