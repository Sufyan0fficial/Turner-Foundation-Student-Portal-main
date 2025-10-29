<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_advisor_section($user_id) {
    $calendly_url = get_option('tfsp_calendly_url', 'https://calendly.com/abraizbashir/30min');
    ?>
    
    <div class="advisor-section">
        <h3>👨‍🎓 Your College and Career Coach</h3>
        
        <div class="advisor-content">
            <div class="advisor-card">
                <div class="advisor-photo">👨‍🏫</div>
                <div class="advisor-info">
                    <h4>Your College and Career Coach</h4>
                    <div class="advisor-stats">
                        <div class="stat">
                            <span class="stat-number">95%</span>
                            <span class="stat-label">Success Rate</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">500+</span>
                            <span class="stat-label">Students Helped</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">10+</span>
                            <span class="stat-label">Years Experience</span>
                        </div>
                    </div>
                    <a href="<?php echo esc_url($calendly_url); ?>" target="_blank" class="schedule-btn">
                        📅 Schedule Meeting
                    </a>
                </div>
            </div>
            
            <div class="upcoming-sessions">
                <h4>📅 Upcoming Sessions</h4>
                <?php 
                global $wpdb;
                $upcoming = $wpdb->get_results($wpdb->prepare("
                    SELECT * FROM {$wpdb->prefix}tfsp_meetings 
                    WHERE user_id = %d AND meeting_date >= CURDATE() 
                    ORDER BY meeting_date, meeting_time LIMIT 3
                ", $user_id));
                
                if (empty($upcoming)): ?>
                    <div class="no-sessions">
                        <p>No upcoming sessions scheduled.</p>
                        <p><strong>Ready to take the next step?</strong></p>
                        <p>Schedule a personalized session with your dedicated college and career coach.</p>
                    </div>
                <?php else: ?>
                    <div class="sessions-list">
                        <?php foreach ($upcoming as $session): ?>
                            <div class="session-item">
                                <div class="session-date">
                                    <div class="date"><?php echo date('M j', strtotime($session->meeting_date)); ?></div>
                                    <div class="time"><?php echo date('g:i A', strtotime($session->meeting_time)); ?></div>
                                </div>
                                <div class="session-info">
                                    <div class="session-type"><?php echo esc_html($session->meeting_type); ?></div>
                                    <div class="session-status"><?php echo esc_html($session->status); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <style>
    .advisor-section {
        background: white;
        padding: 30px;
        border-radius: 16px;
        margin: 30px 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .advisor-section h3 {
        margin: 0 0 25px 0;
        color: #2d5016;
        font-size: 24px;
        font-weight: 700;
    }
    .advisor-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
    .advisor-card {
        background: linear-gradient(135deg, #8BC34A 0%, #7CB342 100%);
        color: white;
        padding: 25px;
        border-radius: 12px;
        text-align: center;
    }
    .advisor-photo {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        margin: 0 auto 20px;
    }
    .advisor-info h4 {
        margin: 0 0 20px 0;
        font-size: 20px;
        font-weight: 600;
    }
    .advisor-stats {
        display: flex;
        justify-content: space-around;
        margin-bottom: 25px;
    }
    .stat {
        text-align: center;
    }
    .stat-number {
        display: block;
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 4px;
    }
    .stat-label {
        font-size: 12px;
        opacity: 0.9;
    }
    .schedule-btn {
        background: white;
        color: #8BC34A;
        padding: 12px 24px;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 600;
        display: inline-block;
        transition: all 0.3s;
    }
    .schedule-btn:hover {
        background: #f0f0f0;
        transform: translateY(-2px);
    }
    .upcoming-sessions h4 {
        margin: 0 0 20px 0;
        color: #333;
        font-size: 18px;
        font-weight: 600;
    }
    .no-sessions {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 8px;
        text-align: center;
        color: #666;
    }
    .no-sessions p {
        margin: 0 0 10px 0;
    }
    .sessions-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .session-item {
        display: flex;
        align-items: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #8BC34A;
    }
    .session-date {
        margin-right: 20px;
        text-align: center;
        min-width: 60px;
    }
    .date {
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }
    .time {
        font-size: 12px;
        color: #666;
    }
    .session-info {
        flex: 1;
    }
    .session-type {
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
    }
    .session-status {
        font-size: 12px;
        color: #666;
        text-transform: capitalize;
    }
    
    @media (max-width: 768px) {
        .advisor-content {
            grid-template-columns: 1fr;
        }
        .advisor-stats {
            flex-direction: column;
            gap: 15px;
        }
    }
    </style>
    <?php
}
?>
