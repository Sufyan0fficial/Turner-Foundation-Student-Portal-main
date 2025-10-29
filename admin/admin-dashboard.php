<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>Turner Foundation Student Portal</h1>
    
    <div class="tfsp-admin-wrap">
        <!-- Quick Stats -->
        <div class="tfsp-stats-grid">
            <div class="stat-box">
                <h3>Total Students</h3>
                <span class="stat-number" id="total-students">0</span>
            </div>
            <div class="stat-box">
                <h3>Active Assignments</h3>
                <span class="stat-number" id="active-assignments">0</span>
            </div>
            <div class="stat-box">
                <h3>Pending Submissions</h3>
                <span class="stat-number" id="pending-submissions">0</span>
            </div>
            <div class="stat-box">
                <h3>Scheduled Meetings</h3>
                <span class="stat-number" id="scheduled-meetings">0</span>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="tfsp-quick-actions">
            <h2>⚡ Quick Actions</h2>
            <div class="action-buttons">
                <a href="<?php echo admin_url('admin.php?page=tfsp-students'); ?>" class="tfsp-admin-btn">👥 Manage Students</a>
                <a href="<?php echo admin_url('admin.php?page=tfsp-documents'); ?>" class="tfsp-admin-btn">📋 Review Documents</a>
                <a href="<?php echo admin_url('admin.php?page=tfsp-meetings'); ?>" class="tfsp-admin-btn">📅 Manage Meetings</a>
                <a href="<?php echo admin_url('admin.php?page=tfsp-settings'); ?>" class="tfsp-admin-btn">⚙️ Portal Settings</a>
            </div>
        </div>

        <!-- Documentation Section -->
        <div class="tfsp-documentation">
            <h2>📚 How to Use the Student Portal</h2>
            
            <div class="doc-section">
                <h3>🎯 For Students - How to Access Features</h3>
                <div class="doc-content">
                    <h4>1. Student Dashboard</h4>
                    <p><strong>Shortcode:</strong> <code>[student_dashboard]</code></p>
                    <p>Add this shortcode to any page/post where students should access their dashboard.</p>
                    
                    <h4>2. College Applications</h4>
                    <p><strong>Shortcode:</strong> <code>[college_applications]</code></p>
                    <p>Add this shortcode to display the college application tracking interface.</p>
                    
                    <h4>Student Features:</h4>
                    <ul>
                        <li>✅ View progress overview with percentages</li>
                        <li>📝 Access and submit assignments</li>
                        <li>📤 Upload documents and files</li>
                        <li>📅 Schedule one-on-one meetings</li>
                        <li>🎓 Track college applications</li>
                        <li>📊 Monitor completion status</li>
                    </ul>
                </div>
            </div>

            <div class="doc-section">
                <h3>👨‍💼 For Administrators - Admin Features</h3>
                <div class="doc-content">
                    <h4>Admin Dashboard Access:</h4>
                    <ul>
                        <li>WordPress Admin → <strong>Student Portal</strong> (main menu)</li>
                        <li>Or use shortcode: <code>[admin_dashboard]</code></li>
                    </ul>
                    
                    <h4>Admin Capabilities:</h4>
                    <ul>
                        <li>👥 View all students and their progress</li>
                        <li>📋 Create and manage assignments</li>
                        <li>📁 Access document portal</li>
                        <li>📅 Manage meeting schedules</li>
                        <li>📊 Generate progress reports</li>
                        <li>🎓 Track college applications</li>
                    </ul>
                </div>
            </div>

            <div class="doc-section">
                <h3>🚀 Quick Setup Guide</h3>
                <div class="doc-content">
                    <h4>Step 1: Create Student Pages</h4>
                    <ol>
                        <li>Go to <strong>Pages → Add New</strong></li>
                        <li>Title: "Student Dashboard"</li>
                        <li>Content: <code>[student_dashboard]</code></li>
                        <li>Publish the page</li>
                    </ol>
                    
                    <h4>Step 2: Create College Applications Page</h4>
                    <ol>
                        <li>Go to <strong>Pages → Add New</strong></li>
                        <li>Title: "College Applications"</li>
                        <li>Content: <code>[college_applications]</code></li>
                        <li>Publish the page</li>
                    </ol>
                    
                    <h4>Step 3: Set User Roles</h4>
                    <ol>
                        <li>Go to <strong>Users → All Users</strong></li>
                        <li>Edit student users</li>
                        <li>Set Role to "Student" or "Subscriber"</li>
                        <li>Students can now access their dashboard</li>
                    </ol>
                </div>
            </div>

            <div class="doc-section">
                <h3>🔧 College Application Features</h3>
                <div class="doc-content">
                    <p>The college application system tracks:</p>
                    <div class="feature-grid">
                        <div class="feature-item">
                            <h4>📄 Academic Resume</h4>
                            <p>Track completion and upload documents</p>
                        </div>
                        <div class="feature-item">
                            <h4>✍️ Personal Essays</h4>
                            <p>Manage essay submissions and progress</p>
                        </div>
                        <div class="feature-item">
                            <h4>📝 Recommendation Letters</h4>
                            <p>Track Letter 1 & Letter 2 separately</p>
                        </div>
                        <div class="feature-item">
                            <h4>📜 Transcripts</h4>
                            <p>Official transcript management</p>
                        </div>
                        <div class="feature-item">
                            <h4>💰 Financial Aid (FAFSA)</h4>
                            <p>Application tracking and documentation</p>
                        </div>
                        <div class="feature-item">
                            <h4>🤝 Community Service</h4>
                            <p>Hours logging and verification</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="doc-section">
                <h3>📊 Default Assignments</h3>
                <div class="doc-content">
                    <p>The system comes with 6 pre-configured assignments:</p>
                    <ol>
                        <li><strong>Course Introduction & Setup</strong> - Due in 7 days</li>
                        <li><strong>Project Planning Document</strong> - Due in 14 days</li>
                        <li><strong>Weekly Progress Quiz</strong> - Due in 21 days</li>
                        <li><strong>Market Research Assignment</strong> - Due in 28 days</li>
                        <li><strong>Mid-Course Presentation</strong> - Due in 35 days</li>
                        <li><strong>Practical Application Exercise</strong> - Due in 42 days</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="tfsp-recent-activity">
            <h2>Recent Activity</h2>
            <div id="recent-activity-list">
                <p>Loading recent activity...</p>
            </div>
        </div>
    </div>
