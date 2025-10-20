<?php

define('DS', '/');

require_once __DIR__ . DS . 'path.php';

require_once VENDOR_PATH . 'autoload.php';

// require_once __DIR__ . DS . 'env.php';

spl_autoload_register(function ($class) {
    $paths = [
        ABSTRACT_PATH,
        CONFIG_PATH,
        CONTAINER_PATH,
        CORE_PATH,
        DEPENDENT_PATH,
        DUMP_PATH,
        ENTITY_PATH,
        ENUM_PATH,
        INTERFACE_PATH, 
        ROUTER_PATH,
        MIDDLEWARE_PATH,
        MODEL_PATH,
        VALIDATOR_PATH
    ];
    foreach ($paths as $path) {
        // Turn camel case to kebab case
        $class = camelToKebabCase($class);

        $file = $path . DS . str_replace('\\', '/', $class) . '.php';
        if (file_exists($file)) {
            require_once $file;
            break;
        }
    }
});

$paths = [
    FE_UTILITY_PATH,
    BE_UTILITY_PATH,
    CONTROLLER_PATH,
    FUNCTION_COMPONENT_PATH
];
foreach ($paths as $path) {
    foreach (glob($path . '*.php') as $fileName) {
        require_once $fileName;
    }
}
