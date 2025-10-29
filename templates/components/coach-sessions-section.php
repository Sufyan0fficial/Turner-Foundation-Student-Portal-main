<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$user_id = get_current_user_id();

// Get upcoming sessions
$upcoming_sessions = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tfsp_coach_sessions 
     WHERE student_id = %d AND session_date >= CURDATE() 
     ORDER BY session_date ASC, session_time ASC 
     LIMIT 5",
    $user_id
));

// Get past sessions
$past_sessions = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tfsp_coach_sessions 
     WHERE student_id = %d AND session_date < CURDATE() 
     ORDER BY session_date DESC 
     LIMIT 5",
    $user_id
));

// Get stats
$total_sessions = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_coach_sessions WHERE student_id = %d",
    $user_id
));

$attended = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}tfsp_coach_sessions WHERE student_id = %d AND status = 'attended'",
    $user_id
));

$attendance_rate = $total_sessions > 0 ? round(($attended / $total_sessions) * 100) : 0;
?>

<style>
.coach-sessions-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}
.session-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.stat-item {
    text-align: center;
    padding: 16px;
    background: linear-gradient(135deg, #8ebb79 0%, #3f5340 100%);
    border-radius: 8px;
    color: white;
}
.stat-number {
    font-size: 32px;
    font-weight: bold;
}
.stat-label {
    font-size: 13px;
    opacity: 0.9;
    margin-top: 4px;
}
.session-item {
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 12px;
    border-left: 4px solid #8ebb79;
}
.session-date {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}
.session-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}
.status-scheduled { background: #dbeafe; color: #1e40af; }
.status-attended { background: #d1fae5; color: #065f46; }
.status-no_show { background: #fee2e2; color: #991b1b; }
.status-postponed { background: #fef3c7; color: #92400e; }
.tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    border-bottom: 2px solid #eee;
}
.tab {
    padding: 12px 24px;
    border: none;
    background: none;
    cursor: pointer;
    font-weight: 500;
    color: #6b7280;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
}
.tab.active {
    color: #8ebb79;
    border-bottom-color: #8ebb79;
}
.tab-content {
    display: none;
}
.tab-content.active {
    display: block;
}
</style>

<div class="coach-sessions-card">
    <h2 style="margin: 0 0 8px; color: #1f2937;">👥 One-on-One Coach Sessions</h2>
    <p style="margin: 0 0 24px; color: #6b7280;">Track your individual sessions with the College & Career Coach</p>
    
    <div class="session-stats">
        <div class="stat-item">
            <div class="stat-number"><?php echo $total_sessions; ?></div>
            <div class="stat-label">Total Sessions</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $attended; ?></div>
            <div class="stat-label">Attended</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $attendance_rate; ?>%</div>
            <div class="stat-label">Attendance Rate</div>
        </div>
    </div>
    
    <div class="tabs">
        <button class="tab active" onclick="switchSessionTab('upcoming')">Upcoming Sessions</button>
        <button class="tab" onclick="switchSessionTab('past')">Past Sessions</button>
    </div>
    
    <div id="upcomingTab" class="tab-content active">
        <?php if (empty($upcoming_sessions)): ?>
            <p style="text-align: center; color: #9ca3af; padding: 40px 0;">No upcoming sessions scheduled</p>
        <?php else: ?>
            <?php foreach ($upcoming_sessions as $session): ?>
                <div class="session-item">
                    <div class="session-date">
                        📅 <?php echo date('l, F j, Y', strtotime($session->session_date)); ?>
                        <?php if ($session->session_time): ?>
                            at <?php echo date('g:i A', strtotime($session->session_time)); ?>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                        <span class="session-status status-<?php echo $session->status; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $session->status)); ?>
                        </span>
                        <?php if ($session->notes): ?>
                            <span style="font-size: 13px; color: #6b7280;">📝 Notes available</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($session->notes): ?>
                        <div style="margin-top: 12px; padding: 12px; background: white; border-radius: 6px; font-size: 14px; color: #4b5563;">
                            <?php echo nl2br(esc_html($session->notes)); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div id="pastTab" class="tab-content">
        <?php if (empty($past_sessions)): ?>
            <p style="text-align: center; color: #9ca3af; padding: 40px 0;">No past sessions</p>
        <?php else: ?>
            <?php foreach ($past_sessions as $session): ?>
                <div class="session-item">
                    <div class="session-date">
                        📅 <?php echo date('l, F j, Y', strtotime($session->session_date)); ?>
                        <?php if ($session->session_time): ?>
                            at <?php echo date('g:i A', strtotime($session->session_time)); ?>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                        <span class="session-status status-<?php echo $session->status; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $session->status)); ?>
                        </span>
                    </div>
                    <?php if ($session->notes): ?>
                        <div style="margin-top: 12px; padding: 12px; background: white; border-radius: 6px; font-size: 14px; color: #4b5563;">
                            <?php echo nl2br(esc_html($session->notes)); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function switchSessionTab(tab) {
    document.querySelectorAll('.tabs .tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    
    event.target.classList.add('active');
    document.getElementById(tab + 'Tab').classList.add('active');
}
</script>
