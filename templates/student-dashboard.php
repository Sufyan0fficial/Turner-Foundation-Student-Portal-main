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

// Get user's progress from database
$user_progress = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tfsp_progress WHERE user_id = %d",
    $user_id
), OBJECT_K);

// Get Calendly settings from database - Fixed to match admin settings
$calendly_link = get_option('tfsp_calendly_url', 'https://calendly.com/abraizbashir/30min');
$meeting_title = get_option('tfsp_meeting_title', '1-on-1 Coaching Session');
$meeting_description = get_option('tfsp_meeting_description', 'Book your personalized college and career coaching session with our expert mentors.');

// Linear progress tracking as per requirement.png
$progress_steps = array(
    'academic_resume' => array(
        'title' => 'Academic Resume',
        'question' => 'What is the goal?',
        'color' => '#8B5CF6', // Purple
        'status' => 'not_started'
    ),
    'personal_essay' => array(
        'title' => 'Personal Essay', 
        'question' => 'How will you present yourself?',
        'color' => '#A855F7', // Purple variant
        'status' => 'not_started'
    ),
    'recommendation' => array(
        'title' => 'Recommendation Letters',
        'question' => 'How will others present you?',
        'color' => '#EC4899', // Pink
        'status' => 'not_started'
    ),
    'transcript' => array(
        'title' => 'Transcript',
        'question' => 'Why is this important?',
        'color' => '#F97316', // Orange
        'status' => 'not_started'
    ),
    'financial_aid' => array(
        'title' => 'Financial Aid',
        'question' => 'What is the goal?',
        'color' => '#3B82F6', // Blue
        'status' => 'not_started'
    ),
    'community_service' => array(
        'title' => 'Community Service',
        'question' => 'What is the goal?',
        'color' => '#6366F1', // Indigo
        'status' => 'not_started'
    ),
    'college_list' => array(
        'title' => 'Create Interest List of Colleges',
        'question' => 'How are you showing progress?',
        'color' => '#8B5CF6', // Purple
        'status' => 'not_started'
    ),
    'college_tours' => array(
        'title' => 'College Tours',
        'question' => 'How are you showing the goal?',
        'color' => '#DC2626', // Red
        'status' => 'not_started'
    ),
    'fafsa' => array(
        'title' => 'FAFSA',
        'question' => 'Why is this important?',
        'color' => '#F59E0B', // Amber
        'status' => 'not_started'
    ),
    'college_admissions_tests' => array(
        'title' => 'College Admissions Tests',
        'question' => 'What do you want to achieve?',
        'color' => '#3B82F6', // Blue
        'status' => 'not_started'
    )
);

// Update status based on saved progress from database
$step_index = 0;
foreach ($progress_steps as $key => &$step) {
    if (isset($user_progress[$step_index]) && $user_progress[$step_index]->status === 'completed') {
        $step['status'] = 'completed';
    }
    $step_index++;
}

// Update status based on documents and applications
foreach ($documents as $doc) {
    if (isset($progress_steps[$doc->document_type])) {
        if ($doc->status === 'approved') {
            $progress_steps[$doc->document_type]['status'] = 'completed';
        } elseif ($doc->status === 'pending') {
            $progress_steps[$doc->document_type]['status'] = 'in_progress';
        }
    }
}

// Calculate overall progress
$total_steps = count($progress_steps);
$completed_steps = 0;
foreach ($progress_steps as $step) {
    if ($step['status'] === 'completed') {
        $completed_steps++;
    }
}
$progress_percentage = $total_steps > 0 ? round(($completed_steps / $total_steps) * 100) : 0;
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

* { margin: 0; padding: 0; box-sizing: border-box; }

body, .dashboard-container, .tfsp-dashboard {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
    background: #FFFFFF !important;
    color: #000000 !important;
    line-height: 1.5 !important;
    min-height: 100vh !important;
}

.header {
    background: #8BC34A !important;
    color: #FFFFFF !important;
    padding: 20px 0 !important;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1) !important;
}

.header-content {
    max-width: 1200px !important;
    margin: 0 auto !important;
    padding: 0 20px !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
}

.header h1 {
    color: #FFFFFF !important;
    font-size: 24px !important;
    font-weight: 700 !important;
    margin: 0 !important;
}

.user-info {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    color: #FFFFFF !important;
}

.user-avatar {
    width: 40px !important;
    height: 40px !important;
    border-radius: 50% !important;
    background: rgba(255,255,255,0.2) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-weight: 600 !important;
    color: #FFFFFF !important;
}

