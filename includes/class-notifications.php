<?php
if (!defined('ABSPATH')) {
    exit;
}

class TFSP_Notifications {
    
    /**
     * Display success message
     */
    public static function success($message) {
        return '<div class="tfsp-notification tfsp-success" role="alert">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>' . esc_html($message) . '</span>
        </div>';
    }
    
    /**
     * Display error message
     */
    public static function error($message) {
        return '<div class="tfsp-notification tfsp-error" role="alert">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span>' . esc_html($message) . '</span>
        </div>';
    }
    
    /**
     * Display warning message
     */
    public static function warning($message) {
        return '<div class="tfsp-notification tfsp-warning" role="alert">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span>' . esc_html($message) . '</span>
        </div>';
    }
    
    /**
     * Display info message
     */
    public static function info($message) {
        return '<div class="tfsp-notification tfsp-info" role="alert">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <span>' . esc_html($message) . '</span>
        </div>';
    }
    
    /**
     * Get notification styles
     */
    public static function get_styles() {
        return '
        <style>
        .tfsp-notification {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border-radius: 8px;
            margin: 16px 0;
            font-family: "Inter", sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .tfsp-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .tfsp-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .tfsp-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .tfsp-info {
            background-color: #cce7ff;
            color: #004085;
            border: 1px solid #b3d7ff;
        }
        
        .tfsp-notification svg {
            flex-shrink: 0;
        }
        </style>';
    }
}
?>
