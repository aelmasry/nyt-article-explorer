<?php

namespace App\Config;

class Router {
    private array $routes = [];
    private string $notFoundHandler = '';
    
    public function get(string $path, string $handler): self {
        $this->routes['GET'][$path] = $handler;
        return $this;
    }
    
    public function post(string $path, string $handler): self {
        $this->routes['POST'][$path] = $handler;
        return $this;
    }
    
    public function put(string $path, string $handler): self {
        $this->routes['PUT'][$path] = $handler;
        return $this;
    }
    
    public function delete(string $path, string $handler): self {
        $this->routes['DELETE'][$path] = $handler;
        return $this;
    }
    
    public function setNotFoundHandler(string $handler): self {
        $this->notFoundHandler = $handler;
        return $this;
    }
    
    public function resolve(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Apply middlewares as needed
        $this->applyGlobalMiddlewares();
        
        // Search for the matching route
        if (isset($this->routes[$method][$path])) {
            $handler = $this->routes[$method][$path];
            
            // Split the handler into class and method
            list($class, $method) = explode('@', $handler);
            $fullClassName = "App\\Controllers\\$class";
            
            if (class_exists($fullClassName)) {
                $controller = new $fullClassName();
                if (method_exists($controller, $method)) {
                    call_user_func([$controller, $method]);
                    return;
                }
            }
        }
        
        // If the route is not found
        if (!empty($this->notFoundHandler)) {
            list($class, $method) = explode('@', $this->notFoundHandler);
            $fullClassName = "App\\Controllers\\$class";
            
            if (class_exists($fullClassName)) {
                $controller = new $fullClassName();
                if (method_exists($controller, $method)) {
                    call_user_func([$controller, $method]);
                    return;
                }
            }
        }
        
        // If no route or handler is found
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
    }
    
    private function applyGlobalMiddlewares(): void {
        // Apply global middlewares here
        // Example: logging requests, checking rate limits, etc.
    }
}