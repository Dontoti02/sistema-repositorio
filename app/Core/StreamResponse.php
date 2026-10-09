<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Respuesta que transmite un archivo en streaming (con soporte de rangos).
 * Evita cargar archivos grandes en memoria.
 */
final class StreamResponse extends Response
{
    private string $path;

    private int $offset;

    private int $length;

    public function __construct(string $path, Response $prototype, int $offset = 0, ?int $length = null)
    {
        parent::__construct('', $prototype->status(), $prototype->headers());
        $this->path = $path;
        $this->offset = $offset;
        $this->length = $length ?? (int) filesize($path);
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status());
            foreach ($this->headers() as $name => $value) {
                header($name . ': ' . $value, true);
            }
        }
        $handle = fopen($this->path, 'rb');
        if ($handle === false) {
            http_response_code(500);
            echo 'No se pudo leer el archivo.';
            return;
        }
        if ($this->offset > 0) {
            fseek($handle, $this->offset);
        }
        $remaining = $this->length;
        while ($remaining > 0 && !feof($handle)) {
            $chunk = fread($handle, min(8192, $remaining));
            if ($chunk === false) {
                break;
            }
            echo $chunk;
            $remaining -= strlen($chunk);
            flush();
        }
        fclose($handle);
    }
}
