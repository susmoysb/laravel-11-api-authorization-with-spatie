<?php

namespace App\Services;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersonalAccessTokenService
{
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
}
