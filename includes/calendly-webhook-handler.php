<?php
if (!defined('ABSPATH')) {
    require_once(dirname(__FILE__, 5) . '/wp-load.php');
}

class TFSP_Calendly_Webhook_Handler {
    
    public function __construct() {
        add_action('admin_post_nopriv_calendly_webhook', array($this, 'handle_webhook'));
        add_action('admin_post_calendly_webhook', array($this, 'handle_webhook'));
    }
    
    public function handle_webhook() {
        // Log webhook attempt
        error_log('TFSP Calendly Webhook: Received request from ' . $_SERVER['REMOTE_ADDR']);
        
        // Get raw POST data
        $raw_data = file_get_contents('php://input');
        $webhook_data = json_decode($raw_data, true);
        
        // Verify webhook signature (implement based on Calendly docs)
        if (!$this->verify_webhook_signature($raw_data)) {
            error_log('TFSP Calendly Webhook: Invalid signature');
            http_response_code(401);
            exit('Invalid signature');
        }
        
        // Check if integration is enabled
        if (!$this->is_integration_enabled()) {
            error_log('TFSP Calendly Webhook: Integration disabled');
            http_response_code(200);
            exit('Integration disabled');
        }
        
        // Process webhook based on event type
        $event_type = $webhook_data['event'] ?? '';
        
        switch ($event_type) {
            case 'invitee.created':
                $this->handle_appointment_created($webhook_data);
                break;
            case 'invitee.canceled':
                $this->handle_appointment_canceled($webhook_data);
                break;
            case 'invitee.rescheduled':
                $this->handle_appointment_rescheduled($webhook_data);
                break;
            default:
                error_log('TFSP Calendly Webhook: Unknown event type - ' . $event_type);
        }
        
        http_response_code(200);
        exit('OK');
    }
    
    private function verify_webhook_signature($raw_data) {
        // TODO: Implement Calendly webhook signature verification
        // For now, return true for development
        return true;
    }
    
    private function is_integration_enabled() {
        global $wpdb;
        $enabled = $wpdb->get_var(
            "SELECT setting_value FROM {$wpdb->prefix}tfsp_calendly_settings WHERE setting_key = 'enabled'"
        );
        return $enabled == '1';
    }
    
    private function handle_appointment_created($webhook_data) {
        global $wpdb;
        
        try {
            $payload = $webhook_data['payload'] ?? array();
            $event_data = $payload['event'] ?? array();
            $invitee_data = $payload['invitee'] ?? array();
            
            // Extract appointment details
            $calendly_event_id = $event_data['uuid'] ?? '';
            $calendly_uri = $event_data['uri'] ?? '';
            $start_time = $event_data['start_time'] ?? '';
            $invitee_email = $invitee_data['email'] ?? '';
            
            if (empty($calendly_event_id) || empty($invitee_email) || empty($start_time)) {
                throw new Exception('Missing required webhook data');
            }
            
            // Find student by email
            $student = get_user_by('email', $invitee_email);
            if (!$student) {
                error_log("TFSP Calendly Webhook: Student not found for email: $invitee_email");
                return;
            }
            
            // Parse date and time (Calendly UTC -> site timezone)
            $datetime = new DateTime($start_time);
            if (function_exists('wp_timezone')) {
                $site_tz = wp_timezone();
            } else {
                $tz_string = get_option('timezone_string');
                $site_tz = $tz_string ? new DateTimeZone($tz_string) : new DateTimeZone('UTC');
            }
            $datetime->setTimezone($site_tz);
            $session_date = $datetime->format('Y-m-d');
            $session_time = $datetime->format('H:i:s');
            
            // Check if session already exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}tfsp_coach_sessions WHERE calendly_event_id = %s",
                $calendly_event_id
            ));
            
            if ($existing) {
                error_log("TFSP Calendly Webhook: Session already exists for event: $calendly_event_id");
                return;
            }
            
