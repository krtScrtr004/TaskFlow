<?php
require_once '../source/backend/config/config.php';

require_once ROUTER_PATH . 'register-routes.php';

$router->dispatch();
