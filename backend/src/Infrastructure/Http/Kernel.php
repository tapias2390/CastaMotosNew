<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Exceptions\AppException;
use App\Infrastructure\Config\Config;
use App\Infrastructure\Logging\Logger;
use App\Presentation\Middleware\CorsMiddleware;
use Throwable;

/**
 * Punto de entrada de la API: aplica middleware global, despacha la ruta
 * y centraliza el manejo de errores en una única respuesta JSON consistente
 * (sección 37 del prompt maestro), sin filtrar detalles internos en producción.
 */
final class Kernel
{
    public function handle(Router $router): void
    {
        CorsMiddleware::apply();

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            return;
        }

        $request = Request::fromGlobals();

        try {
            $router->dispatch($request);
        } catch (AppException $e) {
            Logger::warning($e->getMessage(), ['status' => $e->statusCode()]);
            Response::error($e->getMessage(), $e->statusCode(), $e->errors());
        } catch (Throwable $e) {
            Logger::error($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $isProduction = Config::get('app.env') === 'production';
            $message = $isProduction ? 'Ocurrió un error interno. Intenta nuevamente más tarde.' : $e->getMessage();

            Response::error($message, 500);
        }
    }
}
