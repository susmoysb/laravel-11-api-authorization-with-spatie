<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller implements HasMiddleware
{
    /**
     * Define the middleware for the PermissionController.
     *
     * This method returns an array of middleware that will be applied to the controller's methods.
     * The middleware ensures that the user has the necessary permissions to access the specified actions.
     *
     * @return array The array of middleware instances.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:' . self::PERMISSIONS['permission']['read'], only: ['index']),
            new Middleware('permission:' . self::PERMISSIONS['permission']['assign_to_user'], only: ['assignToUser']),
        ];
    }

    /**
     * Retrieve a list of all permissions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::all();

        return self::withOk('Permissions ' . self::MESSAGES['retrieve'], $permissions);
    }

    /**
     * Assign permissions to a user.
     *
     * Uses Route Model Binding to retrieve the user instance. if the user is not found, it returns a not found response.
     * This method assigns the specified permissions to the user.
     *
     * @param \Illuminate\Http\Request $request The incoming request containing the permission ids.
     * @param \App\Models\User $user The user to whom the permissions will be assigned.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the result of the assign operation.
     *
     * @throws \Illuminate\Validation\ValidationException If the validation fails.
     * @throws \Exception If there is an error during the permission assignment process.
     */
    public function assignToUser(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'permission_ids' => ['present', 'array', 'exists:permissions,id'],
        ]);

        $validatedData = $validator->validated();

        try {
            $user->syncPermissions($validatedData['permission_ids']);

            return self::withOk('Permissions ' . self::MESSAGES['assign']);
        } catch (Exception $e) {
            return self::withBadRequest(self::MESSAGES['system_error'], $e->getMessage() . ' ' . get_class($e));
        }
    }
}
