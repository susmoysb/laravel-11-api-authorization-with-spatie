<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller implements HasMiddleware
{
    /**
     * Define the middleware for the UserController.
     *
     * This method returns an array of middleware that will be applied to the controller's methods.
     * The middleware ensures that the user has the necessary permissions to access the specified actions.
     *
     * @return array The array of middleware instances.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:' . self::PERMISSIONS['user']['read'], only: ['index', 'show']),
            new Middleware('permission:' . self::PERMISSIONS['user']['create'], only: ['store']),
            new Middleware('permission:' . self::PERMISSIONS['own_profile']['read'], only: ['me']),
            new Middleware('permission:' . self::PERMISSIONS['own_profile']['password_change'], only: ['changePassword']),
            new Middleware('permission:' . self::PERMISSIONS['user']['restore'], only: ['restore']),
        ];
    }

    /**
     * Display a listing of the users.
     *
     * This method retrieves all users
     *
     * @param \Illuminate\Http\Request $request The incoming request instance.
     *
     * @return \Illuminate\Http\JsonResponse The response containing the list of users
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => ['boolean'],
        ]);

        $validatedData = $validator->validated();

        $users = User::query();
        if (isset($validatedData['status'])) {
            $users->where('status', $validatedData['status']);
        }
        return self::withOk('Users ' . self::MESSAGES['retrieve'], $users->get());
    }

    /**
     * Store a newly created user in storage.
     *
     * This method handles the creation of a new user and stores it in the database.
     * It validates the incoming request data and returns a JSON response.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming request containing user data.
     *
     * @return \Illuminate\Http\JsonResponse  A JSON response indicating the result of the operation.
     *
     * @throws \Illuminate\Validation\ValidationException  If the request data fails validation.
     * @throws \Exception  If an error occurs during the user creation process.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'        => ['required', 'string', 'min:2', 'max:255'],
            'username'    => ['required', 'string', 'min:2', 'max:30', Rule::unique('users')],
            'employee_id' => ['required', 'string', 'min:2', 'max:30', Rule::unique('users')],
            'email'       => ['required', 'string', 'email', 'max:255', Rule::unique('users')],
            'password'    => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $validatedData = $validator->validated();

        try {
            $user = User::create($validatedData);
            return self::withCreated('User ' . self::MESSAGES['store'], $user);
        } catch (Exception $e) {
            return self::withBadRequest(self::MESSAGES['system_error'], $e->getMessage() . ' ' . get_class($e));
        }
    }

    /**
     * Display the specified user.
     *
     * Uses Route Model Binding to retrieve the user instance. if the user is not found, it returns a not found response.
     *
     * @param \Illuminate\Http\Request $request The incoming request instance.
     * @param string $id The ID of the user to retrieve.
     *
     * @return \Illuminate\Http\JsonResponse The response containing the user data or an error message.
     */
    public function show(User $user): JsonResponse
    {
        return self::withOk('User ' . self::MESSAGES['retrieve'], $user);
    }

    /**
     * Update the specified user in storage.
     *
     * This method handles the request to update a user's profile. It validates the incoming request data and returns a JSON response.
     * The authenticated user can update their own profile or other users' profiles based on their permissions.
     *
     * @param \Illuminate\Http\Request $request The incoming request containing the updated user data.
     * @param \App\Models\User $user The user instance to update.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the operation.
     *
     * @throws \Illuminate\Validation\ValidationException If the request data fails validation.
     * @throws \Exception If an error occurs during the user update process.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $authenticatedUser = $request->user();
        $permissionKey = $authenticatedUser->id === $user->id ? 'own_profile' : 'user';
        if (!$authenticatedUser->can(self::PERMISSIONS[$permissionKey]['update'])) {
            return self::withForbidden(self::MESSAGES['no_permission']);
        }

        $validator = Validator::make($request->all(), [
            'name'        => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'username'    => ['sometimes', 'required', 'string', 'min:2', 'max:30', Rule::unique('users')->ignore($user->id)],
            'employee_id' => ['sometimes', 'required', 'string', 'min:2', 'max:30', Rule::unique('users')->ignore($user->id)],
            'email'       => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $validatedData = $validator->validated();

        try {
            $user->update($validatedData);
            return self::withOk('User ' . self::MESSAGES['update'], $user);
        } catch (Exception $e) {
            return self::withBadRequest(self::MESSAGES['system_error'], $e->getMessage() . ' ' . get_class($e));
        }
    }

    /**
     * Retrieve the authenticated user's information.
     *
     * @param \Illuminate\Http\Request $request The current request instance.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response containing the authenticated user's information.
     */
    public function me(Request $request): JsonResponse
    {
        return self::withOk('Authenticated user ' . self::MESSAGES['retrieve'], $request->user());
    }

    /**
     * Update the authenticated user's password.
     *
     * This method handles the request to change the authenticated user's password.
     * It validates the incoming request data and returns a JSON response.
     *
     * @param \Illuminate\Http\Request $request The incoming request containing the new password.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the operation.
     *
     * @throws \Illuminate\Validation\ValidationException If the request data fails validation.
     * @throws \Exception If an error occurs during the password change process.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $validatedData = $validator->validated();

        $user = $request->user();
        if (!Hash::check($validatedData['current_password'], $user->password)) {
            return self::withUnauthorized(self::MESSAGES['invalid_credentials']);
        }

        try {
            $user->update(['password' => Hash::make($validatedData['new_password'])]);
            return self::withOk('Password ' . self::MESSAGES['update']);
        } catch (Exception $e) {
            return self::withBadRequest(self::MESSAGES['system_error'], $e->getMessage() . ' ' . get_class($e));
        }
    }

    /**
     * Remove the specified user from storage.
     *
     * This method handles the request to soft delete a user from the database.
     * The soft delete process marks the user as deleted without removing the record from the database.
     * Authenticated users can delete their own profile or other users' profiles based on their permissions.
     * It returns a JSON response indicating the result of the operation.
     *
     * @param \App\Models\User $user The user instance to delete.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the operation.
     *
     * @throws \Exception If an error occurs during the user deletion process.
     */
    public function destroy(User $user): JsonResponse
    {
        $authenticatedUser = request()->user();
        $permissionKey = $authenticatedUser->id === $user->id ? 'own_profile' : 'user';
        if (!$authenticatedUser->can(self::PERMISSIONS[$permissionKey]['delete'])) {
            return self::withForbidden(self::MESSAGES['no_permission']);
        }

        if (!$user->trashed() && $user->delete()) {
            return self::withOk('User ' . self::MESSAGES['delete']);
        }

        return self::withBadRequest(self::MESSAGES['system_error']);
    }

    /**
     * Restore the specified user from storage.
     *
     * This method handles the request to restore a soft deleted user from the database.
     * Authenticated users can restore other users' profiles based on their permissions.
     * It returns a JSON response indicating the result of the operation.
     *
     * @param \App\Models\User $user The user instance to restore.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the operation.
     *
     * @throws \Exception If an error occurs during the user restoration process.
     */
    public function restore(User $user): JsonResponse
    {
        if ($user->trashed() && $user->restore()) {
            return self::withOk('User ' . self::MESSAGES['restore']);
        }

        return self::withBadRequest(self::MESSAGES['system_error']);
    }
}
