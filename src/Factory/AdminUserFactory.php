<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Factory;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

final class AdminUserFactory
{
    /** @var array<int, array<string, Authenticatable>> */
    private array $adminUsers = [];

    public function __construct(private readonly Hasher $hasher)
    {
    }

    public function getAdminUser(int $userId, ?string $password = null): Authenticatable
    {
        return $this->getAuthenticatedAdminUser(
            $userId,
            (string) config('testing-kit.default_admin_user_email', 'admin@example.com'),
            $password,
        );
    }

    public function getAuthenticatedAdminUser(
        int $userId,
        ?string $email = null,
        ?string $password = null,
    ): Authenticatable {
        $cacheKey = $email ?? 'null';

        if (!isset($this->adminUsers[$userId][$cacheKey])) {
            $this->adminUsers[$userId][$cacheKey] = $this->createAdminUser($userId, $email, $password);
        }

        return $this->adminUsers[$userId][$cacheKey];
    }

    private function createAdminUser(int $userId, ?string $email = null, ?string $password = null): Authenticatable
    {
        $userData = [
            'id' => $userId,
        ];

        if ($email !== null) {
            $userData['email'] = $email;
        }

        if ($password !== null) {
            $userData['password'] = $this->hasher->make($password);
        }

        $modelClass = $this->getAdminUserModelClass();
        /** @phpstan-ignore staticMethod.notFound (HasFactory trait check enforced at runtime) */
        $factory = $modelClass::factory();

        $adminUser = $factory->create($userData);
        assert($adminUser instanceof Authenticatable);

        return $adminUser;
    }

    /**
     * @return class-string<Model&Authenticatable>
     */
    private function getAdminUserModelClass(): string
    {
        $class = (string) config('testing-kit.admin_user_model');

        if (!class_exists($class)) {
            throw new RuntimeException(
                sprintf('Admin user model `%s` configured in testing-kit.admin_user_model does not exist.', $class),
            );
        }

        if (!in_array(HasFactory::class, class_uses_recursive($class), true)) {
            throw new RuntimeException(
                sprintf('Admin user model `%s` must use the HasFactory trait.', $class),
            );
        }

        return $class;
    }
}
