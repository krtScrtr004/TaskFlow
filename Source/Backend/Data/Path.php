<?php

define('ROOT_PATH', dirname(__DIR__, 3) . DS);

define('ABS_PATH',  ROOT_PATH . 'Source' . DS);
define('REDIRECT_PATH', 'http://TaskFlow.local/');
define('VENDOR_PATH', ROOT_PATH . 'vendor' . DS);

// Absolute paths
define('BACKEND_PATH', ABS_PATH . 'Backend' . DS);
define('FRONTEND_PATH', ABS_PATH . 'Frontend' . DS);

define('CONFIG_PATH', BACKEND_PATH . 'Config' . DS);

define('VIEW_PATH', FRONTEND_PATH . 'View' . DS);
define('SUB_VIEW_PATH', VIEW_PATH . 'SubView' . DS);
define('COMPONENT_PATH', FRONTEND_PATH . 'Component' . DS);

define('DIALOG_PATH', COMPONENT_PATH . 'Dialog' . DS);
define('FUNCTION_COMPONENT_PATH', COMPONENT_PATH . 'Function' . DS);

define('ABSTRACT_PATH', BACKEND_PATH . 'Abstract' . DS);
define('AUTH_PATH', BACKEND_PATH . 'Auth' . DS);
define('CORE_PATH', BACKEND_PATH . 'Core' . DS);
define('CONTAINER_PATH', BACKEND_PATH . 'Container' . DS);
define('CONTROLLER_PATH', BACKEND_PATH . 'Controller' . DS);
define('DATA_PATH', BACKEND_PATH . 'Data' . DS);
define('ENDPOINT_PATH', BACKEND_PATH . 'Endpoint' . DS);
define('ENTITY_PATH', BACKEND_PATH . 'Entity' . DS);
define('ENUM_PATH', BACKEND_PATH . 'Enumeration' . DS);
define('EXCEPTION_PATH', BACKEND_PATH . 'Exception' . DS);
define('INTERFACE_PATH', BACKEND_PATH . 'Interface' . DS);
define('MIDDLEWARE_PATH', BACKEND_PATH . 'Middleware' . DS);
define('MODEL_PATH', BACKEND_PATH . 'Model' . DS);
define('LOG_PATH', BACKEND_PATH . 'Log' . DS);
define('SERVICE_PATH', BACKEND_PATH . 'Service' . DS);
define('ROUTER_PATH', BACKEND_PATH . 'Router' . DS);
define('VALIDATOR_PATH', BACKEND_PATH . 'Validator' . DS);

define('BE_UTILITY_PATH', BACKEND_PATH . 'Utility' . DS);
define('FE_UTILITY_PATH', FRONTEND_PATH . 'Utility' . DS);

// Relative paths (with leading slash for absolute path from domain root)
define('PUBLIC_PATH', DS . 'Public' . DS);

define('ASSET_PATH', PUBLIC_PATH . 'Asset' . DS);
define('SCRIPT_PATH', PUBLIC_PATH . 'Script' . DS);
define('STYLE_PATH', PUBLIC_PATH . 'Style' . DS);

define('EVENT_PATH', SCRIPT_PATH . 'Event' . DS);
define('IMAGE_PATH', ASSET_PATH . 'Image' . DS);
define('LOGO_PATH', IMAGE_PATH . 'Logo' . DS);
define('ICON_PATH', IMAGE_PATH . 'Icon' . DS);

define('VIDEO_PATH', ASSET_PATH . 'Video' . DS);
// README: Remove this
define('DUMP_PATH', FRONTEND_PATH . 'Dump' . DS);
