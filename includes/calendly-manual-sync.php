<?php
if (!defined('ABSPATH')) exit;

class TFSP_Calendly_Manual_Sync {
    
    private $api_token;
    private $user_uri;
    private $organization_uri;
    
    public function __construct() {
        add_action('wp_ajax_sync_calendly_events', array($this, 'sync_events'));
        add_action('wp_ajax_nopriv_sync_calendly_events', array($this, 'sync_events'));
        $this->load_settings();
    }
    
    private function load_settings() {
        global $wpdb;
        $settings = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}tfsp_calendly_settings", OBJECT_K);
        $this->api_token = isset($settings['api_token']) ? ($settings['api_token']->setting_value ?? '') : '';
        $this->user_uri = isset($settings['user_uri']) ? ($settings['user_uri']->setting_value ?? '') : '';
        $this->organization_uri = isset($settings['organization_uri']) ? ($settings['organization_uri']->setting_value ?? '') : '';
    }
    
    public function sync_events() {
        // Verify nonce for external portal requests
        if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'sync_calendly_events')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (empty($this->api_token)) {
            wp_send_json_error('Calendly API token not configured');
        }
        
        try {
            // Ensure we have a valid user URI before fetching
            $this->ensure_user_uri();

            $events = $this->fetch_scheduled_events();
            $synced = $this->process_events($events);
            
            wp_send_json_success(array(
                'message' => "Synced $synced events successfully",
                'events_processed' => $synced
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Sync failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Ensure user_uri is set and valid by querying users/me when missing or placeholder.
     */
    private function ensure_user_uri() {
        $data = $this->api_get('https://api.calendly.com/users/me');
        if (!isset($data['resource']['uri'])) {
            throw new Exception('Unable to determine Calendly user.');
        }

        $me_user_uri = $data['resource']['uri'];
        $me_org_uri = $data['resource']['current_organization'] ?? '';

        // If missing or mismatched, use the authoritative one from users/me
        if (empty($this->user_uri) || $this->user_uri !== $me_user_uri) {
            $this->user_uri = $me_user_uri;
        }
        if (empty($this->organization_uri) || $this->organization_uri !== $me_org_uri) {
            $this->organization_uri = $me_org_uri;
        }

        // Persist discovered URIs
        global $wpdb;
        if (!empty($this->user_uri)) {
            $wpdb->replace(
                $wpdb->prefix . 'tfsp_calendly_settings',
                array('setting_key' => 'user_uri', 'setting_value' => $this->user_uri)
            );
        }
        if (!empty($this->organization_uri)) {
            $wpdb->replace(
                $wpdb->prefix . 'tfsp_calendly_settings',
                array('setting_key' => 'organization_uri', 'setting_value' => $this->organization_uri)
            );
        }
    }

    /**
     * Perform an authenticated GET and return decoded JSON or throw with detailed message.
     */
    private function api_get($url) {
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_token,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code < 200 || $code >= 300) {
            $title = $data['title'] ?? 'HTTP ' . $code;
            $message = $data['message'] ?? 'Unknown error';
            // Append details when available
            if (isset($data['details']) && is_array($data['details'])) {
                $detail_msgs = array();
                foreach ($data['details'] as $d) {
                    $detail_msgs[] = ($d['parameter'] ?? 'param') . ': ' . ($d['message'] ?? 'invalid');
                }
                $message .= ' (' . implode('; ', $detail_msgs) . ')';
            }
            throw new Exception($title . ': ' . $message);
        }

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON from Calendly');
        }

        return $data;
    }

    private function fetch_scheduled_events() {
        if (empty($this->user_uri)) {
            $this->ensure_user_uri();
        }

        $collected = array();
        // Include all statuses (active and canceled) by omitting status filter
        $url = 'https://api.calendly.com/scheduled_events?user=' . urlencode($this->user_uri) . '&sort=start_time:asc&count=20';

        // Follow pagination
        while (!empty($url)) {
            $data = $this->api_get($url);
            if (!isset($data['collection'])) {
                throw new Exception('Invalid API response: missing collection');
            }
            $collected = array_merge($collected, $data['collection']);
            $url = $data['pagination']['next_page'] ?? null;
        }

        return $collected;
    }
    
    private function process_events($events) {
        global $wpdb;
        $synced = 0;
        
        foreach ($events as $event) {
            $event_uuid = $event['uri'] ?? '';
            $start_time = $event['start_time'] ?? '';
            $status = $event['status'] ?? 'active';
            
            if (empty($event_uuid) || empty($start_time)) {
                continue;
            }
            
            // Get invitee information. If canceled and no invitees returned, try to update existing session anyway.
            $invitees = $this->fetch_event_invitees($event_uuid);
            $invitee = $invitees[0] ?? null; // Assuming one-on-one sessions
            $invitee_email = $invitee['email'] ?? '';
            
            // If we couldn't resolve invitee (e.g., canceled event hides invitees), try to update existing record status
            if (empty($invitee_email)) {
                $existing_row = $wpdb->get_row($wpdb->prepare(
                    "SELECT id, status FROM {$wpdb->prefix}tfsp_coach_sessions WHERE calendly_uri = %s",
                    $event_uuid
                ));
                if ($existing_row) {
                    $new_status = ($status === 'active') ? 'scheduled' : 'canceled';
                    // Only update if auto-created or currently scheduled/canceled to avoid overriding attended/completed
                    $should_update = in_array($existing_row->status, array('scheduled','canceled'), true);
                    if ($should_update) {
                        $wpdb->update(
                            $wpdb->prefix . 'tfsp_coach_sessions',
                            array(
                                'session_date' => $session_date,
                                'session_time' => $session_time,
                                'status' => $new_status,
                            ),
                            array('id' => $existing_row->id)
                        );
                        error_log("TFSP Calendly Sync: Updated existing session (no invitee) to status $new_status");
                    }
                }
                // Without invitee email we cannot create new records; proceed to next event
                continue;
            }

            // Find student by email
            $student = get_user_by('email', $invitee_email);
            if (!$student) {
                error_log("TFSP Calendly Sync: Student not found for email: $invitee_email");
                continue;
            }
            
            // Parse date and time (convert from Calendly UTC to site timezone)
            $datetime = new DateTime($start_time); // Calendly returns ISO8601 Zulu (UTC)
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
            $existing_row = $wpdb->get_row($wpdb->prepare(
                "SELECT id, session_date, session_time, status, auto_created FROM {$wpdb->prefix}tfsp_coach_sessions WHERE calendly_uri = %s",
                $event_uuid
            ));

            if ($existing_row) {
                // If auto-created and time/date differ, update to corrected timezone values
                $is_basic = in_array($existing_row->status, array('scheduled','canceled'), true);
                if ($is_basic &&
                    ($existing_row->session_date !== $session_date || $existing_row->session_time !== $session_time)) {
                    $wpdb->update(
                        $wpdb->prefix . 'tfsp_coach_sessions',
                        array(
                            'session_date' => $session_date,
                            'session_time' => $session_time,
                            'status' => $status === 'active' ? 'scheduled' : 'canceled',
                        ),
                        array('id' => $existing_row->id)
                    );
                    error_log("TFSP Calendly Sync: Updated session time for {$student->display_name} to $session_date $session_time");
                }
                continue; // Skip insert
            }
            
            // Create session record
            $result = $wpdb->insert(
                $wpdb->prefix . 'tfsp_coach_sessions',
                array(
                    'student_id' => $student->ID,
                    'session_date' => $session_date,
                    'session_time' => $session_time,
                    'status' => $status === 'active' ? 'scheduled' : 'canceled',
                    'calendly_uri' => $event_uuid,
                    'auto_created' => 1,
                    'created_at' => current_time('mysql')
                )
            );
            
            if ($result) {
                $synced++;
                error_log("TFSP Calendly Sync: Created session for {$student->display_name} on $session_date at $session_time");
            }
        }
        
        return $synced;
    }
    
    private function fetch_event_invitees($event_uri) {
        $url = rtrim($event_uri, '/') . '/invitees';

        try {
            $data = $this->api_get($url);
            return $data['collection'] ?? array();
        } catch (Exception $e) {
            // Log but do not fail the whole sync for a single event
            error_log('TFSP Calendly Sync: invitees fetch failed: ' . $e->getMessage());
            return array();
        }
    }
}

new TFSP_Calendly_Manual_Sync();
?>
