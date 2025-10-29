<?php
class TFSP_Security {
    public static function sanitize_input($input, $type = "text") {
        switch ($type) {
            case "email":
                return sanitize_email($input);
            case "url":
                return esc_url_raw($input);
            case "int":
                return intval($input);
            case "key":
                return sanitize_key($input);
            case "textarea":
                return sanitize_textarea_field($input);
            default:
                return sanitize_text_field($input);
        }
    }
    
    public static function escape_output($output, $context = "html") {
        switch ($context) {
            case "attr":
                return esc_attr($output);
            case "url":
                return esc_url($output);
            case "js":
                return esc_js($output);
            default:
                return esc_html($output);
        }
    }
    
    public static function verify_nonce($action) {
        return wp_verify_nonce($_POST["_wpnonce"] ?? "", $action);
    }
}
?>