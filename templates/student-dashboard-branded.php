<?php
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

$current_user = wp_get_current_user();
$user_id = get_current_user_id();

// Get user's documents and progress
global $wpdb;
$documents = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tfsp_documents WHERE user_id = %d ORDER BY upload_date DESC",
    $user_id
));

$meetings = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tfsp_meetings WHERE user_id = %d ORDER BY created_at DESC LIMIT 3",
    $user_id
));

$applications = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tfsp_applications WHERE user_id = %d ORDER BY created_at DESC",
    $user_id
));

// Get Calendly settings
$calendly_link = get_option('tfsp_calendly_url', 'https://calendly.com/abraizbashir/30min');
$meeting_title = get_option('tfsp_meeting_title', '1-on-1 Coaching Session');
$meeting_description = get_option('tfsp_meeting_description', 'Book your personalized college and career coaching session with our expert mentors.');

// Progress steps
$progress_steps = array(
    'academic_resume' => array('title' => 'Academic Resume', 'status' => 'not_started'),
    'personal_essay' => array('title' => 'Personal Essay', 'status' => 'not_started'),
    'recommendation' => array('title' => 'Recommendation Letters', 'status' => 'not_started'),
    'transcript' => array('title' => 'Transcript', 'status' => 'not_started'),
    'financial_aid' => array('title' => 'Financial Aid', 'status' => 'not_started'),
    'community_service' => array('title' => 'Community Service', 'status' => 'not_started'),
    'college_list' => array('title' => 'Create Interest List of Colleges', 'status' => 'not_started'),
    'college_tours' => array('title' => 'College Tours', 'status' => 'not_started'),
    'fafsa' => array('title' => 'FAFSA', 'status' => 'not_started'),
    'college_admissions_tests' => array('title' => 'College Admissions Tests', 'status' => 'not_started')
);

// Calculate progress
$total_steps = count($progress_steps);
$completed_steps = count(array_filter($progress_steps, function($step) { return $step['status'] === 'completed'; }));
$progress_percentage = $total_steps > 0 ? round(($completed_steps / $total_steps) * 100) : 0;
?>

<div class="dashboard-container">
    <header class="header">
        <div class="header-content">
            <h1>Turner Foundation Student Portal</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($current_user->display_name, 0, 1)); ?>
                </div>
                <span>Welcome, <?php echo esc_html($current_user->display_name); ?></span>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn tf-btn-secondary">Logout</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <!-- Progress Overview -->
        <section class="progress-overview tf-section">
            <h2>Your Progress Overview</h2>
            <div class="progress-stats tf-grid tf-grid-3">
                <div class="stat-card tf-card">
                    <span class="stat-number"><?php echo $completed_steps; ?>/<?php echo $total_steps; ?></span>
                    <span class="stat-label">Steps Completed</span>
                </div>
                <div class="stat-card tf-card">
                    <span class="stat-number"><?php echo count($documents); ?></span>
                    <span class="stat-label">Documents Uploaded</span>
                </div>
                <div class="stat-card tf-card">
                    <span class="stat-number"><?php echo count($meetings); ?></span>
                    <span class="stat-label">Meetings Scheduled</span>
                </div>
            </div>
            
            <div class="tf-card tf-mt-md">
                <h3>Overall Progress</h3>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
                </div>
                <p class="tf-mt-sm"><?php echo $progress_percentage; ?>% Complete</p>
            </div>
        </section>

        <!-- Meeting Scheduling -->
        <section class="tf-section">
            <div class="tf-card">
                <h3>📅 Schedule Your <?php echo esc_html($meeting_title); ?></h3>
                <p class="tf-mb-md"><?php echo esc_html($meeting_description); ?></p>
                <div class="tf-text-center">
                    <a href="<?php echo esc_url($calendly_link); ?>" target="_blank" class="btn tf-btn">
                        🗓️ Schedule Meeting Now
                    </a>
                </div>
            </div>
        </section>

        <!-- Progress Steps -->
        <section class="tf-section">
            <h2>College Preparation Steps</h2>
            <div class="progress-steps">
                <?php foreach ($progress_steps as $key => $step): ?>
                <div class="step-item <?php echo $step['status']; ?>">
                    <div class="step-header">
                        <h4 class="step-title"><?php echo esc_html($step['title']); ?></h4>
                        <span class="status-badge status-<?php echo $step['status']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $step['status'])); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Recent Documents -->
        <?php if (!empty($documents)): ?>
        <section class="tf-section">
            <h2>Recent Documents</h2>
            <div class="tf-grid tf-grid-2">
                <?php foreach (array_slice($documents, 0, 4) as $doc): ?>
                <div class="tf-card">
                    <h4><?php echo esc_html($doc->document_name); ?></h4>
                    <p class="tf-mb-sm">Type: <?php echo esc_html($doc->document_type); ?></p>
                    <span class="status-badge status-<?php echo $doc->status; ?>">
                        <?php echo ucfirst($doc->status); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Quick Actions -->
        <section class="tf-section">
            <h2>Quick Actions</h2>
            <div class="tf-grid tf-grid-3">
                <div class="tf-card tf-text-center">
                    <h4>📄 Upload Document</h4>
                    <p class="tf-mb-md">Upload your academic documents</p>
                    <button class="btn tf-btn" onclick="openUploadModal()">Upload</button>
                </div>
                <div class="tf-card tf-text-center">
                    <h4>📝 College Applications</h4>
                    <p class="tf-mb-md">Manage your college applications</p>
                    <a href="#applications" class="btn tf-btn">View Applications</a>
                </div>
                <div class="tf-card tf-text-center">
                    <h4>💬 Get Help</h4>
                    <p class="tf-mb-md">Contact your mentor</p>
                    <a href="mailto:support@turnerfoundation.org" class="btn tf-btn">Contact Support</a>
                </div>
            </div>
        </section>
    </main>
</div>

<script>
function openUploadModal() {
    // Add upload functionality here
    alert('Upload functionality would be implemented here');
}
</script>
