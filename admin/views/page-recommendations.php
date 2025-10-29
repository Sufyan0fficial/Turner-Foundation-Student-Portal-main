<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get recommendation settings
global $wpdb;
$settings = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}tfsp_advisor_settings WHERE setting_key IN ('recommendation_link', 'recommendation_email')", OBJECT_K);
$rec_link = $settings['recommendation_link']->setting_value ?? '';
$rec_email = $settings['recommendation_email']->setting_value ?? '';

// Get all students for recommendation tracking
$students = get_users(array('role' => 'subscriber'));
?>

<style>
.recommendations-header { background: linear-gradient(135deg, #D2527F 0%, #E74C3C 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 25px; }
.recommendations-header h1 { margin: 0 0 8px 0; font-size: 28px; font-weight: 700; }
.process-info { background: white; padding: 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
.process-links { display: flex; gap: 15px; margin: 15px 0; }
.process-link { background: #667eea; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; }
.process-link:hover { background: #764ba2; color: white; text-decoration: none; }
.process-link.email { background: #28a745; }
.process-link.email:hover { background: #218838; }
.students-grid { display: grid; gap: 20px; }
.student-rec-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
.student-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
.student-name { font-size: 18px; font-weight: 600; color: #333; margin: 0; }
.student-email { color: #666; font-size: 14px; }
.rec-status { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
.rec-item { padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #ddd; }
.rec-item.pending { border-left-color: #ffc107; }
.rec-item.received { border-left-color: #28a745; }
.rec-item.missing { border-left-color: #dc3545; }
.rec-title { font-weight: 600; margin: 0 0 5px; }
.rec-details { font-size: 14px; color: #666; margin: 0; }
.share-links { background: #e3f2fd; padding: 15px; border-radius: 8px; margin-top: 15px; }
.share-links h4 { margin: 0 0 10px; color: #1976d2; }
.copy-link { background: #2196f3; color: white; padding: 6px 12px; border: none; border-radius: 4px; font-size: 12px; cursor: pointer; margin-left: 10px; }

@media (max-width: 768px) {
    .recommendations-header { padding: 20px; }
    .recommendations-header h1 { font-size: 22px; }
    .process-info { padding: 15px; }
    .process-links { flex-direction: column; }
    .process-link { text-align: center; }
    .rec-status { grid-template-columns: 1fr; }
    .student-header { flex-direction: column; align-items: flex-start; gap: 10px; }
    .share-links code { display: block; word-break: break-all; margin: 5px 0; }
    .copy-link { margin-left: 0; margin-top: 5px; }
}
</style>

<div class="recommendations-header">
    <h1>📝 Recommendation Management</h1>
    <p>Manage staff recommendation process and track submission status</p>
</div>

<div class="process-info">
    <h2>Staff Recommendation Process</h2>
    <p>Teachers and coaches should use the designated links below to submit recommendation letters. Students cannot upload these documents directly.</p>
    
    <?php if ($rec_link || $rec_email): ?>
        <div class="process-links">
            <?php if ($rec_link): ?>
                <a href="<?php echo esc_url($rec_link); ?>" target="_blank" class="process-link">
                    📋 Open Recommendation Form
                </a>
            <?php endif; ?>
            
            <?php if ($rec_email): ?>
                <a href="mailto:<?php echo esc_attr($rec_email); ?>" class="process-link email">
                    ✉️ Email: <?php echo esc_html($rec_email); ?>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="share-links">
            <h4>Share with Staff Members:</h4>
            <?php if ($rec_link): ?>
                <div style="margin: 5px 0;">
                    <strong>Form Link:</strong> 
                    <code style="background: white; padding: 4px 8px; border-radius: 4px;"><?php echo esc_html($rec_link); ?></code>
                    <button class="copy-link" onclick="copyToClipboard('<?php echo esc_js($rec_link); ?>')">Copy</button>
                </div>
            <?php endif; ?>
            
            <?php if ($rec_email): ?>
                <div style="margin: 5px 0;">
                    <strong>Email Address:</strong> 
                    <code style="background: white; padding: 4px 8px; border-radius: 4px;"><?php echo esc_html($rec_email); ?></code>
                    <button class="copy-link" onclick="copyToClipboard('<?php echo esc_js($rec_email); ?>')">Copy</button>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 6px; color: #856404;">
            <strong>⚠️ Setup Required:</strong> Please configure the recommendation form link and/or email address in 
            <a href="?view=advisor">Advisor Settings</a> to enable the staff recommendation process.
        </div>
    <?php endif; ?>
</div>

<div class="process-info">
    <h2>Student Recommendation Status</h2>
    <p>Track recommendation letter submissions for each student</p>
    
    <div class="students-grid">
        <?php if (empty($students)): ?>
            <div style="text-align: center; color: #666; padding: 40px;">
                No students registered yet.
            </div>
        <?php else: ?>
            <?php foreach ($students as $student): ?>
                <div class="student-rec-card">
                    <div class="student-header">
                        <div>
                            <h3 class="student-name"><?php echo esc_html($student->display_name); ?></h3>
                            <div class="student-email"><?php echo esc_html($student->user_email); ?></div>
                        </div>
                        <a href="?view=students&student_id=<?php echo $student->ID; ?>" style="color: #667eea; text-decoration: none; font-size: 14px;">
                            View Details →
                        </a>
                    </div>
                    
                    <div class="rec-status">
                        <div class="rec-item pending">
                            <div class="rec-title">📄 Letter 1</div>
                            <div class="rec-details">Status: Pending submission</div>
                        </div>
                        <div class="rec-item pending">
                            <div class="rec-title">📄 Letter 2</div>
                            <div class="rec-details">Status: Pending submission</div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 15px; font-size: 12px; color: #999;">
                        💡 Staff members should submit letters using the form/email above
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Copied to clipboard!');
    });
}
</script>
