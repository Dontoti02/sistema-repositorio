<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Respuesta HTTP. (No es `final`: StreamResponse la extiende para descargas.)
 */
class Response
{
    private int $status;

    /** @var array<string,string> */
    private array $headers = [];

    private string $content;

    /**
     * @param array<string,string> $headers
     */
    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers;
    }

    public static function html(string $content, int $status = 200): self
    {
        return new self($content, $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = '{"error":"Error de codificación JSON"}';
            $status = 500;
        }
        return new self($json, $status, [
            'Content-Type' => 'application/json; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public static function xml(string $content, int $status = 200): self
    {
        return new self($content, $status, ['Content-Type' => 'text/xml; charset=utf-8']);
    }

    public static function redirect(string $to, int $status = 302): self
    {
        return new self('', $status, ['Location' => $to]);
    }

    public static function noContent(): self
    {
        return new self('', 204);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function content(): string
    {
        return $this->content;
    }

    /** @return array<string,string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function isRedirect(): bool
    {
        return isset($this->headers['Location']);
    }

    /**
     * Envía la respuesta al cliente.
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value, true);
            }
        }
        if ($this->status !== 204 && $this->status !== 304) {
            echo $this->content;
        }
    }
}
