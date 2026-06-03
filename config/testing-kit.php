<?php

declare(strict_types=1);

use App\Models\User;
use Brackets\AdminAuth\Models\AdminUser;

return [
    'user_model' => User::class,
    'admin_user_model' => AdminUser::class,

    'authenticated_user_id' => 123,
    'dummy_csrf_token' => 'csrf-token-mock',
    'default_locale' => 'en',
    'frozen_now' => '2026-01-15 10:30:00',

    'default_user_email' => 'test@example.com',
    'default_admin_user_email' => 'admin@example.com',
    'default_admin_role' => 'Administrator',

    'openapi' => [
        'spec_path' => storage_path('api-docs/openapi.json'),
        'regenerate_command' => 'l5-swagger:generate',
        'regenerate_on_init' => true,
    ],
];
