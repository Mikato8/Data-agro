<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class HealthController extends BaseController
{
    public function index(Request $request): Response
    {
        $db = false;
        $error = null;
        try {
            $db = Database::ping();
        } catch (\Throwable $e) {
            $error = 'No disponible';
        }

        return Response::json([
            'status' => $db ? 'ok' : 'degradado',
            'app' => 'Diagnóstico Veterinario',
            'php' => PHP_VERSION,
            'database' => [
                'ok' => $db,
                'driver' => Database::driverName(),
                'error' => $error,
            ],
            'uploads_writable' => is_writable((string) config('app.uploads.path')),
            'time' => date('c'),
            'timezone' => date_default_timezone_get(),
        ], $db ? 200 : 503)->withHeader('Cache-Control', 'no-store');
    }
}
