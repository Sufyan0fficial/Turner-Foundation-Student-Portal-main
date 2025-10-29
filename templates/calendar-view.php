<?php
if (!defined('ABSPATH')) {
    exit;
}

class TFSP_Calendar_View {
    
    public function render_30_day_calendar() {
        $calendly_url = get_option('tfsp_calendly_url', 'https://calendly.com/abraizbashir/30min');
        ?>
        <div class="calendar-section">
            <h3>📅 Schedule Your Success Session</h3>
            <p>Connect with your dedicated <strong>College and Career Coach</strong> for personalized guidance</p>
            
            <div class="calendar-container">
                <div class="calendar-header">
                    <button id="prev-month" class="calendar-nav">&lt;</button>
                    <h4 id="current-month"></h4>
                    <button id="next-month" class="calendar-nav">&gt;</button>
                </div>
                
                <div class="calendar-grid">
                    <div class="calendar-days">
                        <div class="day-header">Sun</div>
                        <div class="day-header">Mon</div>
                        <div class="day-header">Tue</div>
                        <div class="day-header">Wed</div>
                        <div class="day-header">Thu</div>
                        <div class="day-header">Fri</div>
                        <div class="day-header">Sat</div>
                    </div>
                    <div id="calendar-dates" class="calendar-dates"></div>
                </div>
                
                <div class="calendar-legend">
                    <div class="legend-item">
                        <span class="legend-color available"></span>
                        <span>Available Slots</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color booked"></span>
                        <span>Booked</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color unavailable"></span>
                        <span>Unavailable</span>
                    </div>
                </div>
                
                <div class="calendar-actions">
                    <a href="<?php echo esc_url($calendly_url); ?>" 
                       target="_blank" 
                       class="schedule-button">
                        📅 Open Full Scheduler
                    </a>
                </div>
            </div>
        </div>
        
        <style>
        .calendar-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        
        .calendar-container {
            max-width: 600px;
            margin: 20px auto;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 0 10px;
        }
        
        .calendar-nav {
            background: #8BC34A;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .calendar-nav:hover {
            background: #7CB342;
        }
        
        #current-month {
            margin: 0;
            font-size: 1.2em;
            color: #333;
        }
        
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            margin-bottom: 5px;
        }
        
        .day-header {
            background: #f5f5f5;
            padding: 10px;
            text-align: center;
            font-weight: 600;
            color: #666;
            border-radius: 4px;
        }
        
        .calendar-dates {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .calendar-date {
            background: white;
            padding: 15px 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            min-height: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .calendar-date:hover {
            background: #f0f8ff;
        }
        
        .calendar-date.other-month {
            color: #ccc;
            background: #f9f9f9;
        }
        
        .calendar-date.today {
            background: #e3f2fd;
            font-weight: bold;
        }
        
        .calendar-date.available {
            background: #e8f5e8;
            border-left: 4px solid #4CAF50;
        }
        
        .calendar-date.booked {
            background: #ffebee;
            border-left: 4px solid #f44336;
        }
        
        .calendar-date.unavailable {
            background: #f5f5f5;
            color: #999;
        }
        
        .date-number {
            font-size: 16px;
            margin-bottom: 2px;
        }
        
        .date-slots {
            font-size: 10px;
            color: #666;
        }
        
        .calendar-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
        }
        
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
        }
        
        .legend-color.available {
            background: #4CAF50;
        }
        
        .legend-color.booked {
            background: #f44336;
        }
        
        .legend-color.unavailable {
            background: #999;
        }
        
        .calendar-actions {
            text-align: center;
            margin-top: 20px;
        }
        
        .schedule-button {
            background: #8BC34A;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .schedule-button:hover {
            background: #7CB342;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        @media (max-width: 600px) {
            .calendar-container {
                margin: 10px 0;
            }
            
            .calendar-date {
                padding: 8px 2px;
                min-height: 40px;
                font-size: 12px;
            }
            
            .date-number {
                font-size: 14px;
            }
            
            .date-slots {
                font-size: 9px;
            }
        }
        </style>
        
        <script>
        class CalendarView {
            constructor() {
                this.currentDate = new Date();
                this.today = new Date();
                this.init();
            }
            
            init() {
                this.renderCalendar();
                this.bindEvents();
                this.loadAvailability();
            }
            
            bindEvents() {
                document.getElementById('prev-month').addEventListener('click', () => {
                    this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                    this.renderCalendar();
                    this.loadAvailability();
                });
                
                document.getElementById('next-month').addEventListener('click', () => {
                    this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                    this.renderCalendar();
                    this.loadAvailability();
                });
            }
            
            renderCalendar() {
                const monthNames = [
                    'January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'
                ];
                
                document.getElementById('current-month').textContent = 
                    monthNames[this.currentDate.getMonth()] + ' ' + this.currentDate.getFullYear();
                
                const firstDay = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), 1);
                const lastDay = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 0);
                const startDate = new Date(firstDay);
                startDate.setDate(startDate.getDate() - firstDay.getDay());
                
                const datesContainer = document.getElementById('calendar-dates');
                datesContainer.innerHTML = '';
                
                for (let i = 0; i < 42; i++) {
                    const date = new Date(startDate);
                    date.setDate(startDate.getDate() + i);
                    
                    const dateElement = document.createElement('div');
                    dateElement.className = 'calendar-date';
                    dateElement.dataset.date = date.toISOString().split('T')[0];
                    
                    if (date.getMonth() !== this.currentDate.getMonth()) {
                        dateElement.classList.add('other-month');
                    }
                    
                    if (date.toDateString() === this.today.toDateString()) {
                        dateElement.classList.add('today');
                    }
                    
                    dateElement.innerHTML = `
                        <div class="date-number">${date.getDate()}</div>
                        <div class="date-slots"></div>
                    `;
                    
                    dateElement.addEventListener('click', () => {
                        if (!dateElement.classList.contains('other-month') && 
                            !dateElement.classList.contains('unavailable')) {
                            window.open('<?php echo esc_js($calendly_url); ?>', '_blank');
                        }
                    });
                    
                    datesContainer.appendChild(dateElement);
                }
            }
            
            loadAvailability() {
                // Simulate availability data - in real implementation, this would come from Calendly API
                const dates = document.querySelectorAll('.calendar-date:not(.other-month)');
                dates.forEach(dateEl => {
                    const date = new Date(dateEl.dataset.date);
                    const dayOfWeek = date.getDay();
                    const isPast = date < this.today;
                    
                    if (isPast) {
                        dateEl.classList.add('unavailable');
                        dateEl.querySelector('.date-slots').textContent = 'Past';
                    } else if (dayOfWeek === 0 || dayOfWeek === 6) {
                        dateEl.classList.add('unavailable');
                        dateEl.querySelector('.date-slots').textContent = 'Weekend';
                    } else {
                        // Simulate random availability
                        const slots = Math.floor(Math.random() * 4);
                        if (slots === 0) {
                            dateEl.classList.add('booked');
                            dateEl.querySelector('.date-slots').textContent = 'Booked';
                        } else {
                            dateEl.classList.add('available');
                            dateEl.querySelector('.date-slots').textContent = `${slots} slots`;
                        }
                    }
                });
            }
        }
        
        // Initialize calendar when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            new CalendarView();
        });
        </script>
        <?php
    }
}
?>
