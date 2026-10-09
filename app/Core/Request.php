<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Representación de la petición HTTP entrante.
 */
final class Request
{
    private string $method;

    private string $path;

    /** @var array<string,mixed> */
    private array $query;

    /** @var array<string,mixed> */
    private array $body;

    /** @var array<string,mixed> */
    private array $server;

    /** @var array<string,mixed> */
    private array $files;

    /** @var array<string,string> */
    private array $headers;

    /** @var array<string,string> */
    private array $routeParams = [];

    private ?array $jsonCache = null;

    /**
     * @param array<string,mixed> $query
     * @param array<string,mixed> $body
     * @param array<string,mixed> $server
     * @param array<string,mixed> $files
     */
    public function __construct(
        string $method,
        string $path,
        array $query = [],
        array $body = [],
        array $server = [],
        array $files = []
    ) {
        $this->method = strtoupper($method);
        $this->path = '/' . trim($path, '/');
        if ($this->path !== '/') {
            $this->path = rtrim($this->path, '/');
        }
        $this->query = $query;
        $this->body = $body;
        $this->server = $server;
        $this->files = $files;
        foreach ($server as $k => $v) {
            if (str_starts_with((string) $k, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr((string) $k, 5)));
                $this->headers[$name] = (string) $v;
            }
        }
        if (isset($server['CONTENT_TYPE'])) {
            $this->headers['content-type'] = (string) $server['CONTENT_TYPE'];
        }
    }

    public static function capture(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        // Soporte de método sobrescrito en formularios HTML.
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }
        return new self($method, $path, $_GET, $_POST, $_SERVER, $_FILES);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function fullUrl(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host = $this->host();
        return $scheme . '://' . $host . $this->path . ($this->queryString() !== '' ? '?' . $this->queryString() : '');
    }

    public function queryString(): string
    {
        return http_build_query($this->query);
    }

    public function host(): string
    {
        $host = $this->headers['host'] ?? ($this->server['HTTP_HOST'] ?? 'localhost');
        // Eliminar puerto peligroso en cabeceras Host manipuladas.
        $host = preg_replace('/[^a-zA-Z0-9\.\-:]/', '', (string) $host);
        return (string) $host;
    }

    public function isSecure(): bool
    {
        if (($this->server['HTTPS'] ?? '') !== '' && ($this->server['HTTPS'] ?? 'off') !== 'off') {
            return true;
        }
        return ($this->headers['x-forwarded-proto'] ?? '') === 'https';
    }

    public function ip(): string
    {
        $ip = $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
        return filter_var($ip, FILTER_VALIDATE_IP) ? (string) $ip : '0.0.0.0';
    }

    /**
     * IP enmascarada para auditoría/privacidad (trunca último octeto).
     */
    public function ipMasked(): string
    {
        $ip = $this->ip();
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = 'x';
            return implode('.', $parts);
        }
        $hextets = explode(':', $ip);
        return implode(':', array_slice($hextets, 0, 3)) . '::';
    }

    public function userAgent(): string
    {
        $ua = $this->headers['user-agent'] ?? ($this->server['HTTP_USER_AGENT'] ?? '');
        return mb_substr((string) $ua, 0, 300);
    }

    /** @return array<string,string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function wantsJson(): bool
    {
        $accept = $this->header('accept', '');
        $xhr = $this->header('x-requested-with', '');
        return str_contains((string) $accept, 'application/json')
            || str_contains((string) $xhr, 'XMLHttpRequest')
            || str_starts_with($this->path, '/api/');
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /** @return array<string,mixed> */
    public function queryAll(): array
    {
        return $this->query;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function str(string $key, string $default = ''): string
    {
        $v = $this->input($key, $default);
        return is_scalar($v) ? trim((string) $v) : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $v = $this->input($key);
        return is_numeric($v) ? (int) $v : $default;
    }

    /** @return array<mixed> */
    public function array(string $key, array $default = []): array
    {
        $v = $this->input($key);
        return is_array($v) ? $v : $default;
    }

    /** @return array<string,mixed> */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    /** @return array<string,mixed> */
    public function body(): array
    {
        return $this->body;
    }

    /** @return array<string,mixed> */
    public function file(string $key): array
    {
        $f = $this->files[$key] ?? null;
        if (!is_array($f) || !isset($f['error'])) {
            return [];
        }
        return $f;
    }

    public function json(string $key, mixed $default = null): mixed
    {
        if ($this->jsonCache === null) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = json_decode($raw, true);
            $this->jsonCache = is_array($decoded) ? $decoded : [];
        }
        return $this->jsonCache[$key] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization', '');
        if ($auth !== null && preg_match('/^Bearer\s+(\S+)$/i', $auth, $m)) {
            return $m[1];
        }
        return null;
    }

    /** @param array<string,string> $params */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function route(string $key, ?string $default = null): ?string
    {
        return $this->routeParams[$key] ?? $default;
    }

    /** @return array<string,string> */
    public function routeParams(): array
    {
        return $this->routeParams;
    }

    public function session(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }
}
