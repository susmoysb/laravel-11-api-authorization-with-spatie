<?php

use App\Classes\ApiResponse;
use App\Classes\BaseClass;
use App\Http\Middleware\EnsureTokenIsValid;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;

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
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Exception $exception, Request $request) {
            if ($request->is('api/*')) {

                if (($previous = $exception->getPrevious()) instanceof ModelNotFoundException) {
                    $modelClass = method_exists($previous, 'getModel') ? class_basename($previous->getModel()) : 'Resource';
                    return ApiResponse::withNotFound($modelClass . ' ' . BaseClass::MESSAGES['not_found']);
                }

                if ($exception instanceof ValidationException) {
                    return ApiResponse::withUnprocessableContent(BaseClass::MESSAGES['validation_error'], $exception->validator->errors());
                }

                if ($exception instanceof UnauthorizedException) {
                    return ApiResponse::withForbidden(BaseClass::MESSAGES['no_permission']);
                }

                if ($exception instanceof ThrottleRequestsException) {
                    return ApiResponse::withTooManyRequests(BaseClass::MESSAGES['too_many_requests']);
                }

                return ApiResponse::withInternalServerError($exception->getMessage(), get_class($exception));
            }
        });
    })->create();
