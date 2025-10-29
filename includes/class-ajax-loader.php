<?php
/**
 * AJAX Loader Class
 * Centralized loader for all AJAX handler classes
 */

if (!defined('ABSPATH')) {
    exit;
}

class TFSP_Ajax_Loader {
    
    private static $instance = null;
    private $loaded_handlers = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'load_ajax_handlers'));
    }
    
    public function load_ajax_handlers() {
        $this->load_handler('TFSP_Ajax_Handler', 'class-ajax-handler-consolidated.php');
    }
    
    private function load_handler($class_name, $file_name) {
        if (!isset($this->loaded_handlers[$class_name])) {
            $file_path = TFSP_PLUGIN_PATH . 'includes/' . $file_name;
            
            if (file_exists($file_path)) {
                require_once $file_path;
                
                if (class_exists($class_name)) {
                    $this->loaded_handlers[$class_name] = new $class_name();
                }
            }
        }
    }
    
    public function get_loaded_handlers() {
        return $this->loaded_handlers;
    }
}

// Initialize the AJAX loader
TFSP_Ajax_Loader::get_instance();