<?php

namespace Core;


/**
 * Simple Route class
 */
class InstallRoute
{
    private $routes = [];
    private $patterns = [
        'num'   => '[0-9]+',
        'alnum' => '[A-Za-z0-9]+',
        'alpha' => '[A-Za-z]+',
        'any'   => '[^/]+',
        'slug'  => '[a-z0-9]+(?:-[a-z0-9]+)*',
        'date'  => '[0-9]{4}-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12][0-9]|3[01])',
        'year' => '[0-9]{4}',
        'month' => '(?:0[1-9]|1[0-2])',
        'day' => '(?:0[1-9]|[12][0-9]|3[01])',
        'bool' => '(?:true|false|1|0|yes|no)',
        'hexcolor' => '#?(?:[0-9a-fA-F]{3}){1,2}',
        'all' => '.*',
        'path' => '.*'
    ];
    /**
     * @var Installer
     */
    private $installer;


    function __construct(Installer $installer)
    {
        $this->installer = $installer;
    }

    public function getRoutesStrArray()
    {
        return array_column($this->routes, 'path');
    }

    public function addPattern(string $name, string $pattern) {
        $this->patterns[$name] = $pattern;
    }

    public function get(string $path, string $action, array $paramPatterns = []): self {
        $route = [
            'method'     => 'GET',
            'path'       => $this->normalizePath($path),
            'action'     => $action,
            'params'     => $this->compileParamPatterns($paramPatterns),
        ];
        $this->routes[] = $route;
        return $this;
    }

    public function post(string $path, string $action, array $paramPatterns = []): self {
        $route = [
            'method'     => 'POST',
            'path'       => $this->normalizePath($path),
            'action'     => $action,
            'params'     => $this->compileParamPatterns($paramPatterns),
        ];
        $this->routes[] = $route;
        return $this;
    }

    public function reroute(string $from, string $to) {
        $this->routes[] = [
            'method' => 'REROUTE',
            'path'   => $this->normalizePath($from),
            'target' => $this->normalizePath($to)
        ];
    }


    private function normalizePath(string $path): string {
        $segments = array_filter(explode('/', $path), fn($segment) => trim($segment) !== '');
        return '/' . implode('/', $segments);
    }

    private function compileParamPatterns(array $paramPatterns): array {
        $compiled = [];
        foreach ($paramPatterns as $param => $patternKey) {
            $compiled[$param] = $this->patterns[$patternKey] ?? $patternKey;
        }
        return $compiled;
    }

    public function dispatch() {
        $requestUri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestPath   = $this->normalizePath($requestUri);

        foreach ($this->routes as $route) {
            if ($route['method'] === 'REROUTE' && $route['path'] === $requestPath) {
                header("Location: " . $route['target']);
                exit;
            }
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            // Check for path parameters
            if (strpos($route['path'], ':path') !== false) {
                $patternResult = $this->matchPathParameter($route, $requestPath);
                if ($patternResult) {
                    list($matches, $params) = $patternResult;
                    $this->executeRoute($route, $params);
                    return;
                }
            } else {
                $pattern = $this->buildRoutePattern($route['path'], $route['params']);
                if (preg_match($pattern, $requestPath, $matches)) {
                    $params = [];
                    foreach ($matches as $key => $value) {
                        if (is_string($key)) {
                            $params[$key] = ($route['params'][$key] ?? '[^/]+') === $this->patterns['any'] ? urldecode($value) : $value;
                        }
                    }

                    $this->executeRoute($route, $params);
                    return;
                }
            }
        }

        header("HTTP/1.0 404 Not Found");
        echo "404 Not Found";
    }

    /**
     * Match a route with a path parameter
     *
     * @param array $route Route definition
     * @param string $requestPath Requested path
     * @return array|null Match result or null if no match
     */
    private function matchPathParameter(array $route, string $requestPath)
    {
        // Extract the part before :path
        $parts = explode('/:path', $route['path']);
        $prefix = $parts[0];

        // Check if the request path starts with the prefix
        if (strpos($requestPath, $prefix) !== 0) {
            return null;
        }

        // Extract the path parameter value
        $pathValue = substr($requestPath, strlen($prefix) + 1);

        // If there's more to the route after :path, check that too
        if (isset($parts[1]) && $parts[1] !== '') {
            // Not handling complex patterns after :path for simplicity
            return null;
        }

        $matches = ['path' => $pathValue];
        $params = ['path' => $pathValue];

        return [$matches, $params];
    }

    /**
     * Execute a matched route
     *
     * @param array $route Route definition
     * @param array $params Route parameters
     */
    private function executeRoute(array $route, array $params)
    {
        $returnValue = call_user_func_array([$this->installer, $route['action']], $params);
    }

    private function buildRoutePattern(string $path, array $paramPatterns): string {
        $segments     = explode('/', ltrim($path, '/'));
        $regexSegments = [];
        foreach ($segments as $segment) {
            if (substr($segment, 0, 1) === ':') { // Überprüfung, ob der Segment mit ':' beginnt
                $paramName = substr($segment, 1);
                $pattern   = isset($paramPatterns[$paramName]) ? $paramPatterns[$paramName] : '[^/]+';
                $regexSegments[] = "(?P<$paramName>$pattern)";
            } else {
                $regexSegments[] = preg_quote($segment, '/');
            }
        }

        return '#^/' . implode('/', $regexSegments) . '$#';
    }

    public function getRoutes(): array {
        return $this->routes;
    }

    public function addRouteDefinition(array $route) {
        $this->routes[] = $route;
    }
}