<?php
/**
 * TODO: 
 * Check queries for consistency
 * 
 * FIXME:
 * Worker can still be added even if he is already assigned on a task
 * 
 */

use App\Core\Session;

require_once '../Source/Backend/Config/Config.php';

define('PESO_SIGN', '₱');

// Restore session at the start of every request
Session::restore();

require_once ROUTER_PATH . 'RegisterRoutes.php';

$router->dispatch();
