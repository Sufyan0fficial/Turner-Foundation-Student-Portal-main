/**
 * Turner Foundation Student Portal - Frontend JavaScript
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        TFSP.init();
    });

    // Main TFSP object
    window.TFSP = {
        
        // Initialize all functionality
        init: function() {
            this.initProgressTracking();
            this.initDocumentUpload();
            this.initRegistrationForm();
            this.initMeetingScheduler();
            this.initNotifications();
            this.bindEvents();
        },

        // Progress tracking functionality
        initProgressTracking: function() {
            $('.tfsp-progress-btn').on('click', function(e) {
                e.preventDefault();
                
                const $btn = $(this);
                const applicationIndex = $btn.data('app-index');
                const status = $btn.data('status');
                
                TFSP.updateProgress(applicationIndex, status, $btn);
            });
        },

        // Update application progress
        updateProgress: function(applicationIndex, status, $btn) {
            const originalText = $btn.text();
            $btn.text('Updating...').prop('disabled', true);

            $.ajax({
                url: tfsp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'tfsp_update_progress',
                    nonce: tfsp_ajax.nonce,
                    application_index: applicationIndex,
                    status: status
                },
                success: function(response) {
                    if (response.success) {
                        TFSP.showNotification('Success: ' + response.data.message, 'success');
                        
                        // Update UI
                        const $card = $btn.closest('.tfsp-application-card');
                        if (status === 'completed') {
                            $card.addClass('completed');
                            $card.find('.tfsp-application-actions').html(
                                '<span class="tfsp-completed-badge">✅ Completed</span>'
                            );
                        }
                        
                        // Refresh progress stats
                        TFSP.refreshProgressStats();
                    } else {
                        TFSP.showNotification('Error: ' + response.data, 'error');
                    }
                },
                error: function() {
                    TFSP.showNotification('Network error occurred', 'error');
                },
                complete: function() {
                    $btn.text(originalText).prop('disabled', false);
                }
            });
        },

        // Document upload functionality
        initDocumentUpload: function() {
            const $uploadArea = $('.tfsp-upload-area');
            
            if ($uploadArea.length) {
                // Click to upload
                $uploadArea.on('click', function() {
                    TFSP.openFileDialog();
                });

                // Drag and drop
                $uploadArea.on('dragover', function(e) {
                    e.preventDefault();
                    $(this).addClass('dragover');
                });

                $uploadArea.on('dragleave', function(e) {
                    e.preventDefault();
                    $(this).removeClass('dragover');
                });

                $uploadArea.on('drop', function(e) {
                    e.preventDefault();
                    $(this).removeClass('dragover');
                    
                    const files = e.originalEvent.dataTransfer.files;
                    TFSP.handleFileUpload(files);
                });
            }
        },

        // Open file dialog
        openFileDialog: function() {
            const input = document.createElement('input');
            input.type = 'file';
            input.multiple = true;
            input.accept = '.pdf,.doc,.docx,.jpg,.jpeg,.png';
            
            input.onchange = function(e) {
                const files = e.target.files;
                if (files.length > 0) {
                    TFSP.handleFileUpload(files);
                }
            };
            
            input.click();
        },

        // Handle file upload
        handleFileUpload: function(files) {
            if (!files || files.length === 0) return;

            const formData = new FormData();
            formData.append('action', 'tfsp_upload_document');
            formData.append('nonce', tfsp_ajax.nonce);

            // Add files to form data
            for (let i = 0; i < files.length; i++) {
                formData.append('documents[]', files[i]);
            }

            // Show upload progress
            TFSP.showUploadProgress(files.length);

            $.ajax({
                url: tfsp_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        TFSP.showNotification('Files uploaded successfully!', 'success');
                        TFSP.refreshDocumentsList();
                    } else {
                        TFSP.showNotification('Upload failed: ' + response.data, 'error');
                    }
                },
                error: function() {
                    TFSP.showNotification('Upload failed due to network error', 'error');
                },
                complete: function() {
                    TFSP.hideUploadProgress();
                }
            });
        },

        // Registration form functionality
        initRegistrationForm: function() {
            const $form = $('#student-registration-form');
            
            if ($form.length) {
                $form.on('submit', function(e) {
                    e.preventDefault();
                    TFSP.handleRegistration($form);
                });
            }
        },

        // Handle student registration
        handleRegistration: function($form) {
            const formData = new FormData($form[0]);
            const $submitBtn = $form.find('button[type="submit"]');
            const $messageDiv = $('#registration-message');
            
            // Validate form
            if (!TFSP.validateRegistrationForm($form)) {
                return;
            }

            // Show loading state
            const originalText = $submitBtn.html();
            $submitBtn.html('⏳ Creating Account...').prop('disabled', true);

            $.ajax({
                url: tfsp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'tfsp_register_student',
                    nonce: formData.get('nonce'),
                    first_name: formData.get('first_name'),
                    last_name: formData.get('last_name'),
                    email: formData.get('email'),
                    password: formData.get('password'),
                    phone: formData.get('phone'),
                    school_name: formData.get('school_name'),
                    graduation_year: formData.get('graduation_year')
                },
                success: function(response) {
                    if (response.success) {
                        $messageDiv.removeClass('error').addClass('success').show();
                        $messageDiv.html('✅ ' + response.data.message);
                        
                        // Redirect after 2 seconds
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url;
                        }, 2000);
                    } else {
                        $messageDiv.removeClass('success').addClass('error').show();
                        $messageDiv.html('❌ ' + response.data);
                        
                        $submitBtn.html(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    $messageDiv.removeClass('success').addClass('error').show();
                    $messageDiv.html('❌ Network error. Please try again.');
                    
                    $submitBtn.html(originalText).prop('disabled', false);
                }
            });
        },

        // Validate registration form
        validateRegistrationForm: function($form) {
            let isValid = true;
            const requiredFields = ['first_name', 'last_name', 'email', 'password'];
            
            requiredFields.forEach(function(field) {
                const $field = $form.find('[name="' + field + '"]');
                if (!$field.val().trim()) {
                    $field.addClass('error');
                    isValid = false;
                } else {
                    $field.removeClass('error');
                }
            });

            // Validate email format
            const email = $form.find('[name="email"]').val();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailRegex.test(email)) {
                $form.find('[name="email"]').addClass('error');
                isValid = false;
            }

            // Check terms acceptance
            if (!$form.find('[name="terms"]').is(':checked')) {
                TFSP.showNotification('Please accept the terms and conditions', 'error');
                isValid = false;
            }

            if (!isValid) {
                TFSP.showNotification('Please fill in all required fields correctly', 'error');
            }

            return isValid;
        },

        // Meeting scheduler functionality
        initMeetingScheduler: function() {
            $('.tfsp-schedule-meeting-btn').on('click', function(e) {
                e.preventDefault();
                TFSP.openMeetingScheduler();
            });
        },

        // Open meeting scheduler modal
        openMeetingScheduler: function() {
            // This would open a modal with meeting scheduling options
            alert('Meeting Scheduler\n\nThis will open a calendar interface where you can:\n- Select available time slots\n- Choose meeting type\n- Add agenda items\n- Confirm booking');
        },

        // Notification system
        initNotifications: function() {
            // Check for new notifications periodically
            if (tfsp_ajax.user_id) {
                setInterval(TFSP.checkNotifications, 30000); // Every 30 seconds
            }
        },

        // Check for new notifications
        checkNotifications: function() {
            $.ajax({
                url: tfsp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'tfsp_get_notifications',
                    nonce: tfsp_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        TFSP.displayNotifications(response.data);
                    }
                }
            });
        },

        // Display notifications
        displayNotifications: function(notifications) {
            notifications.forEach(function(notification) {
                TFSP.showNotification(notification.message, notification.type, 5000);
            });
        },

        // Show notification
        showNotification: function(message, type, duration) {
            type = type || 'info';
            duration = duration || 3000;

            const $notification = $('<div class="tfsp-notification tfsp-notification-' + type + '">')
                .html(message)
                .appendTo('body');

            // Position notification
            $notification.css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                zIndex: 9999,
                padding: '15px 20px',
                borderRadius: '8px',
                boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                maxWidth: '400px',
                fontSize: '14px',
                fontWeight: '500'
            });

            // Style based on type
            switch (type) {
                case 'success':
                    $notification.css({
                        background: '#d1fae5',
                        color: '#065f46',
                        border: '1px solid #10b981'
                    });
                    break;
                case 'error':
                    $notification.css({
                        background: '#fef2f2',
                        color: '#991b1b',
                        border: '1px solid #ef4444'
                    });
                    break;
                case 'warning':
                    $notification.css({
                        background: '#fef3c7',
                        color: '#92400e',
                        border: '1px solid #f59e0b'
                    });
                    break;
                default:
                    $notification.css({
                        background: '#dbeafe',
                        color: '#1e40af',
                        border: '1px solid #3b82f6'
                    });
            }

            // Auto-hide notification
            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, duration);

            // Click to dismiss
            $notification.on('click', function() {
                $(this).fadeOut(300, function() {
                    $(this).remove();
                });
            });
        },

        // Show upload progress
        showUploadProgress: function(fileCount) {
            const $progress = $('<div class="tfsp-upload-progress">')
                .html('Uploading ' + fileCount + ' file(s)... <div class="tfsp-spinner"></div>')
                .appendTo('body');

            $progress.css({
                position: 'fixed',
                top: '50%',
                left: '50%',
                transform: 'translate(-50%, -50%)',
                background: 'white',
                padding: '20px 30px',
                borderRadius: '8px',
                boxShadow: '0 10px 25px rgba(0,0,0,0.2)',
                zIndex: 10000,
                textAlign: 'center',
                fontSize: '16px',
                fontWeight: '500'
            });
        },

        // Hide upload progress
        hideUploadProgress: function() {
            $('.tfsp-upload-progress').fadeOut(300, function() {
                $(this).remove();
            });
        },

        // Refresh progress stats (only on dashboard)
        refreshProgressStats: function() {
            // Only refresh stats if we're on a page with dashboard elements
            if ($('.tfsp-overall-progress').length === 0) {
                return;
            }
            
            $.ajax({
                url: tfsp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'tfsp_get_dashboard_data',
                    nonce: tfsp_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update progress statistics in the UI
                        $('.tfsp-overall-progress').text(response.data.overall_progress + '%');
                        $('.tfsp-completed-count').text(response.data.completed_applications + '/10');
                        $('.tfsp-in-progress-count').text(response.data.in_progress_applications);
                    }
                }
            });
        },

        // Refresh documents list
        refreshDocumentsList: function() {
            // This would refresh the documents section if it exists
            console.log('Documents list refreshed');
        },

        // Bind additional events
        bindEvents: function() {
            // Smooth scrolling for anchor links
            $('a[href^="#"]').on('click', function(e) {
                e.preventDefault();
                const target = $($(this).attr('href'));
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 500);
                }
            });

            // Form field focus effects
            $('.tfsp-form-group input, .tfsp-form-group select').on('focus', function() {
                $(this).parent().addClass('focused');
            }).on('blur', function() {
                $(this).parent().removeClass('focused');
            });

            // Tooltip functionality
            $('[data-tooltip]').on('mouseenter', function() {
                const tooltip = $(this).data('tooltip');
                const $tooltip = $('<div class="tfsp-tooltip">').text(tooltip);
                
                $tooltip.css({
                    position: 'absolute',
                    background: '#333',
                    color: 'white',
                    padding: '8px 12px',
                    borderRadius: '4px',
                    fontSize: '12px',
                    zIndex: 1000,
                    whiteSpace: 'nowrap'
                });

                $('body').append($tooltip);

                const rect = this.getBoundingClientRect();
                $tooltip.css({
                    top: rect.top - $tooltip.outerHeight() - 5,
                    left: rect.left + (rect.width / 2) - ($tooltip.outerWidth() / 2)
                });
            }).on('mouseleave', function() {
                $('.tfsp-tooltip').remove();
            });
        },

        // Utility functions
        utils: {
            // Format date
            formatDate: function(date) {
                return new Date(date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            },

            // Format file size
            formatFileSize: function(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            },

            // Debounce function
            debounce: function(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = function() {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }
        }
    };

})(jQuery);
