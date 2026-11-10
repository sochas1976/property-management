<?php
namespace App\Core;

class Router {
    /** @var array */
    private static $routes = [];
    /** @var string|null */
    private static $basePath = null;

    /**
     * @param string $path
     * @param array|callable $handler
     * @param array $middlewares
     */
    public static function get(string $path, $handler, array $middlewares = []): void {
        self::addRoute('GET', $path, $handler, $middlewares);
    }

    /**
     * @param string $path
     * @param array|callable $handler
     * @param array $middlewares
     */
    public static function post(string $path, $handler, array $middlewares = []): void {
        self::addRoute('POST', $path, $handler, $middlewares);
    }

    /**
     * @param string $method
     * @param string $path
     * @param array|callable $handler
     * @param array $middlewares
     */
    private static function addRoute(string $method, string $path, $handler, array $middlewares): void {
        self::$routes[] = [
            'method'      => $method,
            'path'        => '/' . trim($path, '/'),
            'handler'     => $handler,
            'middlewares' => $middlewares
        ];
    }

    public static function getBasePath(): string {
        if (self::$basePath === null) {
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $dir = dirname($scriptName);
            self::$basePath = ($dir === '/' || $dir === '\\') ? '' : rtrim(str_replace('\\', '/', $dir), '/');
        }
        return self::$basePath;
    }

    public static function url(string $path = ''): string {
        $base = self::getBasePath();
        $cleanPath = '/' . ltrim($path, '/');
        return ($cleanPath === '/') ? ($base ?: '/') : ($base . $cleanPath);
    }

    public static function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Strip basePath from URI
        $base = self::getBasePath();
        if (!empty($base) && (strpos($uri, $base) === 0)) {
            $uri = substr($uri, strlen($base));
        }

        $uri = '/' . trim($uri, '/');

        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            // Convert route pattern with {param} to regex
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                // Extract named params
                $params = array_filter($matches, function($k) { return !is_int($k); }, ARRAY_FILTER_USE_KEY);

                // Run Middlewares
                foreach ($route['middlewares'] as $middleware) {
                    if (is_callable($middleware)) {
                        call_user_func($middleware);
                    } elseif ($middleware === 'auth') {
                        Auth::requireLogin();
                    } elseif (strpos($middleware, 'role:') === 0) {
                        $roles = explode(',', substr($middleware, 5));
                        Auth::requireRole($roles);
                    }
                }

                // Dispatch Handler
                $handler = $route['handler'];

                if (is_array($handler) && count($handler) === 2 && is_string($handler[0])) {
                    [$class, $action] = $handler;
                    if (class_exists($class)) {
                        $controller = new $class();
                        if (method_exists($controller, $action)) {
                            call_user_func_array([$controller, $action], $params);
                            return;
                        }
                    }
                }

                if (is_callable($handler)) {
                    call_user_func_array($handler, $params);
                    return;
                }
            }
        }

        // 404 Not Found
        http_response_code(404);
        require VIEW_PATH . '/layouts/404.php';
    }
}
