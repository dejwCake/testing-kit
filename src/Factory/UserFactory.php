<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Factory;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

final class UserFactory
{
    /** @var array<int, array<string, Authenticatable>> */
    private array $users = [];

    public function __construct(private readonly Hasher $hasher)
    {
    }

    public function getCustomer(int $userId, ?string $password = null): Authenticatable
    {
        return $this->getAuthenticatedUser(
            $userId,
            (string) config('testing-kit.default_user_email', 'test@example.com'),
            $password,
        );
    }

    public function getAuthenticatedUser(int $userId, ?string $email = null, ?string $password = null): Authenticatable
    {
        $cacheKey = $email ?? 'null';

        if (!isset($this->users[$userId][$cacheKey])) {
            $this->users[$userId][$cacheKey] = $this->createUser($userId, $email, $password);
        }

        return $this->users[$userId][$cacheKey];
    }

    private function createUser(int $userId, ?string $email = null, ?string $password = null): Authenticatable
    {
        $userData = [
            'id' => $userId,
            'email' => $email ?? (string) config('testing-kit.default_user_email', 'test@example.com'),
            'name' => 'User',
        ];

        if ($password !== null) {
            $userData['password'] = $this->hasher->make($password);
        }

        $modelClass = $this->getUserModelClass();
        /** @phpstan-ignore staticMethod.notFound (HasFactory trait check enforced at runtime) */
        $factory = $modelClass::factory();

        $user = $factory->create($userData);
        assert($user instanceof Authenticatable);

        return $user;
    }

    /**
     * @return class-string<Model&Authenticatable>
     */
    private function getUserModelClass(): string
    {
        $class = (string) config('testing-kit.user_model');

        if (!class_exists($class)) {
            throw new RuntimeException(
                sprintf('User model `%s` configured in testing-kit.user_model does not exist.', $class),
            );
        }

        if (!in_array(HasFactory::class, class_uses_recursive($class), true)) {
            throw new RuntimeException(
                sprintf('User model `%s` must use the HasFactory trait.', $class),
            );
        }

        return $class;
    }
}
