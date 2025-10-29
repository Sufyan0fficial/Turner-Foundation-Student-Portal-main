<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$user_id = get_current_user_id();

// Get coaching session stats
$total_sessions = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_coach_sessions WHERE student_id = %d",
    $user_id
));

$attended = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_coach_sessions WHERE student_id = %d AND status = 'attended'",
    $user_id
));

$scheduled = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_coach_sessions WHERE student_id = %d AND status = 'scheduled'",
    $user_id
));

$completion_rate = $total_sessions > 0 ? round(($attended / 3) * 100) : 0; // Out of 3 required

// Get next upcoming session
$next_session = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tfsp_coach_sessions 
     WHERE student_id = %d AND session_date >= CURDATE() 
     ORDER BY session_date ASC, session_time ASC 
     LIMIT 1",
    $user_id
));

// Get advisor settings for the schedule button
$advisor_settings = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}tfsp_advisor_settings", OBJECT_K);
?>

<div class="coaching-module-consolidated">
    <div class="coaching-header">
        <h2>🎯 One-on-One Coaching Sessions</h2>
        <p>Required individual sessions with your College & Career Coach</p>
    </div>
    
    <div class="coaching-stats">
        <div class="stat-box required">
            <span class="stat-number">3</span>
            <span class="stat-label">Required Sessions</span>
        </div>
        <div class="stat-box scheduled">
            <span class="stat-number"><?php echo $scheduled; ?> out of 3</span>
            <span class="stat-label">Scheduled</span>
        </div>
        <div class="stat-box attended">
            <span class="stat-number"><?php echo $attended; ?> out of 3</span>
            <span class="stat-label">Attended</span>
        </div>
        <div class="stat-box completion">
            <span class="stat-number"><?php echo $completion_rate; ?>%</span>
            <span class="stat-label">Completion Rate</span>
        </div>
    </div>
    
    <?php if ($next_session): ?>
    <div class="next-session">
        <div class="session-info">
            <h4>📅 Next Session</h4>
            <div class="session-details">
                <span class="session-date"><?php echo date('l, F j, Y', strtotime($next_session->session_date)); ?></span>
                <span class="session-time"><?php echo date('g:i A', strtotime($next_session->session_time)); ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="coaching-actions">
        <button onclick="toggleCalendar()" class="schedule-btn">
            📅 Schedule Your Session
        </button>
        <?php if ($attended < 3): ?>
        <div class="progress-indicator">
            <span>Progress: <?php echo $attended; ?>/3 sessions completed</span>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo ($attended / 3) * 100; ?>%"></div>
            </div>
        </div>
        <?php else: ?>
        <div class="completion-badge">
            ✅ All required sessions completed!
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.coaching-module-consolidated {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 25px;
    border-radius: 16px;
    margin-bottom: 30px;
}

.coaching-header h2 {
    color: white !important;
    font-size: 22px !important;
    margin: 0 0 6px 0 !important;
    font-weight: 700;
}

.coaching-header p {
    color: rgba(255,255,255,0.9) !important;
    margin: 0 0 20px 0 !important;
    font-size: 14px;
}

.coaching-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-box {
    background: rgba(255,255,255,0.15);
    padding: 15px;
    border-radius: 10px;
    text-align: center;
    backdrop-filter: blur(10px);
}

.stat-number {
    display: block;
    font-size: 20px;
    font-weight: 700;
    color: white;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 11px;
    color: rgba(255,255,255,0.8);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.next-session {
    background: rgba(255,255,255,0.1);
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.next-session h4 {
    color: white !important;
    margin: 0 0 8px 0 !important;
    font-size: 14px;
}

.session-details {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.session-date {
    font-weight: 600;
    font-size: 14px;
}

.session-time {
    font-size: 13px;
    opacity: 0.9;
}

.coaching-actions {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.schedule-btn {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 2px solid rgba(255,255,255,0.3);
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
}

.schedule-btn:hover {
    background: rgba(255,255,255,0.3);
    border-color: rgba(255,255,255,0.5);
    transform: translateY(-1px);
}

.progress-indicator {
    text-align: center;
}

.progress-indicator span {
    font-size: 12px;
    color: rgba(255,255,255,0.9);
    margin-bottom: 8px;
    display: block;
}

.progress-bar {
    background: rgba(255,255,255,0.2);
    height: 6px;
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    background: rgba(255,255,255,0.8);
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s ease;
}

.completion-badge {
    background: rgba(16, 185, 129, 0.3);
    color: white;
    padding: 10px;
    border-radius: 8px;
    text-align: center;
    font-weight: 600;
    font-size: 14px;
}

@media (max-width: 768px) {
    .coaching-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stat-number {
        font-size: 18px;
    }
    
    .coaching-module-consolidated {
        padding: 20px;
    }
}
</style>
