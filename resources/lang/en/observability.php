<?php

return [

    // Health check
    'health_ok'       => 'All systems operational.',
    'health_degraded' => 'System performance is degraded.',
    'health_down'     => 'System is currently unavailable.',

    // Status labels
    'status_healthy'   => 'Healthy',
    'status_warning'   => 'Warning',
    'status_critical'  => 'Critical',
    'status_unknown'   => 'Unknown',

    // Check names
    'check_database'   => 'Database',
    'check_cache'      => 'Cache',
    'check_queue'      => 'Queue',
    'check_storage'    => 'Storage',
    'check_scheduler'  => 'Scheduler',

    // Messages
    'last_checked'     => 'Last checked :time.',
    'check_passed'     => ':check is operational.',
    'check_failed'     => ':check is not responding.',

];
