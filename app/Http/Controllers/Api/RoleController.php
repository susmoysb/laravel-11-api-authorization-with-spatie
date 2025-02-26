<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            new Middleware(['permission:' . self::PERMISSIONS['role']['update'], 'permission:' . self::PERMISSIONS['permission']['assign_to_role']], only: ['update']),
            new Middleware('permission:' . self::PERMISSIONS['role']['delete'], only: ['destroy']),
            new Middleware('permission:' . self::PERMISSIONS['role']['assign_to_user'], only: ['assignToUser']),
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
            'permission_ids' => ['present', 'array', 'exists:permissions,id'],
        ]);

        $validatedData = $validator->validated();
        $validatedData['guard_name'] = 'web';
        unset($validatedData['permission_id']);

        try {
            DB::beginTransaction();
            $role = Role::create($validatedData);
            $role->syncPermissions($validatedData['permission_ids']);
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

    /**
     * Update role and assign permissions.
     *
     * Uses Route Model Binding to retrieve the role instance. if the role is not found, it returns a not found response.
     * Validates the request (role name and permission_ids), updates the role, syncs the provided permissions
     * All operations are wrapped in a DB transaction for data consistency.
     *
     * @param \Illuminate\Http\Request $request The request instance containing the input data.
     * @param \Spatie\Permission\Models\Role $role The role instance to be updated.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the result of the update operation.
     *
     * @throws \Illuminate\Validation\ValidationException If the validation fails.
     * @throws \Exception If there is an error during the role creation process.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:255', 'unique:roles,name,' . $role->id],
            'permission_ids' => ['present', 'array', 'exists:permissions,id'],
        ]);

        $validatedData = $validator->validated();
        unset($validatedData['permission_id']);

        try {
            DB::beginTransaction();
            $role->update($validatedData);
            $role->syncPermissions($validatedData['permission_ids']);
            DB::commit();

            return self::withOk('Role ' . self::MESSAGES['update'], $role);
        } catch (Exception $e) {
            DB::rollBack();
            return self::withBadRequest(self::MESSAGES['system_error'], $e->getMessage() . ' ' . get_class($e));
        }
    }

    /**
     * Remove the specified role from storage.
     *
     * Uses Route Model Binding to retrieve the role instance. if the role is not found, it returns a not found response.
     *
     * @param  \Spatie\Permission\Models\Role  $role The role instance to be deleted.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the result of the delete operation.
     *
     * @throws \Exception If there is an error during the role deletion process.
     */
    public function destroy(Role $role): JsonResponse
    {
        try {
            $role->delete();
            return self::withOk('Role ' . self::MESSAGES['delete']);
        } catch (Exception $e) {
            return self::withBadRequest(self::MESSAGES['system_error'], $e->getMessage() . ' ' . get_class($e));
        }
    }

    /**
     * Assign roles to the specified user.
     *
     * Uses Route Model Binding to retrieve the user instance. if the user is not found, it returns a not found response.
     * Validates the request (role_ids), syncs the provided roles to the user
     *
     * @param \Illuminate\Http\Request $request The request instance containing the role_ids.
     * @param \App\Models\User $user The user instance to which the roles are to be assigned.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the result of the assign operation.
     *
     * @throws \Illuminate\Validation\ValidationException If the validation fails.
     * @throws \Exception If there is an error during the role assignment process.
     */
    public function assignToUser(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id == $user->id) {
            return self::withForbidden(self::MESSAGES['no_permission']);
        }

        $validator = Validator::make($request->all(), [
            'role_ids' => ['present', 'array', 'exists:roles,id'],
        ]);
        $validatedData = $validator->validated();

        try {
            $user->syncRoles($validatedData['role_ids']);
            return self::withOk('Roles ' . self::MESSAGES['assign']);
        } catch (Exception $e) {
            return self::withBadRequest(self::MESSAGES['system_error'], $e->getMessage() . ' ' . get_class($e));
        }
    }
}
