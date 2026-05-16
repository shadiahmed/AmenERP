<?php

declare(strict_types=1);

/**
 * AmenERP Router Class
 * 
 * A minimal, zero-dependency router that handles URL routing for the modular ERP system.
 * Routes are matched against registered patterns and dispatched to module controllers.
 * 
 * @package AmenERP\Core
 * @author Bob
 * @version 1.0.0
 */
class Router
{
    /**
     * Registered routes
     * Format: ['method' => ['pattern' => 'controller_path']]
     * 
     * @var array<string, array<string, string>>
     */
    private array $routes = [];

    /**
     * Route parameters extracted from URL
     * 
     * @var array<string, string>
     */
    private array $params = [];

    /**
     * Register a GET route
     * 
     * @param string $pattern URL pattern (e.g., '/users/{id}')
     * @param string $controller Controller file path relative to modules directory
     * @return self
     */
    public function get(string $pattern, string $controller): self
    {
        $this->routes['GET'][$pattern] = $controller;
        return $this;
    }

    /**
     * Register a POST route
     * 
     * @param string $pattern URL pattern
     * @param string $controller Controller file path relative to modules directory
     * @return self
     */
    public function post(string $pattern, string $controller): self
    {
        $this->routes['POST'][$pattern] = $controller;
        return $this;
    }

    /**
     * Register a PUT route
     * 
     * @param string $pattern URL pattern
     * @param string $controller Controller file path relative to modules directory
     * @return self
     */
    public function put(string $pattern, string $controller): self
    {
        $this->routes['PUT'][$pattern] = $controller;
        return $this;
    }

    /**
     * Register a DELETE route
     * 
     * @param string $pattern URL pattern
     * @param string $controller Controller file path relative to modules directory
     * @return self
     */
    public function delete(string $pattern, string $controller): self
    {
        $this->routes['DELETE'][$pattern] = $controller;
        return $this;
    }

    /**
     * Dispatch the current request to the appropriate controller
     * 
     * @return void
     */
    public function dispatch(): void
    {
        $requestUri = $this->getRequestUri();
        $requestMethod = $this->getRequestMethod();

        // Check if routes exist for this method
        if (!isset($this->routes[$requestMethod])) {
            $this->handleNotFound();
            return;
        }

        // Try to match the request URI against registered routes
        foreach ($this->routes[$requestMethod] as $pattern => $controller) {
            if ($this->matchRoute($pattern, $requestUri)) {
                $this->loadController($controller);
                return;
            }
        }

        // No route matched
        $this->handleNotFound();
    }

    /**
     * Get the current request URI (cleaned)
     * 
     * @return string
     */
    private function getRequestUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Remove base path if app is in subdirectory (case-insensitive)
        $basePath = parse_url(BASE_URL, PHP_URL_PATH) ?? '';
        if ($basePath && stripos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }

        // Ensure leading slash
        $uri = '/' . ltrim($uri, '/');

        return $uri;
    }

    /**
     * Get the current request method
     * 
     * @return string
     */
    private function getRequestMethod(): string
    {
        // Check for method override (for PUT/DELETE via POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method'])) {
            return strtoupper($_POST['_method']);
        }

        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Match a route pattern against the request URI
     * 
     * @param string $pattern Route pattern (e.g., '/users/{id}')
     * @param string $uri Request URI
     * @return bool
     */
    private function matchRoute(string $pattern, string $uri): bool
    {
        // Reset params
        $this->params = [];

        // Convert pattern to regex
        // Replace {param} with named capture groups
        $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        // Try to match
        if (preg_match($regex, $uri, $matches)) {
            // Extract named parameters
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $this->params[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Load and execute a controller file
     * 
     * @param string $controller Controller file path relative to modules directory
     * @return void
     */
    private function loadController(string $controller): void
    {
        $controllerPath = MODULES_PATH . '/' . $controller;

        if (!file_exists($controllerPath)) {
            $this->handleError("Controller not found: {$controller}");
            return;
        }

        // Make params available to controller
        $params = $this->params;

        // Include the controller file
        require $controllerPath;
    }

    /**
     * Get route parameters
     * 
     * @param string|null $key Specific parameter key or null for all
     * @return mixed
     */
    public function getParams(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->params;
        }

        return $this->params[$key] ?? null;
    }

    /**
     * Handle 404 Not Found
     * 
     * @return void
     */
    private function handleNotFound(): void
    {
        http_response_code(404);
        
        if (ENVIRONMENT === 'development') {
            echo '<h1>404 - Not Found</h1>';
            echo '<p>The requested URL was not found on this server.</p>';
            echo '<p><strong>Request URI:</strong> ' . htmlspecialchars($this->getRequestUri()) . '</p>';
            echo '<p><strong>Request Method:</strong> ' . htmlspecialchars($this->getRequestMethod()) . '</p>';
        } else {
            echo '<h1>404 - Not Found</h1>';
        }
        
        exit;
    }

    /**
     * Handle general errors
     * 
     * @param string $message Error message
     * @return void
     */
    private function handleError(string $message): void
    {
        http_response_code(500);
        
        if (ENVIRONMENT === 'development') {
            echo '<h1>Router Error</h1>';
            echo '<p>' . htmlspecialchars($message) . '</p>';
        } else {
            echo '<h1>Internal Server Error</h1>';
        }
        
        exit;
    }
}

// Made with Bob