.btn, button, input[type="submit"], .calendly-button {
    background: #8BC34A !important;
    color: #FFFFFF !important;
    padding: 12px 24px !important;
    border: none !important;
    border-radius: 8px !important;
    font-family: 'Inter', sans-serif !important;
    font-weight: 500 !important;
    cursor: pointer !important;
    text-decoration: none !important;
    display: inline-block !important;
    transition: all 0.2s ease !important;
}

.btn:hover, button:hover, .calendly-button:hover {
    background: #689F38 !important;
    color: #FFFFFF !important;
    text-decoration: none !important;
}

.btn-secondary {
    background: transparent !important;
    color: #FFFFFF !important;
    border: 2px solid #FFFFFF !important;
}

.btn-secondary:hover {
    background: #FFFFFF !important;
    color: #8BC34A !important;
}

.main-content {
    max-width: 1200px !important;
    margin: 0 auto !important;
    padding: 30px 20px !important;
}

.tf-card, .progress-card, .card, .calendly-section, .academic-challenges {
    background: #FFFFFF !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 12px !important;
    padding: 24px !important;
    margin-bottom: 20px !important;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
}

.progress-stats {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
    gap: 20px !important;
    margin-bottom: 30px !important;
}

.stat-card {
    background: #FFFFFF !important;
    padding: 20px !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
    text-align: center !important;
    border: 1px solid #e5e7eb !important;
}

.stat-number {
    font-size: 32px !important;
    font-weight: 700 !important;
    color: #8BC34A !important;
    display: block !important;
}

.stat-label {
    color: #333333 !important;
    font-size: 12px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    margin-top: 8px !important;
}

.progress-bar {
    background: #e5e7eb !important;
    border-radius: 10px !important;
    height: 8px !important;
    overflow: hidden !important;
    margin: 16px 0 !important;
}

.progress-fill {
    background: #8BC34A !important;
    height: 100% !important;
    transition: width 0.3s ease !important;
}

h1, h2, h3, h4, h5, h6 {
    color: #000000 !important;
    font-family: 'Inter', sans-serif !important;
    font-weight: 600 !important;
    margin-bottom: 16px !important;
}

.tf-section {
    margin-bottom: 40px !important;
}

.tf-text-center { text-align: center !important; }
.tf-mb-md { margin-bottom: 16px !important; }
.tf-mt-sm { margin-top: 8px !important; }

@media (max-width: 768px) {
    .header-content {
        flex-direction: column !important;
        gap: 16px !important;
        text-align: center !important;
    }
    .progress-stats {
        grid-template-columns: 1fr !important;
    }
}
</style>

