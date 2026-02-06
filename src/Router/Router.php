<?php

namespace App\Router;

use App\Utils\Response;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    /**
     * Add a route to the router
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $pattern URL pattern (supports regex groups)
     * @param callable|array $handler Callback or [Controller, 'method']
     * @param array $middlewares Array of middleware callables
     */
    public function addRoute(string $method, string $pattern, callable|array $handler, array $middlewares = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    /**
     * Add a GET route
     */
    public function get(string $pattern, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $pattern, $handler, $middlewares);
    }

    /**
     * Add a POST route
     */
    public function post(string $pattern, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $pattern, $handler, $middlewares);
    }

    /**
     * Add a PUT route
     */
    public function put(string $pattern, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $pattern, $handler, $middlewares);
    }

    /**
     * Add a DELETE route
     */
    public function delete(string $pattern, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $pattern, $handler, $middlewares);
    }

    /**
     * Add global middleware to all routes
     */
    public function addGlobalMiddleware(callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Dispatch the request to the appropriate handler
     * 
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @param array|null $requestBody Request body data
     * @param array $queryParams Query parameters
     */
    public function dispatch(string $method, string $uri, ?array $requestBody = null, array $queryParams = []): void
    {
        // Run global middlewares first
        foreach ($this->middlewares as $middleware) {
            $result = $middleware();
            if ($result === false) {
                return; // Middleware stopped execution
            }
        }

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            $pattern = '#^' . $route['pattern'] . '$#';
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove full match

                // Run route-specific middlewares
                foreach ($route['middlewares'] as $middleware) {
                    $result = is_callable($middleware) ? $middleware() : call_user_func($middleware);
                    if ($result === false || $result === null) {
                        return; // Middleware stopped execution
                    }
                }

                // Execute handler
                $handler = $route['handler'];
                
                if (is_array($handler)) {
                    [$controller, $method] = $handler;
                    
                    if (in_array($route['method'], ['POST', 'PUT', 'PATCH'])) {
                        $params = array_merge([$requestBody ?? []], $matches);
                    } else {
                        // For GET and DELETE, only pass path parameters
                        $params = $matches;
                    }
                    
                    call_user_func_array([$controller, $method], $params);
                } else {
                    $handlerParams = in_array($route['method'], ['POST', 'PUT', 'PATCH']) 
                        ? array_merge([$requestBody ?? []], $matches)
                        : array_merge([$queryParams], $matches);
                    call_user_func_array($handler, $handlerParams);
                }
                
                return;
            }
        }

        // No route matched
        Response::notFound('Endpoint not found');
    }

    /**
     * Group routes with common prefix and middlewares
     * 
     * @param string $prefix URL prefix
     * @param callable $callback Callback to define routes
     * @param array $middlewares Middlewares for all routes in group
     */
    public function group(string $prefix, callable $callback, array $middlewares = []): void
    {
        $originalRoutes = $this->routes;
        $this->routes = [];
        
        // Execute callback to collect routes
        $callback($this);
        
        // Add prefix and middlewares to collected routes
        $groupRoutes = $this->routes;
        foreach ($groupRoutes as &$route) {
            $route['pattern'] = $prefix . '/' . ltrim($route['pattern'], '/');
            $route['middlewares'] = array_merge($middlewares, $route['middlewares']);
        }
        
        // Merge back with original routes
        $this->routes = array_merge($originalRoutes, $groupRoutes);
    }
}
