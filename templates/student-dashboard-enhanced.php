<?php
if (!defined('ABSPATH')) {
    exit;
}

// Include all components
require_once(TFSP_PLUGIN_PATH . 'templates/components/student-header.php');
require_once(TFSP_PLUGIN_PATH . 'templates/components/welcome-section.php');
require_once(TFSP_PLUGIN_PATH . 'templates/components/challenges-section.php');
require_once(TFSP_PLUGIN_PATH . 'templates/components/roadmap-section.php');
require_once(TFSP_PLUGIN_PATH . 'templates/components/document-hub.php');
require_once(TFSP_PLUGIN_PATH . 'templates/components/advisor-section.php');
require_once(TFSP_PLUGIN_PATH . 'templates/components/resources-section.php');

// Get current user
$current_user = wp_get_current_user();
if (!$current_user->ID) {
    wp_redirect(home_url('/student-login/'));
    exit;
}
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Dashboard - Turner Foundation</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            line-height: 1.6;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .main-content {
            padding-bottom: 50px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 0 15px;
            }
            
            .main-content {
                padding-bottom: 30px;
            }
        }
        
        /* Loading Animation */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #8BC34A;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Smooth Animations */
        .fade-in {
            animation: fadeIn 0.6s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Success/Error Messages */
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 500;
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
        
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Header Section -->
        <?php render_student_header($current_user); ?>
        
        <div class="main-content">
            <!-- Welcome Section -->
            <div class="fade-in">
                <?php render_welcome_section($current_user); ?>
            </div>
            
            <!-- Challenges Section -->
            <div class="fade-in">
                <?php render_challenges_section($current_user->ID); ?>
            </div>
            
            <!-- College Application Roadmap -->
            <div class="fade-in">
                <?php render_roadmap_section($current_user->ID); ?>
            </div>
            
            <!-- Document Management Hub -->
            <div class="fade-in">
                <?php render_document_hub($current_user->ID); ?>
            </div>
            
            <!-- College Advisor Section -->
            <div class="fade-in">
                <?php render_advisor_section($current_user->ID); ?>
            </div>
            
            <!-- Resources & Tools -->
            <div class="fade-in">
                <?php render_resources_section(); ?>
            </div>
        </div>
    </div>
    
    <!-- Global JavaScript -->
    <script>
        // Global dashboard functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add fade-in animation to sections
            const sections = document.querySelectorAll('.fade-in');
            sections.forEach((section, index) => {
                setTimeout(() => {
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Initialize tooltips and interactive elements
            initializeInteractiveElements();
        });
        
        function initializeInteractiveElements() {
            // Add click handlers for roadmap cards
            document.querySelectorAll('.roadmap-card .status-btn.pending').forEach(btn => {
                btn.addEventListener('click', function() {
                    const card = this.closest('.roadmap-card');
                    const title = card.querySelector('h4').textContent;
                    
                    if (confirm(`Start working on "${title}"?`)) {
                        this.textContent = '⏳ In Progress';
                        this.className = 'status-btn in-progress';
                        
                        // Add input field for details
                        const input = document.createElement('input');
                        input.type = 'text';
                        input.placeholder = 'Enter details...';
                        input.className = 'detail-input';
                        this.parentNode.appendChild(input);
                        
                        // Save to database (implement AJAX call)
                        updateRoadmapStatus(card.dataset.stepId, 'in-progress');
                    }
                });
            });
            
            // Add progress tracking
            trackUserActivity();
        }
        
        function updateRoadmapStatus(stepId, status) {
            // AJAX call to update roadmap status
            jQuery.post(ajaxurl, {
                action: 'update_roadmap_status',
                step_id: stepId,
                status: status,
                nonce: '<?php echo wp_create_nonce('roadmap_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    showMessage('Progress updated successfully!', 'success');
                    updateProgressCircle();
                } else {
                    showMessage('Error updating progress. Please try again.', 'error');
                }
            });
        }
        
        function updateProgressCircle() {
            // Recalculate and update progress circle
            const circle = document.querySelector('.progress-circle');
            // Implementation for updating progress percentage
        }
        
        function showMessage(text, type) {
            const message = document.createElement('div');
            message.className = `message ${type}`;
            message.textContent = text;
            
            document.querySelector('.main-content').prepend(message);
            
            setTimeout(() => {
                message.remove();
            }, 5000);
        }
        
        function trackUserActivity() {
            // Track user interactions for analytics
            document.addEventListener('click', function(e) {
                const target = e.target;
                
                // Track button clicks
                if (target.classList.contains('status-btn') || 
                    target.classList.contains('schedule-btn') ||
                    target.classList.contains('upload-btn')) {
                    
                    // Send analytics data
                    console.log('User interaction:', target.className, target.textContent);
                }
            });
        }
        
        // Auto-save functionality for input fields
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('detail-input')) {
                clearTimeout(e.target.saveTimeout);
                e.target.saveTimeout = setTimeout(() => {
                    // Auto-save input value
                    console.log('Auto-saving:', e.target.value);
                }, 2000);
            }
        });
    </script>
</body>
</html>