<div class="dashboard-container">
    <header class="header">
        <div class="header-content">
            <h1>Turner Foundation Student Portal</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($current_user->display_name, 0, 1)); ?>
                </div>
                <span>Welcome, <?php echo esc_html($current_user->display_name); ?></span>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <!-- Progress Overview -->
        <section class="tf-section">
            <h2>Your Progress Overview</h2>
            <div class="progress-stats">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $completed_steps; ?>/<?php echo $total_steps; ?></span>
                    <span class="stat-label">Steps Completed</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo count($documents); ?></span>
                    <span class="stat-label">Documents Uploaded</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo count($meetings); ?></span>
                    <span class="stat-label">Meetings Scheduled</span>
                </div>
            </div>
            
            <div class="tf-card">
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
                    <a href="javascript:void(0)" onclick="openCalendlyPopup()" class="btn">
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
                <div class="tf-card">
                    <h4><?php echo esc_html($step['title']); ?></h4>
                    <?php if (isset($step['question'])): ?>
                    <p style="color: #333333; font-size: 14px;"><?php echo esc_html($step['question']); ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</div>
        }
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        /* Academic Challenges */
        .academic-challenges {
            background: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .academic-challenges h3 {
            color: #1e293b;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .academic-challenges h3::before {
            content: '🎯';
            font-size: 28px;
        }
        
        .challenges-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .challenge-card {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 25px;
            border-radius: 12px;
            border-left: 4px solid #8ebb79;
        }
        
        .challenge-card h4 {
            color: #1e293b;
            margin-bottom: 8px;
            font-size: 18px;
            font-weight: 600;
        }
        
        .challenge-card p {
            color: #64748b;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .challenge-progress {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .progress-bar {
            flex: 1;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #8ebb79 0%, #3a543f 100%);
            transition: width 0.3s ease;
        }
        
        .challenge-progress span {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }
        
        /* Challenge Section - Most Important */
        .challenge-section {
            background: linear-gradient(135deg, #8ebb79 0%, #3a543f 100%);
            color: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 40px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .challenge-section h2 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 16px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            color: white;
        }
        
        .challenge-section p {
            font-size: 18px;
            opacity: 0.95;
            max-width: 600px;
            margin: 0 auto 24px;
            line-height: 1.6;
            color: white;
        }
        
        .challenge-cta {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 12px 32px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .challenge-cta:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            color: white;
        }
        
        /* Progress Overview */
        .progress-overview {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .progress-header h3 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .progress-header h3::before {
            content: '📊';
            font-size: 28px;
        }
        
        .progress-stats {
            text-align: right;
        }
        
        .progress-percentage {
            font-size: 32px;
            font-weight: 800;
            color: #10b981;
            line-height: 1;
        }
        
        .progress-label {
            font-size: 14px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Linear Progress Tracking */
        .progress-timeline {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin: 40px 0;
        }
        
        .progress-step {
            background: white;
            border-radius: 12px;
            padding: 24px;
            width: 280px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            border-top: 4px solid;
            position: relative;
        }
        
        .progress-step.completed {
            background: linear-gradient(135deg, #8ebb79 0%, #3a543f 100%);
            color: white;
        }
        
        .progress-step.completed .step-title,
        .progress-step.completed .step-question {
            color: white;
        }
        
        .progress-step:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .step-header {
            margin-bottom: 16px;
        }
        
        .step-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .step-question {
            font-size: 14px;
            color: #64748b;
            line-height: 1.4;
        }
        
        .step-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }
        
        .step-btn {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: white;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        
        .step-btn.completed {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }
        
        .step-btn.in-progress {
            background: #f59e0b;
            color: white;
            border-color: #f59e0b;
        }
        
        .step-btn:hover {
            transform: translateY(-1px);
        }
        
        .step-status {
            position: absolute;
            top: -8px;
            right: 16px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: white;
        }
        
        .step-status.completed {
            background: #10b981;
        }
        
        .step-status.in_progress {
            background: #f59e0b;
        }
        
        .step-status.not_started {
            background: #94a3b8;
        }
        
        /* External Links */
        .external-links {
            background: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .external-links h3 {
            color: #1e293b;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .external-links h3::before {
            content: '🔗';
            font-size: 28px;
        }
        
        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .link-card {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 25px;
            border-radius: 12px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            border-left: 4px solid #8ebb79;
            display: block;
        }
        
        .link-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            text-decoration: none;
            color: inherit;
        }
        
        .link-icon {
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        .link-card h4 {
            color: #1e293b;
            margin-bottom: 8px;
            font-size: 18px;
            font-weight: 600;
        }
        
        .link-card p {
            color: #64748b;
            margin-bottom: 10px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .link-url {
            color: #8ebb79;
            font-size: 12px;
            font-weight: 500;
        }
        
        .hbcu-link {
            border-left-color: #10B981;
        }
        
        .common-app-link {
            border-left-color: #3B82F6;
        }
        
        .fafsa-link {
            border-left-color: #F59E0B;
        }
        
        .templates-link {
            border-left-color: #8B5CF6;
            cursor: default;
        }
        
        .templates-link:hover {
            transform: none;
        }
        
        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-top: 4px solid #667eea;
        }
        
        .card h3 {
            color: #1e293b;
            margin-bottom: 24px;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .card h3::before {
            content: '📄';
            font-size: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        
        .form-input, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%);
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Calendly Integration */
        .calendly-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .calendly-section h3 {
            color: #1e293b;
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .calendly-section h3::before {
            content: '📅';
            font-size: 24px;
        }
        
        .calendly-embed {
            min-height: 600px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        /* Attendance Section */
        .attendance-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .attendance-section h3 {
            color: #1e293b;
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .attendance-section h3::before {
            content: '📋';
            font-size: 24px;
        }
        
        .attendance-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .attendance-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 4px solid #10b981;
        }
        
        .attendance-date {
            font-weight: 600;
            color: #1e293b;
        }
        
        .attendance-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .attendance-status.present {
            background: #dcfce7;
            color: #166534;
        }
        
        .attendance-status.absent {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Resources Section */
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }
        
        .resource-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
            text-decoration: none;
            color: #1e293b;
            font-weight: 500;
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
        }
        
        .resource-link:hover {
            background: #f1f5f9;
            transform: translateY(-2px);
            color: #1e293b;
        }
        
        .resource-icon {
            font-size: 20px;
        }
        
        /* Messages */
        .message {
            margin-top: 16px;
            padding: 12px 16px;
            border-radius: 8px;
            display: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .message.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .progress-timeline {
                flex-direction: column;
                align-items: center;
            }
            
            .progress-step {
                width: 100%;
                max-width: 400px;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            
            .main-content {
                padding: 20px 16px;
            }
            
            .challenge-section {
                padding: 30px 20px;
            }
            
            .challenge-section h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <div class="header-content">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($current_user->first_name, 0, 1) . substr($current_user->last_name, 0, 1)); ?>
                    </div>
                    <span><?php echo esc_html($current_user->display_name); ?></span>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>

        <div class="main-content">
            <!-- Challenge Section -->
            <div class="challenge-section">
                <h2>🎯 Your College Journey Awaits</h2>
                <p>Complete your progress steps below to unlock your path to higher education success.</p>
                <a href="#progress" class="challenge-cta">Start Your Journey</a>
            </div>
            
            <!-- Academic Challenges Section -->
            <div class="academic-challenges">
                <h3>Academic Challenges</h3>
                <p style="margin-bottom: 20px; color: #64748b;">Complete these challenges to advance in your college application journey.</p>
                
                <div class="challenges-grid">
                    <?php
                    // Get dynamic challenges from database
                    global $wpdb;
                    $current_user_id = get_current_user_id();
                    $challenges = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tfsp_challenges WHERE active = 1 ORDER BY created_at DESC");
                    
                    if ($challenges): 
                        foreach ($challenges as $challenge): 
                            // Calculate progress based on challenge type
                            $progress_percentage = 0;
                            
                            if (stripos($challenge->title, 'resume') !== false) {
                                // Check if resume step is completed
                                $resume_progress = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d AND step_key = 'academic_resume' AND status = 'completed'",
                                    $current_user_id
                                ));
                                $progress_percentage = $resume_progress > 0 ? 100 : 0;
                                
                            } elseif (stripos($challenge->title, 'scholarship') !== false) {
                                // Check documents uploaded
                                $docs_count = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_documents WHERE user_id = %d AND status = 'approved'",
                                    $current_user_id
                                ));
                                $progress_percentage = min(($docs_count * 20), 100); // 20% per document, max 100%
                                
                            } elseif (stripos($challenge->title, 'fafsa') !== false) {
                                // Check if FAFSA step is completed
                                $fafsa_progress = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d AND step_key = 'fafsa' AND status = 'completed'",
                                    $current_user_id
                                ));
                                $progress_percentage = $fafsa_progress > 0 ? 100 : 0;
                                
                            } elseif (stripos($challenge->title, 'college research') !== false || stripos($challenge->title, 'research') !== false) {
                                // Check if college list step is completed
                                $research_progress = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d AND step_key = 'college_list' AND status = 'completed'",
                                    $current_user_id
                                ));
                                $progress_percentage = $research_progress > 0 ? 100 : 0;
                                
                            } elseif (stripos($challenge->title, 'personal statement') !== false || stripos($challenge->title, 'essay') !== false) {
                                // Check if essay step is completed
                                $essay_progress = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d AND step_key = 'personal_essay' AND status = 'completed'",
                                    $current_user_id
                                ));
                                $progress_percentage = $essay_progress > 0 ? 100 : 0;
                                
                            } elseif (stripos($challenge->title, 'recommendation') !== false || stripos($challenge->title, 'letter') !== false) {
                                // Check if recommendation step is completed
                                $rec_progress = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d AND step_key = 'recommendation' AND status = 'completed'",
                                    $current_user_id
                                ));
                                $progress_percentage = $rec_progress > 0 ? 100 : 0;
                            }
                            ?>
                            <div class="challenge-card">
                                <div class="challenge-header">
                                    <h4><?php echo esc_html($challenge->title); ?></h4>
                                    <span class="difficulty-badge difficulty-<?php echo strtolower($challenge->difficulty); ?>">
                                        <?php echo ucfirst($challenge->difficulty); ?>
                                    </span>
                                </div>
                                <div class="challenge-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
                                    </div>
                                    <span class="progress-text" style="color: <?php echo $progress_percentage == 100 ? '#10b981' : '#8BC34A'; ?>; font-weight: bold;">
                                        <?php echo $progress_percentage; ?>%
                                    </span>
                                </div>
                                <p class="challenge-description"><?php echo esc_html($challenge->description); ?></p>
                                <?php if ($progress_percentage == 100): ?>
                                    <div class="challenge-completed">🎉 Completed!</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach;
                    else: ?>
                        <p>No challenges available.</p>
                    <?php endif;
                    if (!is_array($progress_steps)) {
                        $progress_steps = array();
                    }
                    
                    // Calculate progress for each challenge
                    $challenge1_steps = array('academic_resume', 'personal_essay', 'recommendation', 'transcript');
                    $challenge2_steps = array('financial_aid', 'community_service', 'college_list');
                    $challenge3_steps = array('college_tours', 'fafsa', 'college_admissions_tests');
                    
                    $challenge1_completed = 0;
                    $challenge2_completed = 0;
                    $challenge3_completed = 0;
                    
                    foreach($challenge1_steps as $step) {
                        if(isset($progress_steps[$step]) && is_array($progress_steps[$step]) && $progress_steps[$step]['status'] === 'completed') {
                            $challenge1_completed++;
                        }
                    }
                    
                    foreach($challenge2_steps as $step) {
                        if(isset($progress_steps[$step]) && is_array($progress_steps[$step]) && $progress_steps[$step]['status'] === 'completed') {
                            $challenge2_completed++;
                        }
                    }
                    
                    foreach($challenge3_steps as $step) {
                        if(isset($progress_steps[$step]) && is_array($progress_steps[$step]) && $progress_steps[$step]['status'] === 'completed') {
                            $challenge3_completed++;
                        }
                    }
                    
                    $challenge1_percent = count($challenge1_steps) > 0 ? round(($challenge1_completed / count($challenge1_steps)) * 100) : 0;
                    $challenge2_percent = count($challenge2_steps) > 0 ? round(($challenge2_completed / count($challenge2_steps)) * 100) : 0;
                    $challenge3_percent = count($challenge3_steps) > 0 ? round(($challenge3_completed / count($challenge3_steps)) * 100) : 0;
                    ?>
                    
                    <div class="challenge-card">
                        <h4>Challenge 1</h4>
                        <p>Complete your academic foundation</p>
                        <div class="challenge-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $challenge1_percent; ?>%"></div>
                            </div>
                            <span><?php echo $challenge1_completed; ?>/<?php echo count($challenge1_steps); ?> tasks completed</span>
                        </div>
                    </div>
                    
                    <div class="challenge-card">
                        <h4>Challenge 2</h4>
                        <p>Build your application portfolio</p>
                        <div class="challenge-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $challenge2_percent; ?>%"></div>
                            </div>
                            <span><?php echo $challenge2_completed; ?>/<?php echo count($challenge2_steps); ?> tasks completed</span>
                        </div>
                    </div>
                    
                    <div class="challenge-card">
                        <h4>Challenge 3</h4>
                        <p>Finalize college applications</p>
                        <div class="challenge-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $challenge3_percent; ?>%"></div>
                            </div>
                            <span><?php echo $challenge3_completed; ?>/<?php echo count($challenge3_steps); ?> tasks completed</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Overview -->
            <div class="progress-overview">
                <div class="progress-header">
                    <h3>📊 Your Progress Journey</h3>
                    <div class="progress-stats">
                        <div class="progress-percentage"><?php echo $progress_percentage; ?>%</div>
                        <div class="progress-label">Complete</div>
                    </div>
                </div>
                
                <!-- Linear Progress Timeline (<?php echo count($progress_steps); ?> items) -->
                <div class="progress-timeline" id="progress">
                    <?php 
                    $step_counter = 1;
                    foreach ($progress_steps as $key => $step): 
                    ?>
                        <div class="progress-step <?php echo $step['status']; ?>" style="border-top-color: <?php echo $step['color']; ?>">
                            <div class="step-status <?php echo $step['status']; ?>">
                                <?php if ($step['status'] === 'completed'): ?>
                                    ✓
                                <?php elseif ($step['status'] === 'in_progress'): ?>
                                    ⏳
                                <?php else: ?>
                                    <?php echo $step_counter; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="step-header">
                                <div class="step-title"><?php echo $step_counter; ?>. <?php echo esc_html($step['title']); ?></div>
                                <div class="step-question"><?php echo esc_html($step['question']); ?></div>
                            </div>
                            
                            <div class="step-actions">
                                <button class="step-btn <?php echo $step['status'] === 'completed' ? 'completed' : ''; ?>" 
                                        data-step="<?php echo $key; ?>" data-action="completed">
                                    ✅ Completed
                                </button>
                                <button class="step-btn <?php echo $step['status'] === 'in_progress' ? 'in-progress' : ''; ?>" 
                                        data-step="<?php echo $key; ?>" data-action="in_progress">
                                    ⏳ In Progress
                                </button>
                            </div>
                        </div>
                    <?php 
                    $step_counter++;
                    endforeach; 
                    ?>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Document Upload -->
                <div class="card">
                    <h3>📄 Upload Documents</h3>
                    <form id="documentUploadForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label">Document Type</label>
                            <select class="form-select" id="documentType" name="document_type" required>
                                <option value="">Select document type</option>
                                <option value="academic_resume">Academic Resume</option>
                                <option value="personal_essay">Personal Essay</option>
                                <option value="recommendation_letters">Recommendation Letters</option>
                                <option value="transcript">Transcript</option>
                                <option value="financial_aid">Financial Aid</option>
                                <option value="community_service">Community Service</option>
                                <option value="college_list">College Interest List</option>
                                <option value="college_tours">College Tours</option>
                                <option value="fafsa">FAFSA</option>
                                <option value="college_admissions_tests">College Admissions Tests</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Choose File</label>
                            <input type="file" id="documentFile" name="document" class="form-input" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small style="color: #64748b; font-size: 12px; margin-top: 4px; display: block;">
                                Supported formats: PDF, DOC, DOCX, JPG, PNG (Max 10MB)
                            </small>
                        </div>
                        <button type="submit" class="btn">Upload Document</button>
                    </form>
                    <div id="uploadMessage" class="message"></div>
                </div>
        
        .task-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .task-item:last-child {
            border-bottom: none;
        }
        
        .task-checkbox {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            border: 2px solid #bdc3c7;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .task-checkbox.completed {
            background: #27ae60;
            border-color: #27ae60;
            color: white;
        }
        
        .task-text {
            flex: 1;
            font-size: 14px;
            color: #333333;
        }
        
        .task-text.completed {
            text-decoration: line-through;
            color: #95a5a6;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #333333;
            font-size: 14px;
        }
        
        .tfsp-dashboard .form-input, .tfsp-dashboard .form-select {
            width: 100%;
            padding: 12px;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .tfsp-dashboard .form-input:focus, .tfsp-dashboard .form-select:focus {
            outline: none;
            border-color: #8ebb79;
            box-shadow: 0 0 0 2px rgba(142, 187, 121, 0.1);
        }
        
        .btn {
            background: #8ebb79;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            width: 100%;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #3a543f;
        }
        
        .btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
        
        .document-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .document-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .document-item:last-child {
            border-bottom: none;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending { background: #f39c12; color: white; }
        .status-approved { background: #27ae60; color: white; }
        .status-rejected { background: #e74c3c; color: white; }
        
        .notification {
            background: #d4edda;
            color: #155724;
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #27ae60;
            position: relative;
            padding-right: 40px;
        }
        
        .notification-close {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #155724;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .notification-close:hover {
            background: rgba(0,0,0,0.1);
            border-radius: 50%;
        }
        
        .message {
            margin-top: 15px;
            padding: 12px;
            border-radius: 4px;
            display: none;
            font-size: 14px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .logout-btn:hover {
            background: #c0392b;
            color: white;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .main-content {
                padding: 20px 15px;
            }
        }
    </style>
    <div class="dashboard-container tfsp-dashboard">
        <div class="main-content">
            <!-- Calendly Integration -->
            <div class="calendly-section">
                <h3>📅 Schedule Your <?php echo esc_html($meeting_title); ?></h3>
                <p style="margin-bottom: 20px; color: #64748b;"><?php echo esc_html($meeting_description); ?></p>
                
                <!-- Calendly Button - Opens in new tab -->
                <div style="text-align: center; padding: 20px;">
                    <a href="<?php echo esc_url($calendly_link); ?>" 
                       target="_blank" 
                       class="calendly-button"
                       style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 16px 32px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 16px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); transition: all 0.3s ease;"
                       onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(102, 126, 234, 0.6)';"
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(102, 126, 234, 0.4)';">
                        🗓️ Schedule Meeting Now
                    </a>
                </div>
                
                <div style="text-align: center; margin-top: 16px; color: #64748b; font-size: 14px;">
                    <p>Click the button above to open your personalized scheduling link</p>
                </div>
            </div>

            <!-- Attendance Tracking -->
            <div class="attendance-section">
                <h3>📋 Your Attendance Record</h3>
                <p style="margin-bottom: 20px; color: #64748b;">View your session attendance history (read-only).</p>
                
                <div class="attendance-list">
                    <?php
                    // Get actual attendance data from database
                    $attendance_records = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}tfsp_meetings WHERE user_id = %d AND status = 'completed' ORDER BY meeting_date DESC LIMIT 10",
                        $user_id
                    ), ARRAY_A);
                    
                    // If no real data, show sample data
                    if (empty($attendance_records)) {
                        $attendance_records = array(
                            array('date' => '2024-09-15', 'status' => 'present', 'session' => 'College Planning Session'),
                            array('date' => '2024-09-08', 'status' => 'present', 'session' => 'Essay Review'),
                            array('date' => '2024-09-01', 'status' => 'absent', 'session' => 'Financial Aid Workshop'),
                            array('date' => '2024-08-25', 'status' => 'present', 'session' => 'Application Strategy'),
                        );
                    }
                    
                    if (empty($attendance_records)): ?>
                        <div style="text-align: center; padding: 40px; color: #64748b;">
                            <p>No attendance records yet. Your session history will appear here.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($attendance_records as $record): ?>
                            <div class="attendance-item">
                                <div>
                                    <div class="attendance-date"><?php echo date('M j, Y', strtotime($record['date'] ?? $record['meeting_date'])); ?></div>
                                    <div style="font-size: 14px; color: #64748b;"><?php echo esc_html($record['session'] ?? $record['meeting_title']); ?></div>
                                </div>
                                <span class="attendance-status <?php echo $record['status']; ?>">
                                    <?php echo ucfirst($record['status']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- External Application Links -->
            <div class="external-links">
                <h3>🔗 Application Portals & Resources</h3>
                <div class="links-grid">
                    <a href="https://commonblackcollegeapp.com/" target="_blank" class="link-card hbcu-link">
                        <div class="link-icon">🎓</div>
                        <h4>HBCU Common App</h4>
                        <p>Apply to multiple HBCUs with one application</p>
                        <span class="link-url">commonblackcollegeapp.com</span>
                    </a>
                    
                    <a href="https://www.commonapp.org/" target="_blank" class="link-card common-app-link">
                        <div class="link-icon">📝</div>
                        <h4>Common Application</h4>
                        <p>Apply to 900+ colleges and universities</p>
                        <span class="link-url">commonapp.org</span>
                    </a>
                    
                    <a href="https://studentaid.gov/h/apply-for-aid/fafsa" target="_blank" class="link-card fafsa-link">
                        <div class="link-icon">💰</div>
                        <h4>FAFSA Application</h4>
                        <p>Apply for federal financial aid</p>
                        <span class="link-url">studentaid.gov</span>
                    </a>
                    
                    <div class="link-card templates-link">
                        <div class="link-icon">📋</div>
                        <h4>Templates / How-to's</h4>
                        <p>Guides and templates for applications</p>
                        <span class="link-url">Available below</span>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- My Documents -->
                <div class="card">
                    <h3>📁 My Documents</h3>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php if (empty($documents)): ?>
                            <div style="text-align: center; padding: 40px; color: #64748b;">
                                <p>No documents uploaded yet.</p>
                                <small>Upload your first document to get started!</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($documents as $doc): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid #f1f5f9;">
                                    <div>
                                        <div style="font-weight: 600; color: #1e293b; margin-bottom: 4px;">
                                            <?php echo esc_html($doc->file_name); ?>
                                        </div>
                                        <div style="font-size: 12px; color: #64748b;">
                                            <?php echo esc_html(ucwords(str_replace('_', ' ', $doc->document_type))); ?> • 
                                            <?php echo date('M j, Y', strtotime($doc->upload_date)); ?>
                                        </div>
                                    </div>
                                    <span style="padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; 
                                                 background: <?php echo $doc->status === 'approved' ? '#dcfce7' : ($doc->status === 'pending' ? '#fef3c7' : '#fee2e2'); ?>;
                                                 color: <?php echo $doc->status === 'approved' ? '#166534' : ($doc->status === 'pending' ? '#92400e' : '#991b1b'); ?>;">
                                        <?php echo esc_html(ucfirst($doc->status)); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Progress Step Tracking
        document.querySelectorAll('.step-btn').forEach(button => {
            button.addEventListener('click', function() {
                const step = this.dataset.step;
                const action = this.dataset.action;
                
                // Update UI immediately
                const stepElement = this.closest('.progress-step');
                const statusElement = stepElement.querySelector('.step-status');
                const allBtns = stepElement.querySelectorAll('.step-btn');
                
                // Remove all status classes
                stepElement.classList.remove('completed', 'in_progress', 'not_started');
                allBtns.forEach(btn => btn.classList.remove('completed', 'in-progress'));
                
                if (action === 'completed') {
                    statusElement.className = 'step-status completed';
                    statusElement.textContent = '✓';
                    this.classList.add('completed');
                    stepElement.classList.add('completed');
                } else if (action === 'in_progress') {
                    statusElement.className = 'step-status in_progress';
                    statusElement.textContent = '⏳';
                    this.classList.add('in-progress');
                    stepElement.classList.add('in_progress');
                }
                
                // Update progress percentage
                updateProgressPercentage();
                
                // Send AJAX request to save progress
                const formData = new FormData();
                formData.append('action', 'tfsp_update_step_progress');
                formData.append('step', step);
                formData.append('status', action === 'completed' ? 'completed' : 'in_progress');
                formData.append('nonce', '<?php echo wp_create_nonce('tfsp_progress_nonce'); ?>');
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Progress updated successfully');
                    } else {
                        console.error('Failed to update progress:', data.data);
                    }
                })
                .catch(error => {
                    console.error('Error updating progress:', error);
                });
            });
        });
        
        // Update progress percentage
        function updateProgressPercentage() {
            const totalSteps = document.querySelectorAll('.progress-step').length;
            const completedSteps = document.querySelectorAll('.step-status.completed').length;
            const percentage = Math.round((completedSteps / totalSteps) * 100);
            
            const percentageElement = document.querySelector('.progress-percentage');
            if (percentageElement) {
                percentageElement.textContent = percentage + '%';
            }
        }

        // Enhanced Document Upload with Progress Tracking
        document.getElementById('documentUploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            const messageDiv = document.getElementById('uploadMessage');
            
            // Show progress indicator
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span style="display: inline-flex; align-items: center; gap: 8px;"><span class="spinner"></span>Uploading...</span>';
            
            // Clear previous messages
            messageDiv.style.display = 'none';
            messageDiv.className = 'message';
            
            const formData = new FormData(this);
            formData.append('action', 'tfsp_upload_general_document');
            formData.append('nonce', '<?php echo wp_create_nonce('tfsp_nonce'); ?>');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                messageDiv.style.display = 'block';
                
                if (data.success) {
                    messageDiv.className = 'message success';
                    messageDiv.innerHTML = '<strong>✓ Success!</strong> ' + data.data;
                    this.reset();
                    
                    // Show success animation
                    submitBtn.innerHTML = '<span style="color: #065f46;">✓ Uploaded Successfully!</span>';
                    
                    // Refresh page after 2 seconds to show new document and updated progress
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    messageDiv.className = 'message error';
                    messageDiv.innerHTML = '<strong>✗ Error:</strong> ' + (data.data || 'Upload failed. Please try again.');
                }
            })
            .catch(error => {
                messageDiv.style.display = 'block';
                messageDiv.className = 'message error';
                messageDiv.innerHTML = '<strong>✗ Error:</strong> Upload failed. Please check your connection and try again.';
                console.error('Upload error:', error);
            })
            .finally(() => {
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }, 1000);
            });
        });
        
        // Smooth scroll to progress section when challenge CTA is clicked
        document.querySelector('.challenge-cta').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('progress').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });
        
        // Auto-hide success messages after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.message.success').forEach(msg => {
                if (msg.style.display === 'block') {
                    msg.style.display = 'none';
                }
            });
        }, 5000);
    });
    </script>

