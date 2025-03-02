<?php

namespace App\Http\Middleware;

use App\Classes\BaseClass;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsValid extends BaseClass
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->expectsJson()) {
            return self::withNotAcceptable(self::MESSAGES['accept_header_error']);
        }

        if ($request->is('api/login') || $request->is('api/register')) {
            return $next($request);
        }

        $bearerToken = trim($request->bearerToken());
        if (!$bearerToken) {
            return self::withUnauthorized('Token is required.');
        }

        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return self::withUnauthorized(self::MESSAGES['unauthenticated']);
        }

        // $token = PersonalAccessToken::findToken($bearerToken);
        // if (!$token) {
        //     return self::withUnauthorized('Invalid Token.');
        // }

        return $next($request);
    }
}
