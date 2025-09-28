<?php

define('ABS_PATH', dirname(__DIR__, 2) . '/');
define('REDIRECT_PATH', 'http://localhost/Techstock/');

// Absolute paths
define('BACKEND_PATH', ABS_PATH . 'backend' . DS);
define('FRONTEND_PATH', ABS_PATH . 'frontend' . DS);

define('CONFIG_PATH', BACKEND_PATH . 'config' . DS);
define('VENDOR_PATH', BACKEND_PATH . 'vendor' . DS);

define('VIEW_PATH', FRONTEND_PATH . 'view' . DS);
define('COMPONENT_PATH', FRONTEND_PATH . 'component' . DS);

define('DIALOG_PATH', COMPONENT_PATH . 'dialog' . DS);
define('FUNCTION_COMPONENT_PATH', COMPONENT_PATH . 'function' . DS);

define('CORE_PATH', BACKEND_PATH . 'core' . DS);
define('CONTAINER_PATH', BACKEND_PATH . 'container' . DS);
define('CONTROLLER_PATH', BACKEND_PATH . 'controller' . DS);
define('DATA_PATH', BACKEND_PATH . 'data' . DS);
define('ENTITY_PATH', BACKEND_PATH . 'entity' . DS);
define('ENUM_PATH', BACKEND_PATH . 'enum' . DS);
define('MIDDLEWARE_PATH', BACKEND_PATH . 'middleware' . DS);
define('MODEL_PATH', BACKEND_PATH . 'model' . DS);
define('ROUTER_PATH', BACKEND_PATH . 'router' . DS);

define('DEPENDENT_PATH', ENTITY_PATH . 'dependent' . DS);

define('BE_UTILITY_PATH', BACKEND_PATH . 'utility' . DS);
define('FE_UTILITY_PATH', FRONTEND_PATH . 'utility' . DS);

// Relative paths
define('PUBLIC_PATH', DS . 'Techstock' . DS . 'public' . DS);

define('ASSET_PATH', 'asset' . DS);
define('SCRIPT_PATH', 'script' . DS);
define('STYLE_PATH', 'style' . DS);

define('EVENT_PATH', SCRIPT_PATH . 'event' . DS);

define('IMAGE_PATH', ASSET_PATH . 'image' . DS);
define('LOGO_PATH', IMAGE_PATH . 'logo' . DS);
define('ICON_PATH', IMAGE_PATH . 'icon' . DS);

define('VIDEO_PATH', ASSET_PATH . 'video' . DS);

// README: Remove this
define('DUMP_PATH', FRONTEND_PATH . 'dump' . DS);
