<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Models\Role;

class RoleController extends Controller implements HasMiddleware
{
    /**
     * Define the middleware for the RoleController.
     *
     * This method returns an array of middleware that will be applied to the controller's routes.
     * The middleware ensures that the user has the necessary permissions to access the specified actions.
     *
     * @return array The array of middleware instances.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:' . self::PERMISSIONS['role']['read'], only: ['index', 'show']),
        ];
    }

    /**
     * Retrieve a list of all roles with their associated permissions and users count.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $roles = Role::all()->loadCount(['permissions', 'users']);

        return self::withOk('Roles ' . self::MESSAGES['retrieve'], $roles);
    }

    /**
     * Display the specified role along with its permissions and counts.
     *
     * @param  \App\Models\Role  $role
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Role $role): JsonResponse
    {
        $role->load('permissions')->loadCount(['permissions', 'users']);

        return self::withOk('Role ' . self::MESSAGES['retrieve'], $role);
    }
}
