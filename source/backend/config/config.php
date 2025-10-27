<?php

define('STRICT_TYPES', 1);
define('DS', '/');

require_once dirname(__DIR__, 1) . DS . 'data' . DS . 'path.php';
require_once VENDOR_PATH . 'autoload.php';
require_once DATA_PATH . 'data-length.php';

// require_once __DIR__ . DS . 'env.php';

spl_autoload_register(function ($class) {
    // Map namespace folders to actual paths
    static $pathMap = [
    'abstract' => ABSTRACT_PATH,
    'auth' => AUTH_PATH,
    'config' => CONFIG_PATH,
    'container' => CONTAINER_PATH,
    'controller' => CONTROLLER_PATH,
    'core' => CORE_PATH,
    'dependent' => DEPENDENT_PATH,
    'dump' => DUMP_PATH,
    'endpoint' => ENDPOINT_PATH,
    'entity' => ENTITY_PATH,
    'enumeration' => ENUM_PATH,
    'exception' => EXCEPTION_PATH,
    'interface' => INTERFACE_PATH,
    'router' => ROUTER_PATH,
    'middleware' => MIDDLEWARE_PATH,
    'model' => MODEL_PATH,
    'validator' => VALIDATOR_PATH,
    ];

    $prefix = 'App\\';

    // Check if the class uses our namespace
    if (strncmp($prefix, $class, strlen($prefix)) === 0) {
        // Fully qualified namespace (e.g., App\Controller\AuthController)
        // Remove the App\ prefix to get the relative class path
        $relativeClass = substr($class, strlen($prefix));

        // Split into namespace parts
        $parts = explode('\\', $relativeClass);

        // The last part is the class name, convert it to kebab-case
        $className = camelToKebabCase(array_pop($parts));

        // The first part is the folder (Interface, Controller, Model, etc.)
        $folder = strtolower($parts[0] ?? '');

        // Get the base path for this namespace folder
        if (isset($pathMap[$folder])) {
            $file = $pathMap[$folder] . $className . '.php';

            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    } else {
        // Short class name from routes (e.g., AuthController, UserController)
        // Try to find it in all mapped paths
        $className = camelToKebabCase($class);

        foreach ($pathMap as $path) {
            $file = $path . $className . '.php';

            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// Only include utility files (non-namespaced helper functions)
$paths = [
    FE_UTILITY_PATH,
    BE_UTILITY_PATH,
    FUNCTION_COMPONENT_PATH
];
foreach ($paths as $path) {
    foreach (glob($path . '*.php') as $fileName) {
        require_once $fileName;
    }
}
App\Core\Session::restore();

