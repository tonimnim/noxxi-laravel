<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Health Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configure thresholds and settings for system health monitoring
    |
    */

    'thresholds' => [
        'database' => [
            'connections' => [
                'warning' => 70,  // % of max connections
                'critical' => 90,
            ],
            'query_time' => [
                'warning' => 500,   // milliseconds
                'critical' => 1000,
            ],
            'size' => [
                'warning' => 50,   // GB
                'critical' => 100,
            ],
        ],
        
        'application' => [
            'failed_jobs' => [
                'info' => 1,
                'warning' => 10,
                'critical' => 100,
            ],
            'queue_size' => [
                'warning' => 100,
                'critical' => 1000,
            ],
            'api_response' => [
                'warning' => 500,   // milliseconds
                'critical' => 1000,
            ],
        ],
        
        'system' => [
            'memory' => [
                'warning' => 70,   // % of memory limit
                'critical' => 90,
            ],
            'disk' => [
                'warning' => 70,   // % of disk space
                'critical' => 90,
            ],
        ],
    ],
    
    'cache_ttl' => [
        'database' => 120,    // 2 minutes
        'application' => 60,  // 1 minute
        'system' => 30,       // 30 seconds
    ],
    
    'monitoring' => [
        'log_critical_issues' => true,
        'alert_on_critical' => true,
        'check_interval' => 60, // seconds
    ],
];