<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PersonalAccessTokenService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Constructor.
     *
     * Injects the PersonalAccessTokenService dependency to manage personal access tokens.
     *
     * @param PersonalAccessTokenService $personalAccessTokenService The service responsible for generating and managing personal access tokens.
     */
    public function __construct(private PersonalAccessTokenService $personalAccessTokenService) {}

    /**
     * Register a new user.
     *
     * This method handles the registration of a new user. It validates the incoming request data,
     * creates a new user record in the database, generates a personal access token for the user,
     * and returns the user data along with the token.
     *
     * @param \Illuminate\Http\Request $request The incoming request containing user registration data.
     *
     * @return \Illuminate\Http\JsonResponse The response containing the created user data and access token.
     *
     * @throws \Illuminate\Validation\ValidationException If the validation fails.
     * @throws \Exception If there is an error during the user creation process.
     */
    public function register(Request $request): JsonResponse
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
            DB::beginTransaction();
            $user = User::create($validatedData);

            // Create a new personal access token for the user
            $token = $this->personalAccessTokenService->store($request, $user);
            DB::commit();

            return self::withCreated(
                'User ' . self::MESSAGES['register'],
                [
                    'user'       => $user,
                    'token'      => $token,
                    'token_type' => 'Bearer'
                ]
            );
        } catch (Exception $e) {
            DB::rollBack();
            return self::withBadRequest(self::MESSAGES['system_error'], $e->getMessage() . ' ' . get_class($e));
        }
    }

    /**
     * Handle the login request.
     *
     * This method validates the login credentials provided in the request.
     * It accepts a 'login' field which can be a username, employee_id, or email, and a 'password' field
     * If the credentials are valid, it generates a new personal access token for the user with specified abilities and returns it along with user data.
     * If the credentials are invalid, it returns an unauthorized response.
     *
     * @param \Illuminate\Http\Request $request The incoming request instance.
     *
     * @return \Illuminate\Http\JsonResponse The response containing user data and access token, or an unauthorized response.
     *
     * @throws \Illuminate\Validation\ValidationException If the validation fails.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login'    => ['required', 'string'],   // Can be username, employee_id, or email
            'password' => ['required', 'string'],
        ]);

        $validatedData = $validator->validated();

        $login    = $validatedData['login'];
        $password = $validatedData['password'];

        $user = User::where(function ($query) use ($login) {
            $query->where('username', $login)
                ->orWhere('employee_id', $login)
                ->orWhere('email', $login);
            })
            ->where('status', 1)
            // ->whereNull('deleted_at') // This is not necessary since the model uses the SoftDeletes trait
            ->first();

        if ($user && Hash::check($password, $user->password)) {
            // Create a new personal access token for the user
            $token = $this->personalAccessTokenService->store($request, $user);

            return $token ? self::withOk(
                'User ' . self::MESSAGES['login'],
                [
                    'user'       => $user,
                    'token'      => $token,
                    'token_type' => 'Bearer'
                ]
            ) : self::withInternalServerError(self::MESSAGES['system_error']);
        }

        return self::withUnauthorized(self::MESSAGES['invalid_credentials']);
    }

    /**
     * Get the login sessions for the authenticated user.
     * If a user is provided, retrieves the login sessions for that user.
     *
     * This method retrieves all active personal access tokens for the authenticated user or the specified user.
     * It returns the token details including the creation time, expire time and last used time.
     *
     * @param \Illuminate\Http\Request $request The current request instance.
     *
     * @return \Illuminate\Http\JsonResponse The response containing the list of active login sessions.
     */
    public function loginSessions(Request $request, ?User $user = null): JsonResponse
    {
        $authenticatedUser = $request->user();
        $permissionKey = $user ? 'user' : 'own_profile';
        if (!$authenticatedUser->can(self::PERMISSIONS[$permissionKey]['session_read'])) {
            return self::withForbidden(self::MESSAGES['no_permission']);
        }

        $tokens = $this->personalAccessTokenService->index($user ?? $authenticatedUser);

        return self::withOk('Active login sessions ' . self::MESSAGES['retrieve'], $tokens);
    }

    /**
     * Logout the authenticated user by deleting their personal access token.
     *
     * If tokenId is null, deletes the current session.
     * If tokenId is provided, deletes the specified session.
     *
     * @param \Illuminate\Http\Request $request The current request instance.
     *
     * @param int|null $tokenId The ID of the token to delete, or null to delete the current token.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the logout operation.
     */
    public function logout(Request $request, ?PersonalAccessToken $token = null): JsonResponse
    {
        if ($this->personalAccessTokenService->delete($request, $token)) {
            return self::withOk(self::MESSAGES['logout']);
        }
        return self::withBadRequest(self::MESSAGES['system_error']);
    }

    /**
     * Delete a user session.
     *
     * This method deletes a user session based on the provided token. It first checks if the authenticated user
     * has the necessary permissions to delete the session. If the user does not have the required permissions,
     * a forbidden response is returned. If the user has the required permissions, the session is deleted.
     *
     * @param \Illuminate\Http\Request $request The current request instance.
     * @param \Laravel\Sanctum\PersonalAccessToken $token The personal access token associated with the session to be deleted.
     *
     * @return \Illuminate\Http\JsonResponse The response indicating the result of the session deletion.
     */
    public function deleteSession(Request $request, PersonalAccessToken $token)
    {
        $authenticatedUser = $request->user();
        $permissionKey = $authenticatedUser->id === $token->tokenable_id ? 'own_profile' : 'user';

        if (!$authenticatedUser->can(self::PERMISSIONS[$permissionKey]['session_delete'])) {
            return self::withForbidden(self::MESSAGES['no_permission']);
        }

        return $this->logout($request, $token);
    }
}
