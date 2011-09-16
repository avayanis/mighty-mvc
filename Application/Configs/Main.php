<?php

return array(
    
    // Development configuration.
    'dev' => array(
        
        // PHP environment settings
        'php_settings' => array(
            'error_reporting' => E_ALL,
            'display_errors' => 1,
            'display_startup_errors' => 1,
        ),
        
        // Error handler
        'error_handler' => '',

		// Debug mode
		'debug' => false,
        
        // MM_Events
        'events' => array(
            'max_depth' => 20
        ),
        
        // List of globally loaded plugins
        'plugins' => array(
        ),
    ),
);