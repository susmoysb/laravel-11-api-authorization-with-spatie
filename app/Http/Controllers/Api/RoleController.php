<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class RoleController extends Controller implements HasMiddleware
{
    /**
     * Define the middleware for the RoleController.
     *
     * This method returns an array of middleware that will be applied to the controller's methods.
     * The middleware ensures that the user has the necessary permissions to access the specified actions.
     *
     * @return array The array of middleware instances.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:' . self::PERMISSIONS['role']['read'], only: ['index', 'show']),
            new Middleware(['permission:' . self::PERMISSIONS['role']['create'], 'permission:' . self::PERMISSIONS['permission']['assign_to_role']], only: ['store']),
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
     * Create a new role and assign permissions.
     *
     * Validates the request (role name and permission_ids), creates a new role with guard "web", syncs the provided permissions
     * All operations are wrapped in a DB transaction for data consistency.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse The response containing the created role data.
     *
     * @throws \Illuminate\Validation\ValidationException If the validation fails.
     * @throws \Exception If there is an error during the user creation process.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:255', 'unique:roles,name'],
            'permission_ids' => ['array', 'exists:permissions,id'],
        ]);

        $validatedData = $validator->validated();
        $validatedData['guard_name'] = 'web';
        unset($validatedData['permission_id']);

        try {
            DB::beginTransaction();
            $role = Role::create($validatedData);
            $role->syncPermissions($request->input('permission_ids'));
            DB::commit();

            return self::withCreated('Role ' . self::MESSAGES['store'], $role);
        } catch (Exception $e) {
            DB::rollBack();
            return self::withBadRequest(self::MESSAGES['system_error'], $e->getMessage() . ' ' . get_class($e));
        }
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
