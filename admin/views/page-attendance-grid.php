<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap tfsp-attendance-wrap">
    <h1><?php _e('Attendance Grid', 'tfsp'); ?></h1>
    
    <div class="tfsp-attendance-controls">
        <div class="controls-row">
            <div class="week-picker">
                <label><?php _e('Week:', 'tfsp'); ?></label>
                <input type="date" id="week-start" value="<?php echo date('Y-m-d', strtotime('monday this week')); ?>">
                <button type="button" class="button" id="prev-week">‹ <?php _e('Previous', 'tfsp'); ?></button>
                <button type="button" class="button" id="current-week"><?php _e('Current Week', 'tfsp'); ?></button>
                <button type="button" class="button" id="next-week"><?php _e('Next', 'tfsp'); ?> ›</button>
            </div>
            
            <div class="actions">
                <button type="button" class="button button-primary" id="save-all"><?php _e('Save Changes', 'tfsp'); ?></button>
                <button type="button" class="button" id="export-week"><?php _e('Export Week', 'tfsp'); ?></button>
            </div>
        </div>
        
        <div class="legend">
            <span class="legend-item present">✓ <?php _e('Present', 'tfsp'); ?></span>
            <span class="legend-item excused">E <?php _e('Excused', 'tfsp'); ?></span>
            <span class="legend-item absent">✗ <?php _e('Absent', 'tfsp'); ?></span>
            <span class="legend-item late">L <?php _e('Late', 'tfsp'); ?></span>
            <span class="legend-item remote">R <?php _e('Remote', 'tfsp'); ?></span>
        </div>
    </div>
    
    <div id="attendance-grid-container">
        <div class="loading"><?php _e('Loading attendance data...', 'tfsp'); ?></div>
    </div>
    
    <!-- Student 360 Modal -->
    <div id="student-360-modal" class="tfsp-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="student-360-title"><?php _e('Student 360 View', 'tfsp'); ?></h2>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="student-360-tabs">
                    <button class="tab-button active" data-tab="attendance"><?php _e('Attendance', 'tfsp'); ?></button>
                    <button class="tab-button" data-tab="documents"><?php _e('Documents', 'tfsp'); ?></button>
                    <button class="tab-button" data-tab="challenges"><?php _e('Challenges', 'tfsp'); ?></button>
                </div>
                
                <div class="tab-content">
                    <div id="tab-attendance" class="tab-pane active">
                        <div class="attendance-stats"></div>
                        <div class="attendance-history"></div>
                    </div>
                    
                    <div id="tab-documents" class="tab-pane">
                        <div class="documents-list"></div>
                    </div>
                    
                    <div id="tab-challenges" class="tab-pane">
                        <div class="challenges-list"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
