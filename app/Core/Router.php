<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method: string, pattern: string, regex: string, handler: callable|array}> */
    private array $routes = [];

    public function get(string $pattern, callable|array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable|array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function put(string $pattern, callable|array $handler): void
    {
        $this->add('PUT', $pattern, $handler);
    }

    public function delete(string $pattern, callable|array $handler): void
    {
        $this->add('DELETE', $pattern, $handler);
    }

    public function add(string $method, string $pattern, callable|array $handler): void
    {
        $pattern = '/' . trim($pattern, '/');
        if ($pattern === '/') {
            $regex = '#^/$#';
        } else {
            $quoted = preg_quote($pattern, '#');
            $regex = '#^' . preg_replace('/\\\\\{(\w+)\\\\\}/', '(?P<$1>[^/]+)', $quoted) . '$#';
        }
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'regex' => $regex,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $allowed = [];
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path(), $matches)) {
                continue;
            }
            if ($route['method'] !== $method) {
                $allowed[] = $route['method'];
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }

            $handler = $route['handler'];
            if (is_array($handler) && isset($handler[0]) && is_string($handler[0])) {
                $controller = new $handler[0]();
                $handler = [$controller, $handler[1]];
            }
            if (!is_callable($handler)) {
                throw new \RuntimeException('Ruta sin controlador válido.');
            }

            return $handler($request, ...array_values($params));
        }

        if ($allowed !== []) {
            throw new HttpException(405);
        }
        throw new HttpException(404);
    }
}
