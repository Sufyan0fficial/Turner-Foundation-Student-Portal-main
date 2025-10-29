jQuery(document).ready(function($) {
    let currentWeek = tfspAttendance.currentWeek;
    let gridData = {};
    let pendingChanges = {};
    
    // Initialize
    loadGridData();
    
    // Week navigation
    $('#prev-week').on('click', function() {
        currentWeek = moment(currentWeek).subtract(1, 'week').format('YYYY-MM-DD');
        $('#week-start').val(currentWeek);
        loadGridData();
    });
    
    $('#next-week').on('click', function() {
        currentWeek = moment(currentWeek).add(1, 'week').format('YYYY-MM-DD');
        $('#week-start').val(currentWeek);
        loadGridData();
    });
    
    $('#current-week').on('click', function() {
        currentWeek = tfspAttendance.currentWeek;
        $('#week-start').val(currentWeek);
        loadGridData();
    });
    
    $('#week-start').on('change', function() {
        currentWeek = $(this).val();
        loadGridData();
    });
    
    // Save changes
    $('#save-all').on('click', function() {
        saveChanges();
    });
    
    // Load grid data
    function loadGridData() {
        $('#attendance-grid-container').html('<div class="loading">Loading attendance data...</div>');
        
        $.get(tfspAttendance.apiUrl + 'attendance/grid', {
            week_start: currentWeek,
            class_id: 1
        }).done(function(data) {
            gridData = data;
            renderGrid();
        }).fail(function() {
            $('#attendance-grid-container').html('<div class="error">Failed to load data</div>');
        });
    }
    
    // Render grid
    function renderGrid() {
        let html = '<div class="attendance-grid" style="grid-template-columns: 200px repeat(' + gridData.sessions.length + ', 80px);">';
        
        // Header row
        html += '<div class="grid-header">';
        html += '<div class="student-header">Student</div>';
        gridData.sessions.forEach(function(session) {
            let date = new Date(session.session_date);
            html += '<div class="session-header">';
            html += '<div>' + date.toLocaleDateString('en-US', {weekday: 'short'}) + '</div>';
            html += '<div>' + date.getDate() + '</div>';
            html += '<div>' + (session.subject || '') + '</div>';
            html += '</div>';
        });
        html += '</div>';
        
        // Student rows
        gridData.students.forEach(function(student) {
            html += '<div class="student-row">';
            html += '<div class="student-cell" data-student-id="' + student.ID + '">';
            html += '<div class="student-name">' + student.display_name + '</div>';
            html += '<div class="student-email">' + student.user_email + '</div>';
            html += '</div>';
            
            gridData.sessions.forEach(function(session) {
                let attendance = gridData.attendance[student.ID] && gridData.attendance[student.ID][session.id];
                let currentStatus = attendance ? attendance.status : '';
                
                html += '<div class="attendance-cell">';
                html += '<select class="status-select" data-student-id="' + student.ID + '" data-session-id="' + session.id + '">';
                html += '<option value="">-</option>';
                
                let statuses = ['present', 'excused_absence', 'did_not_attend', 'late', 'remote'];
                statuses.forEach(function(status) {
                    let selected = currentStatus === status ? 'selected' : '';
                    html += '<option value="' + status + '" ' + selected + '>' + getStatusLabel(status) + '</option>';
                });
                
                html += '</select>';
                html += '</div>';
            });
            
            html += '</div>';
        });
        
        html += '</div>';
        $('#attendance-grid-container').html(html);
        
        // Bind events
        bindGridEvents();
    }
    
    // Bind grid events
    function bindGridEvents() {
        // Status change
        $('.status-select').on('change', function() {
            let studentId = $(this).data('student-id');
            let sessionId = $(this).data('session-id');
            let status = $(this).val();
            
            // Track pending changes
            if (!pendingChanges[studentId]) {
                pendingChanges[studentId] = {};
            }
            pendingChanges[studentId][sessionId] = {
                session_id: sessionId,
                student_id: studentId,
                status: status
            };
            
            // Update visual state
            $(this).removeClass().addClass('status-select ' + status);
            
            // Show save indicator
            $('#save-all').addClass('button-primary').text('Save Changes *');
        });
        
        // Student 360 click
        $('.student-cell').on('click', function() {
            let studentId = $(this).data('student-id');
            openStudent360(studentId);
        });
    }
    
    // Save changes
    function saveChanges() {
        let records = [];
        
        Object.keys(pendingChanges).forEach(function(studentId) {
            Object.keys(pendingChanges[studentId]).forEach(function(sessionId) {
                records.push(pendingChanges[studentId][sessionId]);
            });
        });
        
        if (records.length === 0) {
            return;
        }
        
        $('#save-all').prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: tfspAttendance.apiUrl + 'attendance/bulk-upsert',
            method: 'POST',
            data: JSON.stringify(records),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', tfspAttendance.nonce);
            }
        }).done(function(response) {
            pendingChanges = {};
            $('#save-all').removeClass('button-primary').prop('disabled', false).text('Save Changes');
            showNotice('Changes saved successfully', 'success');
        }).fail(function() {
            $('#save-all').prop('disabled', false).text('Save Changes *');
            showNotice('Failed to save changes', 'error');
        });
    }
    
    // Open Student 360
    function openStudent360(studentId) {
        let student = gridData.students.find(s => s.ID == studentId);
        if (!student) return;
        
        $('#student-360-title').text('Student 360: ' + student.display_name);
        $('#student-360-modal').show();
        
        // Load attendance tab by default
        loadStudentAttendance(studentId);
    }
    
    // Load student attendance
    function loadStudentAttendance(studentId) {
        $.get(tfspAttendance.apiUrl + 'student360/' + studentId + '/attendance')
        .done(function(data) {
            let html = '<div class="attendance-stats">';
            html += '<div class="stat-card"><div class="stat-number">' + data.stats.percentage + '%</div><div class="stat-label">Attendance Rate</div></div>';
            html += '<div class="stat-card"><div class="stat-number">' + data.stats.present + '</div><div class="stat-label">Present</div></div>';
            html += '<div class="stat-card"><div class="stat-number">' + data.stats.total + '</div><div class="stat-label">Total Sessions</div></div>';
            html += '</div>';
            
            html += '<h4>Recent Sessions</h4>';
            html += '<div class="attendance-history">';
            data.records.forEach(function(record) {
                html += '<div class="attendance-record">';
                html += '<span>' + record.session_date + '</span>';
                html += '<span>' + (record.subject || '') + '</span>';
                html += '<span class="status-badge ' + record.status + '">' + getStatusLabel(record.status) + '</span>';
                html += '</div>';
            });
            html += '</div>';
            
            $('#tab-attendance').html(html);
        });
    }
    
    // Modal events
    $('.modal-close').on('click', function() {
        $('#student-360-modal').hide();
    });
    
    $('.tab-button').on('click', function() {
        let tab = $(this).data('tab');
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        $('.tab-pane').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });
    
    // Helper functions
    function getStatusLabel(status) {
        const labels = {
            'present': 'Present',
            'excused_absence': 'Excused',
            'did_not_attend': 'Absent',
            'late': 'Late',
            'remote': 'Remote',
            'postponed': 'Postponed'
        };
        return labels[status] || status;
    }
    
    function showNotice(message, type) {
        let notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.tfsp-attendance-wrap h1').after(notice);
        setTimeout(function() {
            notice.fadeOut();
        }, 3000);
    }
    
    // Add moment.js fallback for date manipulation
    if (typeof moment === 'undefined') {
        window.moment = function(date) {
            return {
                subtract: function(num, unit) {
                    let d = new Date(date);
                    if (unit === 'week') d.setDate(d.getDate() - (num * 7));
                    return moment(d.toISOString().split('T')[0]);
                },
                add: function(num, unit) {
                    let d = new Date(date);
                    if (unit === 'week') d.setDate(d.getDate() + (num * 7));
                    return moment(d.toISOString().split('T')[0]);
                },
                format: function(fmt) {
                    if (fmt === 'YYYY-MM-DD') {
                        return new Date(date).toISOString().split('T')[0];
                    }
                    return date;
                }
            };
        };
    }
});
