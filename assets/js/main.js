jQuery(document).ready(function($) {
    // Initialize the dashboard
    initDashboard();
    
    function initDashboard() {
        loadStudentData();
        initEventHandlers();
        initProgressTracking();
        initAnimations();
    }
    
    function loadStudentData() {
        $.ajax({
            url: tfsp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tfsp_get_student_data',
                nonce: tfsp_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateDashboard(response.data);
                }
            },
            error: function() {
                console.log('Error loading student data');
            }
        });
    }
    
    function updateDashboard(data) {
        const assignments = data.assignments || [];
        const progress = data.progress || [];
        
        // Calculate overall progress
        let totalProgress = 0;
        let completedCount = 0;
        let inProgressCount = 0;
        
        assignments.forEach(assignment => {
            const assignmentProgress = progress.find(p => p.assignment_id == assignment.id);
            const progressPercent = assignmentProgress ? parseInt(assignmentProgress.progress_percentage) : 0;
            
            totalProgress += progressPercent;
            
            if (progressPercent === 100) {
                completedCount++;
            } else if (progressPercent > 0) {
                inProgressCount++;
            } else {
                inProgressCount++;
            }
        });
        
        const overallProgress = assignments.length > 0 ? Math.round(totalProgress / assignments.length) : 0;
        
        // Animate progress updates
        animateCounter('#overall-progress', overallProgress, '%');
        animateCounter('#completed-count', completedCount, '/' + assignments.length);
        animateCounter('#in-progress-count', inProgressCount, '');
        
        // Update upcoming assignments
        updateUpcomingAssignments(assignments, progress);
        
        // Update progress bars in college applications
        updateCollegeApplications(assignments, progress);
    }
    
    function animateCounter(selector, targetValue, suffix) {
        const element = $(selector);
        const startValue = 0;
        const duration = 1500;
        const startTime = performance.now();
        
        function updateCounter(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const currentValue = Math.round(startValue + (targetValue - startValue) * progress);
            
            element.text(currentValue + suffix);
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            }
        }
        
        requestAnimationFrame(updateCounter);
    }
    
    function updateUpcomingAssignments(assignments, progress) {
        const upcomingContainer = $('#upcoming-assignments');
        upcomingContainer.empty();
        
        // Get assignments that are not completed
        const upcomingAssignments = assignments.filter(assignment => {
            const assignmentProgress = progress.find(p => p.assignment_id == assignment.id);
            const progressPercent = assignmentProgress ? parseInt(assignmentProgress.progress_percentage) : 0;
            return progressPercent < 100;
        }).slice(0, 3); // Show only next 3
        
        if (upcomingAssignments.length === 0) {
            upcomingContainer.html('<div class="upcoming-item"><div><h4>🎉 Congratulations!</h4><p>All assignments completed!</p></div><div class="date">Done</div></div>');
            return;
        }
        
        upcomingAssignments.forEach(assignment => {
            const dueDate = new Date(assignment.due_date);
            const formattedDate = dueDate.toLocaleDateString();
            
            const upcomingItem = $(`
                <div class="upcoming-item">
                    <div>
                        <h4>${assignment.title}</h4>
                        <p>${assignment.description}</p>
                    </div>
                    <div class="date">${formattedDate}</div>
                </div>
            `);
            
            upcomingContainer.append(upcomingItem);
        });
    }
    
    function updateCollegeApplications(assignments, progress) {
        assignments.forEach(assignment => {
            const assignmentProgress = progress.find(p => p.assignment_id == assignment.id);
            const progressPercent = assignmentProgress ? parseInt(assignmentProgress.progress_percentage) : 0;
            
            // Update progress bar with animation
            const progressBar = $(`.application-card[data-assignment-id="${assignment.id}"] .progress-fill`);
            progressBar.animate({width: progressPercent + '%'}, 1000);
            
            // Update status
            const statusElement = $(`.application-card[data-assignment-id="${assignment.id}"] .card-status`);
            let statusClass = 'status-not-started';
            let statusText = 'Not Started';
            
            if (progressPercent === 100) {
                statusClass = 'status-completed';
                statusText = 'Completed ✅';
            } else if (progressPercent > 0) {
                statusClass = 'status-in-progress';
                statusText = 'In Progress ⏳';
            }
            
            statusElement.removeClass('status-not-started status-in-progress status-completed')
                         .addClass(statusClass)
                         .text(statusText);
        });
    }
    
    function initEventHandlers() {
        // Document upload
        $(document).on('click', '#upload-document-btn', function() {
            $('#upload-modal').fadeIn(300);
        });
        
        // Modal close
        $(document).on('click', '.modal-close', function() {
            $('.modal').fadeOut(300);
        });
        
        // Close modal on outside click
        $(document).on('click', '.modal', function(e) {
            if (e.target === this) {
                $(this).fadeOut(300);
            }
        });
        
        // Progress update buttons
        $(document).on('click', '.update-progress-btn', function() {
            const assignmentId = $(this).data('assignment-id');
            const card = $(this).closest('.application-card');
            const currentWidth = parseInt(card.find('.progress-fill').css('width')) || 0;
            const cardWidth = card.find('.progress-bar').width();
            const currentProgress = Math.round((currentWidth / cardWidth) * 100);
            const newProgress = Math.min(currentProgress + 25, 100);
            
            updateProgress(assignmentId, newProgress);
        });
        
        // Mark as complete
        $(document).on('click', '.mark-complete-btn', function() {
            const assignmentId = $(this).data('assignment-id');
            updateProgress(assignmentId, 100);
        });
        
        // Schedule meeting
        $(document).on('click', '#schedule-meeting-btn', function() {
            $('#meeting-modal').fadeIn(300);
        });
        
        // File upload handling
        $(document).on('change', '#document-upload', function() {
            const file = this.files[0];
            if (file) {
                uploadDocument(file);
            }
        });
        
        // Drag and drop functionality
        const uploadArea = $('.upload-area');
        
        uploadArea.on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('dragover');
        });
        
        uploadArea.on('dragleave', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
        });
        
        uploadArea.on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
            
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                uploadDocument(files[0]);
            }
        });
        
        // Card hover effects
        $('.application-card').hover(
            function() {
                $(this).addClass('hovered');
            },
            function() {
                $(this).removeClass('hovered');
            }
        );
    }
    
    function updateProgress(assignmentId, progress) {
        const button = $(`.update-progress-btn[data-assignment-id="${assignmentId}"]`);
        const originalText = button.text();
        button.text('Updating...').prop('disabled', true);
        
        $.ajax({
            url: tfsp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tfsp_update_progress',
                nonce: tfsp_ajax.nonce,
                assignment_id: assignmentId,
                progress: progress
            },
            success: function(response) {
                if (response.success) {
                    loadStudentData(); // Refresh dashboard
                    showNotification('Progress updated successfully!', 'success');
                } else {
                    showNotification('Error updating progress', 'error');
                }
            },
            error: function() {
                showNotification('Error updating progress', 'error');
            },
            complete: function() {
                button.text(originalText).prop('disabled', false);
            }
        });
    }
    
    function uploadDocument(file) {
        // Validate file size (10MB limit)
        if (file.size > 10 * 1024 * 1024) {
            showNotification('File size must be less than 10MB', 'error');
            return;
        }
        
        // Validate file type
        const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            showNotification('Invalid file type. Please upload PDF, DOC, DOCX, JPG, or PNG files.', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('document', file);
        formData.append('action', 'tfsp_upload_document');
        formData.append('nonce', tfsp_ajax.nonce);
        formData.append('assignment_id', $('#assignment-select').val() || 1);
        
        // Show upload progress
        showNotification('Uploading document...', 'info');
        
        $.ajax({
            url: tfsp_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotification('Document uploaded successfully!', 'success');
                    $('.modal').fadeOut(300);
                } else {
                    showNotification('Upload failed: ' + (response.data || 'Unknown error'), 'error');
                }
            },
            error: function() {
                showNotification('Upload failed. Please try again.', 'error');
            }
        });
    }
    
    function initProgressTracking() {
        // Auto-refresh data every 5 minutes
        setInterval(function() {
            loadStudentData();
        }, 300000);
        
        // Save progress automatically when user is active
        let lastActivity = Date.now();
        $(document).on('click keypress', function() {
            lastActivity = Date.now();
        });
        
        // Check for inactivity and save progress
        setInterval(function() {
            if (Date.now() - lastActivity > 60000) { // 1 minute of inactivity
                // Auto-save any pending changes
                console.log('Auto-saving progress...');
            }
        }, 60000);
    }
    
    function initAnimations() {
        // Animate cards on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        });
        
        $('.application-card, .progress-card').each(function() {
            this.style.opacity = '0';
            this.style.transform = 'translateY(20px)';
            this.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(this);
        });
        
        // Stagger animation for cards
        $('.application-card').each(function(index) {
            $(this).css('animation-delay', (index * 0.1) + 's');
        });
    }
    
    function showNotification(message, type) {
        // Remove existing notifications
        $('.tfsp-notification').remove();
        
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            info: '#3b82f6',
            warning: '#f59e0b'
        };
        
        const notification = $(`
            <div class="tfsp-notification" style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${colors[type] || colors.info};
                color: white;
                padding: 16px 24px;
                border-radius: 12px;
                z-index: 1001;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
                font-weight: 600;
                max-width: 400px;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
            ">
                ${message}
            </div>
        `);
        
        $('body').append(notification);
        
        // Animate in
        setTimeout(() => {
            notification.css({
                opacity: '1',
                transform: 'translateX(0)'
            });
        }, 100);
        
        // Auto remove after 4 seconds
        setTimeout(function() {
            notification.css({
                opacity: '0',
                transform: 'translateX(100%)'
            });
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }
    
    // Initialize tooltips for better UX
    function initTooltips() {
        $('[data-tooltip]').hover(
            function() {
                const tooltip = $('<div class="tooltip">' + $(this).data('tooltip') + '</div>');
                $('body').append(tooltip);
                
                const pos = $(this).offset();
                tooltip.css({
                    position: 'absolute',
                    top: pos.top - tooltip.outerHeight() - 10,
                    left: pos.left + ($(this).outerWidth() / 2) - (tooltip.outerWidth() / 2),
                    background: '#1a202c',
                    color: 'white',
                    padding: '8px 12px',
                    borderRadius: '6px',
                    fontSize: '14px',
                    zIndex: 1002
                });
            },
            function() {
                $('.tooltip').remove();
            }
        );
    }
    
    initTooltips();
});