</div>

<style>
.tfsp-admin-wrap {
    max-width: 1200px;
}

.tfsp-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-box {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-box h3 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 14px;
}

.stat-number {
    font-size: 32px;
    font-weight: bold;
    color: #0073aa;
    display: block;
}

.tfsp-quick-actions {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin: 20px 0;
}

.action-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.action-buttons .tfsp-admin-btn {
    flex: 1;
    min-width: 200px;
    text-align: center;
    padding: 15px 20px;
    font-size: 14px;
    font-weight: 600;
}

.tfsp-documentation {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin: 20px 0;
}

.doc-section {
    margin: 20px 0;
    padding: 15px;
    border-left: 4px solid #0073aa;
    background: #f9f9f9;
}

.doc-content {
    margin-top: 10px;
}

.doc-content code {
    background: #f1f1f1;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.feature-item {
    background: #fff;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
}

.feature-item h4 {
    margin: 0 0 8px 0;
    color: #0073aa;
}

.tfsp-recent-activity {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin: 20px 0;
}

@media (max-width: 768px) {
    .action-buttons {
        flex-direction: column;
    }
    
    .feature-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Load admin stats
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'tfsp_get_admin_stats',
            nonce: '<?php echo wp_create_nonce('tfsp_nonce'); ?>'
        },
        success: function(response) {
            if (response.success) {
                $('#total-students').text(response.data.total_students || 0);
                $('#active-assignments').text(response.data.active_assignments || 0);
                $('#pending-submissions').text(response.data.pending_submissions || 0);
                $('#scheduled-meetings').text(response.data.scheduled_meetings || 0);
            }
        }
    });
});
</script>
