<?php

/* 
* Router class for handling HTTP requests and routing them to the appropriate actions.
* This class supports GET, POST, PUT, and DELETE methods and allows for dynamic route parameters.
* 
* Usage:
* $router = Router::getRouter();
* $router->get('/users/{id}', [UserController::class, 'show']);
* $router->post('/users', [UserController::class, 'create']);
* $router->dispatch();
* 
* Author: Madj Soubh
* From: https://medium.com/@majdsoubh/implementing-php-routing-with-dynamic-parameters-18940bd80795
*/

class Router
{
    private static array $routes = [];

    private static $router;

    private function __construct() {}

    /**
     * Get the singleton instance of the Router.
     * @return Router The singleton instance of the Router.
     */
    public static function getRouter(): Router
    {
        if (!isset(self::$router))
            self::$router = new Router();

        return self::$router;
    }

    /**
     * Execute the action for a matched route.
     * 
     * @param array|callable $action The action to execute.
     * @param array $routeParams The parameters extracted from the route.
     * @return mixed The result of the action executed.
     */
    public function resolveAction($action, $routeParams)
    {
        if (is_callable($action)) {
            return call_user_func($action, $routeParams);
        } else {
            return call_user_func([$action[0], $action[1]], $routeParams);
        }
    }

    /**
     * Register a route with a specified HTTP method and action.
     * 
     * @param string $route The route path.
     * @param string $method The HTTP method (GET, POST, PUT, DELETE).
     * @param array|callable $action The action to execute for the route.
     */
    public function register(string $route, string $method, array|callable $action)
    {
        // Trim slashes
        $route = trim($route, '/');

        // Assign action to the passed route
        self::$routes[$method][$route] = $action;
    }

    /**
     * Resolve the current request to the corRespond::responding route action.
     */
    public function dispatch()
    {
        // Get the requested route.
        $requestedRoute = trim($_SERVER['REQUEST_URI'], '/') ?? '/';

        $routes = self::$routes[$_SERVER['REQUEST_METHOD']];

        foreach ($routes as $route => $action) {
            // Transform route to regex pattern.
            $routeRegex = preg_replace_callback('/{\w+(:([^}]+))?}/', function ($matches) {
                return isset($matches[1]) ? '(' . $matches[2] . ')' : '([a-zA-Z0-9_-]+)';
            }, $route);

            // Add the start and end delimiters.
            $routeRegex = '~^' . $routeRegex . '$~';

            // Check if the requested route matches the current route pattern.
            if (preg_match($routeRegex, $requestedRoute, $matches)) {
                // Get all user requested path params values after removing the first matches.
                array_shift($matches);
                $routeParamsValues = $matches;

                // Find all route params names from route and save in $routeParamsNames
                $routeParamsNames = [];
                if (preg_match_all('/{(\w+)(:[^}]+)?}/', $route, $matches)) {
                    $routeParamsNames = $matches[1];
                }

                // Combine between route parameter names and user provided parameter values.
                $routeParams = array_combine($routeParamsNames, $routeParamsValues);

                return  $this->resolveAction($action, $routeParams);
            }
        }

        echo '404 not found';
    }
}
