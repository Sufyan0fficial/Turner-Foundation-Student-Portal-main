<?php
if (!defined('ABSPATH')) {
    exit;
}

class TFSP_Enhanced_Admin_Dashboard {
    
    public function __construct() {
        add_action('wp_ajax_update_challenge', array($this, 'update_challenge'));
        add_action('wp_ajax_create_challenge', array($this, 'create_challenge'));
        add_action('wp_ajax_update_document_status', array($this, 'update_document_status'));
    }
    
    public function render_dashboard() {
        ?>
        <style>
        /* Mobile-First Responsive Admin Dashboard */
        .admin-container { padding: 10px; max-width: 100%; overflow-x: hidden; }
        .admin-nav { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 8px; }
        .admin-nav a { flex: 1; min-width: 120px; padding: 8px 12px; text-align: center; background: white; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .admin-nav a.active, .admin-nav a:hover { background: #8BC34A; color: white; border-color: #8BC34A; }
        .section { background: white; border-radius: 8px; padding: 15px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section h2 { font-size: 18px; margin: 0 0 10px; color: #333; }
        .section p { font-size: 14px; color: #666; margin: 0 0 15px; }
        .stats-grid { display: grid; grid-template-columns: 1fr; gap: 15px; margin-bottom: 20px; }
        .stat-card { display: flex; align-items: center; padding: 15px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; border-left: 4px solid #8BC34A; }
        .stat-icon { font-size: 24px; margin-right: 15px; min-width: 40px; text-align: center; }
        .stat-content { flex: 1; }
        .stat-number { font-size: 24px; font-weight: bold; color: #333; line-height: 1; }
        .stat-label { font-size: 12px; color: #666; margin-top: 2px; }
        .hidden { display: none; }
        .dashboard-section { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Tablet Styles */
        @media (min-width: 768px) {
            .admin-container { padding: 20px; }
            .admin-nav { gap: 10px; padding: 15px; }
            .admin-nav a { min-width: 140px; padding: 10px 16px; font-size: 14px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 20px; }
            .section { padding: 20px; }
            .section h2 { font-size: 20px; }
        }
        
        /* Desktop Styles */
        @media (min-width: 1024px) {
            .admin-container { padding: 30px; max-width: 1200px; margin: 0 auto; }
            .admin-nav { flex-wrap: nowrap; gap: 15px; padding: 20px; }
            .admin-nav a { flex: none; min-width: 160px; padding: 12px 20px; font-size: 15px; }
            .stats-grid { grid-template-columns: repeat(4, 1fr); }
            .section { padding: 25px; }
            .section h2 { font-size: 22px; }
        }
        </style>
        
        <div class="admin-container">
            <div class="tfsp-admin-dashboard">
                <h1>🎓 Turner Foundation Student Portal</h1>
                
                <!-- Mobile Navigation -->
                <div class="admin-nav">
                    <a href="#overview" class="nav-link active" onclick="showSection('overview')">📊 Overview</a>
                    <a href="#students" class="nav-link" onclick="showSection('students')">👥 Students</a>
                    <a href="#attendance" class="nav-link" onclick="showSection('attendance')">📅 Attendance</a>
                    <a href="#challenges" class="nav-link" onclick="showSection('challenges')">🎯 Challenges</a>
                    <a href="#documents" class="nav-link" onclick="showSection('documents')">📄 Documents</a>
                    <a href="#messages" class="nav-link" onclick="showSection('messages')">💬 Messages</a>
                </div>
                
                <!-- Overview Section -->
                <div id="overview-section" class="dashboard-section">
                    <?php $this->render_overview_cards(); ?>
                </div>
                
                <!-- Students Section -->
                <div id="students-section" class="dashboard-section hidden">
                    <?php $this->render_student_progress(); ?>
                </div>
                
                <!-- Attendance Section -->
                <div id="attendance-section" class="dashboard-section hidden">
                    <?php $this->render_attendance_section(); ?>
                </div>
                
                <!-- Challenges Section -->
                <div id="challenges-section" class="dashboard-section hidden">
                    <?php $this->render_challenge_management(); ?>
                </div>
                
                <!-- Documents Section -->
                <div id="documents-section" class="dashboard-section hidden">
                    <?php $this->render_document_management(); ?>
                </div>
                
                <!-- Messages Section -->
                <div id="messages-section" class="dashboard-section hidden">
                    <?php $this->render_messaging_center(); ?>
                </div>
            </div>
        </div>
        
        <script>
        function showSection(sectionName) {
            document.querySelectorAll('.dashboard-section').forEach(section => {
                section.classList.add('hidden');
            });
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            document.getElementById(sectionName + '-section').classList.remove('hidden');
            event.target.classList.add('active');
            window.location.hash = sectionName;
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash.substring(1);
            if (hash && document.getElementById(hash + '-section')) {
                const navLink = document.querySelector(`[onclick="showSection('${hash}')"]`);
                if (navLink) navLink.click();
            }
        });
        </script>
        
        <style>
        .tfsp-admin-dashboard {
            max-width: 100%;
            margin: 0;
            padding: 0;
        }
        
        .dashboard-section {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .dashboard-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .dashboard-card h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .dashboard-card .number {
            font-size: 2em;
            font-weight: bold;
            color: #8BC34A;
        }
        .section {
            background: white;
            margin: 20px 0;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .student-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .student-card {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .student-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .student-name {
            font-weight: 600;
            margin-bottom: 10px;
        }
        .progress-bar {
            background: #f0f0f0;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            background: #8BC34A;
            height: 100%;
            transition: width 0.3s;
        }
        .challenge-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
        }
        .challenge-controls {
            margin-top: 10px;
        }
        .challenge-controls button {
            margin-right: 10px;
        }
        </style>
        <?php
    }
    
    private function render_overview_cards() {
        $students = get_users(array('role' => 'subscriber'));
        $total_students = count($students);
        
        // Calculate completion rates
        global $wpdb;
        $completed_items = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_student_progress WHERE status = 'completed'");
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_student_progress");
        $completion_rate = $total_items > 0 ? round(($completed_items / $total_items) * 100) : 0;
        
        ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $total_students; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $completion_rate; ?>%</div>
                    <div class="stat-label">Completion Rate</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $this->get_active_challenges_count(); ?></div>
                    <div class="stat-label">Active Challenges</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📄</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $this->get_pending_documents_count(); ?></div>
                    <div class="stat-label">Pending Documents</div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_attendance_section() {
        // Embed the new Attendance Grid (REST-powered)
        echo '<div class="section">';
        // Localize config for the grid JS
        $cfg = array(
            'nonce' => wp_create_nonce('wp_rest'),
            'apiUrl' => rest_url('tfsp/v1/'),
            'currentWeek' => date('Y-m-d', strtotime('monday this week')),
        );
        echo '<script>window.tfspAttendance = ' . json_encode($cfg) . ';</script>';
        // Basic styles (enqueue equivalent)
        echo '<link rel="stylesheet" href="' . esc_url(TFSP_PLUGIN_URL . 'admin/css/attendance-admin.css') . '" />';
        include TFSP_PLUGIN_PATH . 'admin/views/page-attendance-grid.php';
        // Grid behavior
        echo '<script src="' . esc_url(TFSP_PLUGIN_URL . 'admin/js/attendance-grid.js') . '"></script>';
        echo '</div>';
    }
    
    private function render_student_progress() {
        $students = get_users(array('role' => 'subscriber'));
        ?>
        <div class="section">
            <h2>📊 Student Progress Overview</h2>
            <p>Click on any student to drill down into their detailed progress</p>
            
            <div class="student-grid">
                <?php foreach ($students as $student): ?>
                    <div class="student-card" onclick="showStudentDetails(<?php echo $student->ID; ?>)">
                        <div class="student-name"><?php echo $student->display_name; ?></div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $this->get_student_progress($student->ID); ?>%"></div>
                        </div>
                        <div class="progress-text"><?php echo $this->get_student_progress($student->ID); ?>% Complete</div>
                        <div class="student-stats">
                            <small>Last Activity: <?php echo $this->get_last_activity($student->ID); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <script>
        function showStudentDetails(studentId) {
            // Open detailed student view
            window.open('?page=tfsp-student-detail&student_id=' + studentId, '_blank');
        }
        </script>
        <?php
    }
    
    private function render_challenge_management() {
        $challenges = $this->get_all_challenges();
        ?>
        <div class="section">
            <h2>🎯 Challenge Management</h2>
            <p>Create and manage challenges based on student year level</p>
            
            <div class="challenge-controls">
                <button class="button button-primary" onclick="createNewChallenge()">Create New Challenge</button>
                <select id="year-filter">
                    <option value="">All Years</option>
                    <option value="sophomore">Sophomore</option>
                    <option value="junior">Junior</option>
                    <option value="senior">Senior</option>
                </select>
            </div>
            
            <div id="challenges-list">
                <?php foreach ($challenges as $challenge): ?>
                    <div class="challenge-item" data-year="<?php echo $challenge->target_year; ?>">
                        <h4><?php echo esc_html($challenge->title); ?></h4>
                        <p><?php echo esc_html($challenge->description); ?></p>
                        <div class="challenge-meta">
                            <span class="year-badge">Target: <?php echo ucfirst($challenge->target_year); ?></span>
                            <span class="difficulty">Difficulty: <?php echo $challenge->difficulty; ?>/5</span>
                        </div>
                        <div class="challenge-controls">
                            <button class="button" onclick="editChallenge(<?php echo $challenge->id; ?>)">Edit</button>
                            <button class="button" onclick="deleteChallenge(<?php echo $challenge->id; ?>)">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <script>
        function createNewChallenge() {
            var title = prompt('Challenge Title:');
            var description = prompt('Challenge Description:');
            var year = prompt('Target Year (sophomore/junior/senior):');
            var difficulty = prompt('Difficulty (1-5):');
            
            if (title && description && year && difficulty) {
                jQuery.post(ajaxurl, {
                    action: 'create_challenge',
                    title: title,
                    description: description,
                    target_year: year,
                    difficulty: difficulty,
                    nonce: '<?php echo wp_create_nonce('challenge_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                });
            }
        }
        </script>
        <?php
    }
    
    private function render_document_management() {
        ?>
        <div class="section">
            <h2>📄 Document Status Management</h2>
            <p>Updated status options: Submitted, Accepted, Sent back for development</p>
            
            <div class="document-status-grid">
                <?php $this->render_document_status_table(); ?>
            </div>
        </div>
        <?php
    }
    
    private function render_messaging_center() {
        ?>
        <div class="section">
            <h2>💬 Messaging Center</h2>
            <div class="messaging-tabs">
                <button class="tab-button active" onclick="showMessages('coach')">Coach Messages</button>
                <button class="tab-button" onclick="showMessages('admin')">Admin Messages</button>
            </div>
            
            <div id="coach-messages" class="message-panel active">
                <?php $this->render_messages('coach'); ?>
            </div>
            
            <div id="admin-messages" class="message-panel">
                <?php $this->render_messages('admin'); ?>
            </div>
        </div>
        
        <script>
        function showMessages(type) {
            document.querySelectorAll('.message-panel').forEach(panel => panel.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            
            document.getElementById(type + '-messages').classList.add('active');
            event.target.classList.add('active');
        }
        </script>
        <?php
    }
    
    // Helper methods
    private function get_student_progress($student_id) {
        global $wpdb;
        $total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d", $student_id));
        $completed = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d AND status = 'completed'", $student_id));
        return $total > 0 ? round(($completed / $total) * 100) : 0;
    }
    
    private function get_last_activity($student_id) {
        global $wpdb;
        $last_activity = $wpdb->get_var($wpdb->prepare("SELECT MAX(updated_at) FROM {$wpdb->prefix}tfsp_student_progress WHERE student_id = %d", $student_id));
        return $last_activity ? date('M j, Y', strtotime($last_activity)) : 'No activity';
    }
    
    private function get_active_challenges_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_challenges WHERE status = 'active'");
    }
    
    private function get_pending_documents_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_documents WHERE status = 'submitted'");
    }
    
    private function get_all_challenges() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tfsp_challenges ORDER BY target_year, difficulty");
    }
    
    private function render_document_status_table() {
        global $wpdb;
        $documents = $wpdb->get_results("
            SELECT d.*, u.display_name 
            FROM {$wpdb->prefix}tfsp_documents d 
            JOIN {$wpdb->users} u ON d.student_id = u.ID 
            ORDER BY d.uploaded_at DESC
        ");
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Document</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $doc): ?>
                    <tr>
                        <td><?php echo $doc->display_name; ?></td>
                        <td><?php echo $doc->file_name; ?></td>
                        <td><?php echo $doc->document_type; ?></td>
                        <td>
                            <select onchange="updateDocumentStatus(<?php echo $doc->id; ?>, this.value)">
                                <option value="submitted" <?php selected($doc->status, 'submitted'); ?>>Submitted</option>
                                <option value="accepted" <?php selected($doc->status, 'accepted'); ?>>Accepted</option>
                                <option value="needs_revision" <?php selected($doc->status, 'needs_revision'); ?>>Sent back for development</option>
                            </select>
                        </td>
                        <td>
                            <a href="<?php echo $doc->file_url; ?>" target="_blank" class="button">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <script>
        function updateDocumentStatus(docId, status) {
            jQuery.post(ajaxurl, {
                action: 'update_document_status',
                doc_id: docId,
                status: status,
                nonce: '<?php echo wp_create_nonce('doc_status_nonce'); ?>'
            });
        }
        </script>
        <?php
    }
    
    private function render_messages($type) {
        global $wpdb;
        $messages = $wpdb->get_results($wpdb->prepare("
            SELECT m.*, u.display_name 
            FROM {$wpdb->prefix}tfsp_messages m 
            JOIN {$wpdb->users} u ON m.student_id = u.ID 
            WHERE m.message_type = %s 
            ORDER BY m.created_at DESC
        ", $type));
        
        ?>
        <div class="messages-list">
            <?php foreach ($messages as $message): ?>
                <div class="message-item">
                    <div class="message-header">
                        <strong><?php echo $message->display_name; ?></strong>
                        <span class="message-date"><?php echo date('M j, Y g:i A', strtotime($message->created_at)); ?></span>
                    </div>
                    <div class="message-subject"><strong><?php echo esc_html($message->subject); ?></strong></div>
                    <div class="message-content"><?php echo nl2br(esc_html($message->message)); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    // AJAX handlers
    public function create_challenge() {
        if (!wp_verify_nonce($_POST['nonce'], 'challenge_nonce')) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        $result = $wpdb->insert($wpdb->prefix . 'tfsp_challenges', array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'target_year' => sanitize_text_field($_POST['target_year']),
            'difficulty' => intval($_POST['difficulty']),
            'status' => 'active'
        ));
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
    
    public function update_document_status() {
        if (!wp_verify_nonce($_POST['nonce'], 'doc_status_nonce')) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'tfsp_documents',
            array('status' => sanitize_text_field($_POST['status'])),
            array('id' => intval($_POST['doc_id']))
        );
        
        wp_send_json_success();
    }
}

// Don't instantiate here - it's done in the main plugin file
?>
