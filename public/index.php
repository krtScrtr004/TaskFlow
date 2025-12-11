<?php
/**
 * TODO: 
 * Check queries for consistency
 * 
 * FIXME:
 * Icon paths on JS script
 * Worker can still be added even if he is already assigned on a task
 * 
 */
    
require_once '../source/backend/config/config.php';

define('PESO_SIGN', '₱');

// Restore session at the start of every request
App\Core\Session::restore();

require_once ROUTER_PATH . 'register-routes.php';

$router->dispatch();
