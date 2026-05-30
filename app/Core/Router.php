<?php

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        foreach ($this->routes[$method] ?? [] as $route) {
            $params = $this->match($route['path'], $path);

            if ($params !== null) {
                [$class, $action] = $route['handler'];
                $controller = new $class();
                $controller->$action(...$params);
                return;
            }
        }

        http_response_code(404);
        echo View::render('layouts/error', [
            'title' => 'Page not found',
            'message' => 'That page does not exist yet.',
        ]);
    }

    private function add(string $method, string $path, array $handler): void
    {
        $this->routes[$method][] = [
            'path' => $path,
            'handler' => $handler,
        ];
    }

    private function match(string $routePath, string $requestPath): ?array
    {
        $pattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (!preg_match($pattern, $requestPath, $matches)) {
            return null;
        }

        array_shift($matches);

        return $matches;
    }
}

