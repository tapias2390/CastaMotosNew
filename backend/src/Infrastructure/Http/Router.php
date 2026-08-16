<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Exceptions\NotFoundException;

/**
 * Router HTTP simple. Soporta parámetros de ruta tipo {id} y despacha
 * a [ClaseControlador, 'metodo'] o a un closure.
 */
final class Router
{
    private array $routes = [];

    /**
     * @param Middleware[] $middleware Se ejecutan en orden antes del handler.
     */
    public function get(string $pattern, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    public function put(string $pattern, callable|array $handler, array $middleware = []): void
    {
        $this->add('PUT', $pattern, $handler, $middleware);
    }

    public function delete(string $pattern, callable|array $handler, array $middleware = []): void
    {
        $this->add('DELETE', $pattern, $handler, $middleware);
    }

    private function add(string $method, string $pattern, callable|array $handler, array $middleware): void
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => trim($pattern, '/'),
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path = trim($request->path(), '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['pattern'], $path);
            if ($params === null) {
                continue;
            }

            foreach ($route['middleware'] as $middleware) {
                $request = $middleware->handle($request);
            }

            $handler = $route['handler'];

            if (is_array($handler)) {
                [$class, $methodName] = $handler;
                $controller = new $class();
                $controller->$methodName($request, ...array_values($params));
                return;
            }

            $handler($request, ...array_values($params));
            return;
        }

        throw new NotFoundException('Recurso no encontrado.');
    }

    /**
     * Compara un patrón tipo "products/{id}" contra la ruta real y devuelve
     * el arreglo de parámetros capturados, o null si no coincide.
     */
    private function match(string $pattern, string $path): ?array
    {
        $patternSegments = $pattern === '' ? [] : explode('/', $pattern);
        $pathSegments = $path === '' ? [] : explode('/', $path);

        if (count($patternSegments) !== count($pathSegments)) {
            return null;
        }

        $params = [];

        foreach ($patternSegments as $index => $segment) {
            $pathSegment = $pathSegments[$index];

            if (str_starts_with($segment, '{') && str_ends_with($segment, '}')) {
                $params[trim($segment, '{}')] = $pathSegment;
                continue;
            }

            if ($segment !== $pathSegment) {
                return null;
            }
        }

        return $params;
    }
}
