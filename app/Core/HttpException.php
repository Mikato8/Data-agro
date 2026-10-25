<?php

declare(strict_types=1);

namespace App\Core;

final class HttpException extends \RuntimeException
{
    public function __construct(private int $statusCode, string $message = '')
    {
        parent::__construct($message !== '' ? $message : self::defaultMessage($statusCode));
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    private static function defaultMessage(int $status): string
    {
        return match ($status) {
            400 => 'La solicitud no es válida.',
            403 => 'No tienes permiso para realizar esta acción.',
            404 => 'No encontramos lo que buscabas.',
            405 => 'Método no permitido.',
            419 => 'Tu sesión expiró. Recarga la página e inténtalo de nuevo.',
            429 => 'Demasiadas solicitudes. Espera un momento.',
            default => 'Ocurrió un error inesperado.',
        };
    }
}
