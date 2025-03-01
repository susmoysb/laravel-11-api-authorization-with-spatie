<?php

namespace App\Services;

use App\Classes\BaseClass;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class PersonalAccessTokenService extends BaseClass
{
    /**
     * Retrieve all personal access tokens for the authenticated user.
     *
     * @param \Illuminate\Http\Request $request The current HTTP request instance.
     *
     * @return \Illuminate\Database\Eloquent\Collection The collection of personal access tokens.
     */
    public function index(User $user): Collection
    {
        return $user->tokens()->orderByDesc('id')->get();
    }

    /**
     * Generate a new personal access token for the given user.
     *
     * @param \Illuminate\Http\Request $request The incoming request.
     * @param \App\Models\User $user The user for whom the token is being generated.
     * @param \DateTimeInterface|null $expiresAt The expiration time for the token.
     * @param string $name The name of the token.
     * @param array $abilities The abilities/permissions to be assigned to the token.
     *
     * @return string The generated personal access token.
     */
    public function store(Request $request, User $user, ?DateTimeInterface $expiresAt = null, string $name = 'auth_token', array $abilities = ['*']): string
    {
        // Create the token with the given name, abilities, and optional expiration time
        $tokenResult = $user->createToken($name, $abilities, $expiresAt);
        $token = $tokenResult->plainTextToken;

        // Update the 'personal_access_tokens' table with the user's IP address and user agent
        DB::table('personal_access_tokens')
            ->where('id', $tokenResult->accessToken->id)
            ->update([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

        // Return the generated token
        return $token;
    }

    /**
     * Delete a personal access token.
     *
     * If no token ID is provided, the current session's access token will be deleted.
     * If a token ID is provided, the token will be found and deleted if it belongs to the authenticated user.
     *
     * @param \Illuminate\Http\Request $request The current HTTP request instance.
     * @param int|null $tokenId The ID of the token to delete, or null to delete the current token.
     *
     * @return bool|null True if the token was successfully deleted, false if the token was not found or does not belong to the user, null if the current session's token was deleted.
     */
    public function delete(Request $request, int $tokenId = null): bool
    {
        if (!$tokenId) {
            // Logout current session
            return $request->user()->currentAccessToken()->delete();
        }

        // Find token by ID
        $token = PersonalAccessToken::find($tokenId);
        if ($token && $token->tokenable_id === $request->user()->id) {
            return $token->delete();
        }

        throw new HttpResponseException(self::withNotFound(self::MESSAGES['token_not_found']));
    }
}
