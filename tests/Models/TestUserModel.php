<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Models;

use DejwCake\TestingKit\Tests\Database\Factories\TestUserModelFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Override;

final class TestUserModel extends Authenticatable
{
    use HasFactory;

    /**
     * @var array<int, string>
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $fillable = ['id', 'email', 'name', 'password'];

    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $table = 'users';

    public $timestamps = false;

    #[Override]
    protected static function newFactory(): Factory
    {
        return TestUserModelFactory::new();
    }
}
