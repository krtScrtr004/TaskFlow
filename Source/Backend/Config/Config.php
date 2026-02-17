<?php

define('STRICT_TYPES', 1);
define('DS', '/');

date_default_timezone_set('Asia/Manila');

require_once dirname(__DIR__, 1) . DS . 'Data' . DS . 'Path.php';
require_once VENDOR_PATH . 'autoload.php';

require_once __DIR__ . DS . 'Env.php';

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

/**
 * Dynamically load and define validation constraints constants from JSON file
 */
// TODO: Uncomment these lines on deployment and remove the manual define statements below
//  $validationConstraints = decodeData(DATA_PATH . 'validation-constraints.json');
// foreach ($validationConstraints as $key => $value) {
//     define($key, $value);
// }

// ============================================================================= //
define('BUDGET_MIN', 0.0);
define('BUDGET_MAX', 999999999999);

define('CONTACT_NUMBER_MIN', 11);
define('CONTACT_NUMBER_MAX', 20);

define('CONTINGENCY_RATE_MIN', 0.0);
define('CONTINGENCY_RATE_MAX', 100.0);

define('DEFAULT_RATE_MIN', 0.0);
define('DEFAULT_RATE_MAX', 999999999);

define('FULL_NAME_MIN', 3);
define('FULL_NAME_MAX', 255);

define('LONG_TEXT_MIN', 5);
define('LONG_TEXT_MAX', 1000);

define('NAME_MIN', 1);
define('NAME_MAX', 50);

define('PASSWORD_MIN', 8);
define('PASSWORD_MAX', 255);

define('RESOURCE_QUANTITY_MIN', 1);
define('RESOURCE_QUANTITY_MAX', 1000000);

define('UNIT_NAME_MIN', 1);
define('UNIT_NAME_MAX', 20);

define('URI_MIN', 3);
define('URI_MAX', 255);

define('WORKER_COUNT_MIN', 1);
define('WORKER_COUNT_MAX', 1000);

define('WORKER_HOURS_MIN', 0.5);
define('WORKER_HOURS_MAX', 1000);

define('YEAR_MIN', 1900);
define('YEAR_CURRENT', 2025);
define('YEAR_MAX', 2100);
// ============================================================================= //

/**
 * Error Handling Config
 */
ini_set('error_reporting', E_ALL);          // Report all errors
ini_set('display_errors', 0);               // Do not display errors on browser
set_error_handler([\App\Core\Logger::class, 'logError']);
set_exception_handler([\App\Core\Logger::class, 'logException']);


