<?php
if (!defined('ABSPATH')) {
    exit;
}

class TFSP_Student_360 {
    
    public function __construct() {
        add_action('wp_ajax_tfsp_student_360_data', array($this, 'get_student_data'));
    }
    
    public function get_student_data() {
        if (!current_user_can('view_student_360')) {
            wp_send_json_error(__('Insufficient permissions', 'tfsp'));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'student_360_nonce')) {
            wp_send_json_error(__('Security check failed', 'tfsp'));
        }
        
        $student_id = intval($_POST['student_id']);
        $tab = sanitize_text_field($_POST['tab']);
        
        switch ($tab) {
            case 'attendance':
                wp_send_json_success($this->get_attendance_data($student_id));
                break;
            case 'documents':
                wp_send_json_success($this->get_documents_data($student_id));
                break;
            case 'challenges':
                wp_send_json_success($this->get_challenges_data($student_id));
                break;
            default:
                wp_send_json_error(__('Invalid tab', 'tfsp'));
        }
    }
    
    private function get_attendance_data($student_id) {
        global $wpdb;
        
        $from = date('Y-m-d', strtotime('-30 days'));
        $to = date('Y-m-d');
        
        // Get attendance records
        $records = $wpdb->get_results($wpdb->prepare(
            "SELECT s.session_date, s.subject, s.start_time, ar.status, ar.check_in_at, ar.notes
             FROM {$wpdb->prefix}tfsp_attendance_records ar
             JOIN {$wpdb->prefix}tfsp_sessions s ON s.id = ar.session_id
             WHERE ar.student_id = %d AND s.session_date BETWEEN %s AND %s
             ORDER BY s.session_date DESC
             LIMIT 20",
            $student_id, $from, $to
        ));
        
        // Calculate stats
        $total_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_sessions
             WHERE session_date BETWEEN %s AND %s AND is_postponed = 0",
            $from, $to
        ));
        
        $present_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_attendance_records ar
             JOIN {$wpdb->prefix}tfsp_sessions s ON s.id = ar.session_id
             WHERE ar.student_id = %d AND s.session_date BETWEEN %s AND %s
             AND ar.status IN ('present', 'late', 'remote') AND s.is_postponed = 0",
            $student_id, $from, $to
        ));
        
        $absent_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_attendance_records ar
             JOIN {$wpdb->prefix}tfsp_sessions s ON s.id = ar.session_id
             WHERE ar.student_id = %d AND s.session_date BETWEEN %s AND %s
             AND ar.status = 'did_not_attend' AND s.is_postponed = 0",
            $student_id, $from, $to
        ));
        
        $percentage = $total_sessions > 0 ? round(($present_count / $total_sessions) * 100) : 0;
        
        return array(
            'stats' => array(
                'percentage' => $percentage,
                'present' => $present_count,
                'absent' => $absent_count,
                'total' => $total_sessions
            ),
            'records' => $records
        );
    }
    
    private function get_documents_data($student_id) {
        global $wpdb;
        
        $documents = $wpdb->get_results($wpdb->prepare(
            "SELECT id, doc_type, status, notes, updated_at
             FROM {$wpdb->prefix}tfsp_student_documents
             WHERE student_id = %d
             ORDER BY updated_at DESC",
            $student_id
        ));
        
        return array('documents' => $documents);
    }
    
    private function get_challenges_data($student_id) {
        global $wpdb;
        
        // Get student's program year (assuming it's stored in user meta)
        $program_year = get_user_meta($student_id, 'program_year', true) ?: 12;
        
        $challenges = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id, c.title, c.difficulty, ri.name as roadmap_name,
                    sc.status, sc.progress_percent, sc.due_date, sc.coach_notes
             FROM {$wpdb->prefix}tfsp_challenges c
             JOIN {$wpdb->prefix}tfsp_roadmap_items ri ON ri.id = c.roadmap_item_id
             LEFT JOIN {$wpdb->prefix}tfsp_student_challenges sc ON sc.challenge_id = c.id AND sc.student_id = %d
             WHERE c.program_year = %d AND c.is_active = 1
             ORDER BY ri.name, c.difficulty",
            $student_id, $program_year
        ));
        
        return array(
            'challenges' => $challenges,
            'program_year' => $program_year
        );
    }
    
    public static function render_student_profile_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your profile.', 'tfsp') . '</p>';
        }
        
        $student_id = get_current_user_id();
        $instance = new self();
        
        ob_start();
        ?>
        <div class="tfsp-student-profile">
            <div class="profile-tabs">
                <button class="tab-btn active" data-tab="attendance"><?php _e('Attendance', 'tfsp'); ?></button>
                <button class="tab-btn" data-tab="documents"><?php _e('Documents', 'tfsp'); ?></button>
                <button class="tab-btn" data-tab="challenges"><?php _e('Challenges', 'tfsp'); ?></button>
            </div>
            
            <div class="tab-content">
                <div id="profile-attendance" class="tab-pane active">
                    <?php echo $instance->render_attendance_profile($student_id); ?>
                </div>
                
                <div id="profile-documents" class="tab-pane">
                    <?php echo $instance->render_documents_profile($student_id); ?>
                </div>
                
                <div id="profile-challenges" class="tab-pane">
                    <?php echo $instance->render_challenges_profile($student_id); ?>
                </div>
            </div>
        </div>
        
        <style>
        .tfsp-student-profile { background: #fff; padding: 20px; border-radius: 8px; }
        .profile-tabs { display: flex; border-bottom: 1px solid #ddd; margin-bottom: 20px; }
        .tab-btn { background: none; border: none; padding: 10px 20px; cursor: pointer; }
        .tab-btn.active { border-bottom: 2px solid #007cba; color: #007cba; }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: #f8f9fa; padding: 15px; border-radius: 6px; text-align: center; }
        .stat-number { font-size: 24px; font-weight: bold; color: #007cba; }
        .stat-label { font-size: 12px; color: #666; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.tab-btn').on('click', function() {
                let tab = $(this).data('tab');
                $('.tab-btn').removeClass('active');
                $(this).addClass('active');
                $('.tab-pane').removeClass('active');
                $('#profile-' + tab).addClass('active');
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    private function render_attendance_profile($student_id) {
        $data = $this->get_attendance_data($student_id);
        
        ob_start();
        ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $data['stats']['percentage']; ?>%</div>
                <div class="stat-label"><?php _e('Attendance Rate', 'tfsp'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $data['stats']['present']; ?></div>
                <div class="stat-label"><?php _e('Present', 'tfsp'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $data['stats']['absent']; ?></div>
                <div class="stat-label"><?php _e('Absent', 'tfsp'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $data['stats']['total']; ?></div>
                <div class="stat-label"><?php _e('Total Sessions', 'tfsp'); ?></div>
            </div>
        </div>
        
        <h4><?php _e('Recent Sessions', 'tfsp'); ?></h4>
        <div class="recent-sessions">
            <?php foreach ($data['records'] as $record): ?>
                <div class="session-record">
                    <span><?php echo date('M j, Y', strtotime($record->session_date)); ?></span>
                    <span><?php echo $record->subject; ?></span>
                    <span class="status-badge <?php echo $record->status; ?>"><?php echo ucfirst(str_replace('_', ' ', $record->status)); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_documents_profile($student_id) {
        $data = $this->get_documents_data($student_id);
        
        ob_start();
        ?>
        <div class="documents-profile">
            <?php foreach ($data['documents'] as $doc): ?>
                <div class="document-card">
                    <h5><?php echo esc_html($doc->doc_type); ?></h5>
                    <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $doc->status)); ?>">
                        <?php echo $doc->status; ?>
                    </span>
                    <?php if ($doc->notes): ?>
                        <p class="doc-notes"><?php echo esc_html($doc->notes); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_challenges_profile($student_id) {
        $data = $this->get_challenges_data($student_id);
        
        ob_start();
        ?>
        <div class="challenges-profile">
            <p><?php printf(__('Program Year: %d', 'tfsp'), $data['program_year']); ?></p>
            
            <?php foreach ($data['challenges'] as $challenge): ?>
                <div class="challenge-card">
                    <h5><?php echo esc_html($challenge->title); ?></h5>
                    <p class="roadmap-name"><?php echo esc_html($challenge->roadmap_name); ?></p>
                    <div class="challenge-meta">
                        <span class="difficulty <?php echo $challenge->difficulty; ?>"><?php echo ucfirst($challenge->difficulty); ?></span>
                        <span class="status"><?php echo ucfirst(str_replace('_', ' ', $challenge->status ?: 'not_started')); ?></span>
                    </div>
                    <?php if ($challenge->progress_percent): ?>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $challenge->progress_percent; ?>%"></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