<!-- Calendly Popup Modal -->
<div id="calendlyModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 900px; height: 80%; background: white; border-radius: 12px; overflow: hidden;">
        <div style="padding: 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <h3>📅 Schedule Meeting</h3>
            <button onclick="closeCalendlyPopup()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <div id="calendlyContainer" style="height: calc(100% - 80px);">
            <!-- Calendly will be loaded here -->
        </div>
    </div>
</div>

<script type="text/javascript" src="https://assets.calendly.com/assets/external/widget.js" async></script>
<script>
function openCalendlyPopup() {
    const modal = document.getElementById('calendlyModal');
    const container = document.getElementById('calendlyContainer');
    
    // Clear previous content
    container.innerHTML = '';
    
    // Create Calendly widget
    const calendlyDiv = document.createElement('div');
    calendlyDiv.className = 'calendly-inline-widget';
    calendlyDiv.setAttribute('data-url', '<?php echo esc_js($calendly_link); ?>?hide_event_type_details=1&hide_gdpr_banner=1');
    calendlyDiv.style.width = '100%';
    calendlyDiv.style.height = '100%';
    
    container.appendChild(calendlyDiv);
    
    // Show modal
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeCalendlyPopup() {
    const modal = document.getElementById('calendlyModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
    
    // Clear Calendly content
    document.getElementById('calendlyContainer').innerHTML = '';
}

// Close modal when clicking outside or pressing Escape
document.addEventListener('click', function(e) {
    const modal = document.getElementById('calendlyModal');
    if (e.target === modal) {
        closeCalendlyPopup();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCalendlyPopup();
    }
});
</script>
<script>
// Working progress update function
function updateProgress(stepOrder, status, button) {
    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'tfsp_update_progress',
        step_order: stepOrder,
        status: status,
        nonce: '<?php echo wp_create_nonce('tfsp_nonce'); ?>'
    }, function(response) {
        if (response.success) {
            alert('Progress updated successfully!');
            location.reload();
        } else {
            alert('Error updating progress');
        }
    }).fail(function() {
        alert('Network error. Please try again.');
    });
}
</script>
