<?php

namespace App\Traits;

trait ConstantsTrait
{
    public const MESSAGES = [
        'accept_header_error' => "Accept header must be 'application/json'.",
        'validation_error'    => 'Validation Error.',
        'register'            => 'registered successfully.',
        'store'               => 'stored successfully.',
        'update'              => 'updated successfully.',
        'delete'              => 'deleted successfully.',
        'restore'             => 'restored successfully.',
        'delete_permanently'  => 'deleted permanently.',
        'login'               => 'logged in successfully.',
        'logout'              => 'logged out successfully.',
        'invalid_credentials' => 'Invalid credentials.',
        'unauthenticated'     => 'Authentication failed. Please log in to continue.',
        'system_error'        => 'Something went wrong. Please try again later.',
        'not_found'           => 'not found.',
        'token_not_found'     => 'Token not found or does not belong to the authenticated user.',
        'retrieve'            => 'retrieved successfully.',
        'assign'              => 'assigned successfully.',
        'no_permission'       => 'You do not have any permission to perform this action.',
    ];

    public const ROLES = [
        'superAdmin' => 'Super Admin',
        'admin'      => 'Admin',
        'user'       => 'User',
    ];

    public const PERMISSIONS = [
        'user' => [
            'create'             => 'User Create',
            'read'               => 'User Read',
            'update'             => 'User Update',
            'delete'             => 'User Delete',
            'delete_permanently' => 'User Delete Permanently',
            'restore'            => 'User Restore',
            'status_change'      => 'User Status Change',
            'session_read'       => 'User Session Read',
            'session_delete'     => 'User Session Delete',
            'password_reset'     => 'User Password Reset',
        ],
        'own_profile' => [
            'read'            => 'Own Profile Read',
            'update'          => 'Own Profile Update',
            'delete'          => 'Own Profile Delete',
            'password_change' => 'Own Password Change',
            'session_read'    => 'Own Session Read',
            'session_delete'  => 'Own Session Delete',
        ],
        'role' => [
            'create'         => 'Role Create',
            'read'           => 'Role Read',
            'update'         => 'Role Update',
            'delete'         => 'Role Delete',
            'assign_to_user' => 'Role Assign to User',
        ],
        'permission' => [
            'read'           => 'Permission Read',
            'assign_to_role' => 'Permission Assign to Role',
            'assign_to_user' => 'Permission Assign to User',
        ],
    ];
}
