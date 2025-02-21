<?php

use App\Classes\ApiResponse;
use App\Classes\BaseClass;
use App\Http\Middleware\EnsureTokenIsValid;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append([
            EnsureTokenIsValid::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Exception $exception, Request $request) {
            if ($request->is('api/*')) {

                if ($exception instanceof ValidationException) {
                    return ApiResponse::withUnprocessableContent(BaseClass::MESSAGES['validation_error'], $exception->validator->errors());
                }

                return ApiResponse::withInternalServerError($exception->getMessage(), get_class($exception));
            }
        });
    })->create();
