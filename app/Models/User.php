<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'employee_id',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?? $this->getRouteKeyName();
        return $this->withTrashed()->where($field, $value)->firstOrFail();
    }

    protected static function boot()
    {
        parent::boot();

        /**
         * Automatically revoke all API tokens when:
         * 1. The user's `status` changes to `0` (inactive).
         * 2. The user is soft deleted (i.e., `deleted_at` is set).
         * 3. The user is hard deleted (i.e., permanently removed from the database).
         */

        // When user status changes to inactive (0), revoke all their API tokens
        // This ensures that inactive users cannot access authenticated routes anymore
        static::updating(function ($user) {
            // Check if the `status` column is being updated and changed to `0`
            if ($user->isDirty('status') && $user->status == 0) {
                $user->tokens()->delete(); // Revoke all tokens (logout user)
            }
        });

        // When a user is soft deleted or permanently deleted (hard delete), revoke all their API tokens
        // This ensures that the user is logged out and cannot access authenticated routes anymore,
        // as all their API tokens will be deleted when the user is deleted.
        static::deleting(function ($user) {
            $user->tokens()->delete(); // Revoke all tokens (logout user)
        });
    }
}
