<?php

namespace App\Traits;

trait ConstantsTrait
{
    public const MESSAGES = [
        'validation_error' => 'Validation Error.',
        'register' => 'registered successfully.',
        'store' => 'stored successfully.',
        'update' => 'updated successfully.',
        'delete' => 'deleted successfully.',
        'login' => 'logged in successfully.',
        'logout' => 'logged out successfully.',
        'invalid_credentials' => 'Invalid credentials.',
        'system_error' => 'Something went wrong. Please try again later.',
        'not_found' => 'not found.',
        'token_not_found' => 'Token not found or does not belong to the authenticated user.',
        'retrieve' => 'retrieved successfully.',
        'no_permission' => 'You do not have any permission to perform this action.',
    ];
}
