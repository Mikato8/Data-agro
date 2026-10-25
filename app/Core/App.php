<?php

declare(strict_types=1);

namespace App\Core;

final class App
{
    public static function run(): void
    {
        $request = Request::capture();

        try {
            Csrf::validate($request);
            $router = new Router();
            $register = require BASE_PATH . '/routes/web.php';
            $register($router);
            $response = $router->dispatch($request);
        } catch (HttpException $e) {
            $response = self::errorResponse($request, $e->statusCode(), $e->getMessage());
        } catch (\Throwable $e) {
            Logger::error('Excepción no controlada', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $response = self::errorResponse($request, 500, 'Ocurrió un error inesperado. El equipo técnico ya fue notificado.');
        }

        $response->send();
    }

    private static function errorResponse(Request $request, int $status, string $message): Response
    {
        if ($request->wantsJson()) {
            return Response::json(['error' => $message, 'status' => $status], $status);
        }

        try {
            $html = View::render('errors.error', [
                'title' => $status . ' · AgroDiagnóstico',
                'metaDescription' => $message,
                'status' => $status,
                'message' => $message,
            ]);
        } catch (\Throwable) {
            $html = '<!doctype html><html lang="es"><meta charset="utf-8"><title>Error</title><body style="font-family:sans-serif;padding:40px"><h1>Error ' . $status . '</h1><p>' . e($message) . '</p></body></html>';
        }

        return Response::html($html, $status);
    }
}
