<?php

declare(strict_types=1);

namespace App\Core;

use Exception;

/**
 * Excepción con estado HTTP controlado.
 */
class HttpException extends Exception
{
    private array $errors;

    /**
     * @param array<string,mixed> $errors
     */
    public function __construct(int $status, string $message = '', array $errors = [])
    {
        parent::__construct($message !== '' ? $message : self::defaultMessage($status), $status);
        $this->errors = $errors;
    }

    public function status(): int
    {
        return $this->getCode();
    }

    /** @return array<string,mixed> */
    public function errors(): array
    {
        return $this->errors;
    }

    private static function defaultMessage(int $status): string
    {
        return match ($status) {
            400 => 'Solicitud incorrecta.',
            401 => 'Debe iniciar sesión para continuar.',
            403 => 'No tiene permisos para realizar esta acción.',
            404 => 'La página solicitada no existe.',
            405 => 'Método HTTP no permitido.',
            409 => 'La operación entra en conflicto con el estado actual.',
            419 => 'La sesión expiró o el formulario no es válido. Intente de nuevo.',
            422 => 'Los datos enviados no son válidos.',
            429 => 'Demasiadas solicitudes. Espere unos minutos.',
            _ => 'Ocurrió un error.',
        };
    }
}
