<?php
require_once '../source/backend/config/config.php';

define('PESO_SIGN', '₱');

require_once ROUTER_PATH . 'register-routes.php';

$router->dispatch();
