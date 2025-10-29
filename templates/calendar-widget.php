<?php
if (!defined('ABSPATH')) {
    exit;
}

// Generate 30-day calendar view
$today = new DateTime();
$calendar_days = array();

for ($i = 0; $i < 30; $i++) {
    $date = clone $today;
    $date->add(new DateInterval("P{$i}D"));
    $calendar_days[] = $date;
}

// Mock available slots (in real implementation, integrate with Calendly API)
$available_slots = array(
    'Monday' => array('10:00 AM', '2:00 PM', '4:00 PM'),
    'Tuesday' => array('9:00 AM', '1:00 PM', '3:00 PM'),
    'Wednesday' => array('11:00 AM', '2:00 PM', '5:00 PM'),
    'Thursday' => array('10:00 AM', '1:00 PM', '4:00 PM'),
    'Friday' => array('9:00 AM', '12:00 PM', '3:00 PM')
);
?>

<style>
.calendar-widget {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    margin-bottom: 24px;
}

.calendar-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid #8BC34A;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 8px;
    margin-bottom: 20px;
}

.calendar-day {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    min-height: 60px;
}

.calendar-day:hover {
    border-color: #8BC34A;
    background: #f0f9ff;
}

.calendar-day.available {
    background: #d4edda;
    border-color: #8BC34A;
}

.calendar-day.available:hover {
    background: #8BC34A;
    color: white;
}

.calendar-day.past {
    background: #f8f9fa;
    color: #6c757d;
    cursor: not-allowed;
}

.calendar-day.today {
    background: #8BC34A;
    color: white;
    font-weight: 600;
}

.day-number {
    font-size: 16px;
    font-weight: 500;
}

.day-name {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.slots-indicator {
    position: absolute;
    bottom: 4px;
    display: flex;
    gap: 2px;
}

.slot-dot {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #8BC34A;
}

.time-slots {
    display: none;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 8px;
    margin-top: 16px;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 8px;
}

.time-slots.active {
    display: grid;
}

.time-slot {
    padding: 8px 12px;
    background: white;
    border: 1px solid #8BC34A;
    border-radius: 6px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 12px;
    font-weight: 500;
}

.time-slot:hover {
    background: #8BC34A;
    color: white;
}

.calendar-legend {
    display: flex;
    gap: 16px;
    font-size: 12px;
    margin-top: 16px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}
</style>

<div class="calendar-widget">
    <div class="calendar-header">
        <h3>📅 Schedule Your Session</h3>
        <p>Select a date to view available time slots</p>
    </div>
    
    <div class="calendar-grid">
        <?php foreach ($calendar_days as $index => $date): ?>
            <?php
            $day_name = $date->format('D');
            $day_number = $date->format('j');
            $is_today = $date->format('Y-m-d') === $today->format('Y-m-d');
            $is_past = $date < $today;
            $is_weekend = in_array($day_name, ['Sat', 'Sun']);
            $has_slots = !$is_weekend && !$is_past && isset($available_slots[$date->format('l')]);
            ?>
            
            <div class="calendar-day <?php 
                echo $is_today ? 'today' : '';
                echo $is_past ? ' past' : '';
                echo $has_slots ? ' available' : '';
            ?>" 
            data-date="<?php echo $date->format('Y-m-d'); ?>"
            <?php if ($has_slots): ?>
                onclick="showTimeSlots('<?php echo $date->format('Y-m-d'); ?>', '<?php echo $date->format('l'); ?>')"
            <?php endif; ?>>
                
                <div class="day-name"><?php echo $day_name; ?></div>
                <div class="day-number"><?php echo $day_number; ?></div>
                
                <?php if ($has_slots): ?>
                    <div class="slots-indicator">
                        <?php foreach ($available_slots[$date->format('l')] as $slot): ?>
                            <div class="slot-dot"></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div id="time-slots-container" class="time-slots">
        <!-- Time slots will be populated by JavaScript -->
    </div>
    
    <div class="calendar-legend">
        <div class="legend-item">
            <div class="legend-color" style="background: #8BC34A;"></div>
            <span>Available</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #f8f9fa;"></div>
            <span>Unavailable</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #8BC34A; border: 2px solid #689F38;"></div>
            <span>Today</span>
        </div>
    </div>
</div>

<script>
const availableSlots = <?php echo json_encode($available_slots); ?>;
const calendlyUrl = '<?php echo esc_url(get_option('tfsp_calendly_url', 'https://calendly.com/abraizbashir/30min')); ?>';

function showTimeSlots(date, dayName) {
    const container = document.getElementById('time-slots-container');
    const slots = availableSlots[dayName] || [];
    
    if (slots.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #666;">No available slots for this day</p>';
        container.classList.add('active');
        return;
    }
    
    let slotsHtml = '';
    slots.forEach(slot => {
        slotsHtml += `
            <div class="time-slot" onclick="bookSlot('${date}', '${slot}')">
                ${slot}
            </div>
        `;
    });
    
    container.innerHTML = slotsHtml;
    container.classList.add('active');
    
    // Scroll to time slots
    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function bookSlot(date, time) {
    // In real implementation, integrate with Calendly API
    const bookingUrl = `${calendlyUrl}?date=${date}&time=${encodeURIComponent(time)}`;
    window.open(bookingUrl, '_blank');
    
    // Show confirmation
    alert(`Redirecting to book your session on ${date} at ${time}`);
}

// Hide time slots when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.calendar-widget')) {
        document.getElementById('time-slots-container').classList.remove('active');
    }
});
</script>
