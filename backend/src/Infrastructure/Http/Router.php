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

    private function add(string $method, string $pattern, callable|array $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => trim($pattern, '/'),
            'handler' => $handler,
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
