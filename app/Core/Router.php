<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

/**
 * Router HTTP con grupos, middleware y parámetros tipados.
 *
 * Ejemplo:
 *   $r->get('/recursos/{id}', [ItemController::class, 'show']);
 *   $r->group('/admin', fn($r) => $r->get('/usuarios', ...), ['middleware' => ['auth']]);
 */
final class Router
{
    /** @var array<int,array{method:string,pattern:string,regex:string,handler:mixed,middleware:array<int,string>,name:?string}> */
    private array $routes = [];

    /** @var array<string,string> */
    private array $named = [];

    private string $groupPrefix = '';

    /** @var array<int,string> */
    private array $groupMiddleware = [];

    /**
     * @param array<int,string> $middleware
     */
    public function get(string $pattern, mixed $handler, array $middleware = [], ?string $name = null): void
    {
        $this->add('GET', $pattern, $handler, $middleware, $name);
    }

    public function post(string $pattern, mixed $handler, array $middleware = [], ?string $name = null): void
    {
        $this->add('POST', $pattern, $handler, $middleware, $name);
    }

    public function put(string $pattern, mixed $handler, array $middleware = [], ?string $name = null): void
    {
        $this->add('PUT', $pattern, $handler, $middleware, $name);
    }

    public function patch(string $pattern, mixed $handler, array $middleware = [], ?string $name = null): void
    {
        $this->add('PATCH', $pattern, $handler, $middleware, $name);
    }

    public function delete(string $pattern, mixed $handler, array $middleware = [], ?string $name = null): void
    {
        $this->add('DELETE', $pattern, $handler, $middleware, $name);
    }

    /**
     * Registra varias rutas de una lista.
     *
     * @param array<int,array{0:string,1:string,2:mixed,3?:array,4?:string}> $definitions
     */
    public function addMany(array $definitions): void
    {
        foreach ($definitions as $def) {
            $this->add(strtoupper($def[0]), $def[1], $def[2], $def[3] ?? [], $def[4] ?? null);
        }
    }

    /**
     * @param array<int,string> $middleware
     */
    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;
        $this->groupPrefix = $previousPrefix . '/' . trim($prefix, '/');
        $this->groupMiddleware = array_merge($previousMiddleware, $middleware);
        $callback($this);
        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    /**
     * @param array<int,string> $middleware
     */
    private function add(string $method, string $pattern, mixed $handler, array $middleware, ?string $name): void
    {
        $full = $this->groupPrefix . '/' . trim($pattern, '/');
        $full = '/' . trim($full, '/');
        $regex = $this->compile($full);
        $allMiddleware = array_merge($this->groupMiddleware, $middleware);
        $this->routes[] = [
            'method' => $method,
            'pattern' => $full,
            'regex' => $regex,
            'handler' => $handler,
            'middleware' => array_values(array_unique($allMiddleware)),
            'name' => $name,
        ];
        if ($name !== null) {
            $this->named[$name] = $full;
        }
    }

    /**
     * Convierte /recursos/{id} en regex con soporte {id:\d+}.
     */
    private function compile(string $pattern): string
    {
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/',
            static function (array $m): string {
                $sub = $m[2] ?? '[^/]+';
                return '(?P<' . $m[1] . '>' . $sub . ')';
            },
            $pattern
        );
        return '#^' . $regex . '$#u';
    }

    /**
     * Encuentra la ruta para método+path.
     *
     * @return array{route: array<string,mixed>, params: array<string,string>}|null
     */
    public function match(string $method, string $path): ?array
    {
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }
        $allowed = [];
        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                if ($route['method'] === $method) {
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    foreach ($params as $key => $value) {
                        $params[$key] = rawurldecode($value);
                    }
                    return ['route' => $route, 'params' => $params];
                }
                $allowed[] = $route['method'];
            }
        }
        if ($allowed !== []) {
            throw new HttpException(405, 'Método no permitido para esta ruta. Métodos válidos: ' . implode(', ', $allowed));
        }
        return null;
    }

    /**
     * URL para ruta con nombre.
     *
     * @param array<string,string|int> $params
     */
    public function to(string $name, array $params = []): string
    {
        $pattern = $this->named[$name] ?? null;
        if ($pattern === null) {
            throw new \RuntimeException("Ruta con nombre no registrada: {$name}");
        }
        $url = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function (array $m) use ($params): string {
            $key = $m[1];
            if (!isset($params[$key])) {
                throw new \RuntimeException("Falta el parámetro {$key} para la ruta {$name}");
            }
            return rawurlencode((string) $params[$key]);
        }, $pattern);
        return (string) $url;
    }

    /** @return array<int,array<string,mixed>> */
    public function routes(): array
    {
        return $this->routes;
    }
}
