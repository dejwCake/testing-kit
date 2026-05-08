<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Models;

use DejwCake\TestingKit\Tests\Database\Factories\TestAdminUserModelFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

final class TestAdminUserModel extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use SoftDeletes;

    /**
     * @var array<int, string>
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'email',
        'password',
        'activated',
        'forbidden',
        'language',
        'last_login_at',
    ];

    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $table = 'admin_users';

    protected static function newFactory(): Factory
    {
        return TestAdminUserModelFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activated' => 'bool',
            'forbidden' => 'bool',
            'last_login_at' => 'datetime',
        ];
    }
}