            // Create new session record
            $result = $wpdb->insert(
                $wpdb->prefix . 'tfsp_coach_sessions',
                array(
                    'student_id' => $student->ID,
                    'session_date' => $session_date,
                    'session_time' => $session_time,
                    'status' => 'scheduled',
                    'calendly_event_id' => $calendly_event_id,
                    'calendly_uri' => $calendly_uri,
                    'auto_created' => 1,
                    'webhook_data' => json_encode($webhook_data),
                    'created_at' => current_time('mysql')
                )
            );
            
            if ($result) {
                error_log("TFSP Calendly Webhook: Created session for student {$student->display_name} on $session_date at $session_time");
            } else {
                error_log("TFSP Calendly Webhook: Failed to create session - " . $wpdb->last_error);
            }
            
        } catch (Exception $e) {
            error_log('TFSP Calendly Webhook Error: ' . $e->getMessage());
        }
    }
    
    private function handle_appointment_canceled($webhook_data) {
        global $wpdb;
        
        try {
            $payload = $webhook_data['payload'] ?? array();
            $event_data = $payload['event'] ?? array();
            $calendly_event_id = $event_data['uuid'] ?? '';
            
            if (empty($calendly_event_id)) {
                throw new Exception('Missing event UUID in cancellation webhook');
            }
            
            // Update session status
            $result = $wpdb->update(
                $wpdb->prefix . 'tfsp_coach_sessions',
                array(
                    'status' => 'canceled',
                    'updated_at' => current_time('mysql')
                ),
                array('calendly_event_id' => $calendly_event_id)
            );
            
            if ($result) {
                error_log("TFSP Calendly Webhook: Canceled session for event: $calendly_event_id");
            } else {
                error_log("TFSP Calendly Webhook: Failed to cancel session for event: $calendly_event_id");
            }
            
        } catch (Exception $e) {
            error_log('TFSP Calendly Webhook Error: ' . $e->getMessage());
        }
    }
    
    private function handle_appointment_rescheduled($webhook_data) {
        global $wpdb;
        
        try {
            $payload = $webhook_data['payload'] ?? array();
            $event_data = $payload['event'] ?? array();
            $old_event_data = $payload['old_event'] ?? array();
            
            $new_event_id = $event_data['uuid'] ?? '';
            $old_event_id = $old_event_data['uuid'] ?? '';
            $new_start_time = $event_data['start_time'] ?? '';
            
            if (empty($new_event_id) || empty($old_event_id) || empty($new_start_time)) {
                throw new Exception('Missing required data in reschedule webhook');
            }
            
            // Parse new date and time (Calendly UTC -> site timezone)
            $datetime = new DateTime($new_start_time);
            if (function_exists('wp_timezone')) {
                $site_tz = wp_timezone();
            } else {
                $tz_string = get_option('timezone_string');
                $site_tz = $tz_string ? new DateTimeZone($tz_string) : new DateTimeZone('UTC');
            }
            $datetime->setTimezone($site_tz);
            $session_date = $datetime->format('Y-m-d');
            $session_time = $datetime->format('H:i:s');
            
            // Update existing session
            $result = $wpdb->update(
                $wpdb->prefix . 'tfsp_coach_sessions',
                array(
                    'session_date' => $session_date,
                    'session_time' => $session_time,
                    'calendly_event_id' => $new_event_id,
                    'status' => 'scheduled',
                    'updated_at' => current_time('mysql')
                ),
                array('calendly_event_id' => $old_event_id)
            );
            
            if ($result) {
                error_log("TFSP Calendly Webhook: Rescheduled session from $old_event_id to $new_event_id on $session_date at $session_time");
            } else {
                error_log("TFSP Calendly Webhook: Failed to reschedule session for event: $old_event_id");
            }
            
        } catch (Exception $e) {
            error_log('TFSP Calendly Webhook Error: ' . $e->getMessage());
        }
    }
}

// Initialize webhook handler
new TFSP_Calendly_Webhook_Handler();
?>
