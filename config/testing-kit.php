<?php

declare(strict_types=1);

return [
    'user_model' => \App\Models\User::class,
    'admin_user_model' => \Brackets\AdminAuth\Models\AdminUser::class,

    'authenticated_user_id' => 123,
    'dummy_csrf_token' => 'csrf-token-mock',
    'default_locale' => 'en',

    'default_user_email' => 'test@example.com',
    'default_admin_user_email' => 'admin@example.com',

    'openapi' => [
        'spec_path' => storage_path('api-docs/openapi.json'),
        'regenerate_command' => 'l5-swagger:generate',
        'regenerate_on_init' => true,
    ],
];
