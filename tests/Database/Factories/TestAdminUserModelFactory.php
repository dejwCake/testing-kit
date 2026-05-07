<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Database\Factories;

use DejwCake\TestingKit\Tests\Models\TestAdminUserModel;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

#[UseModel(TestAdminUserModel::class)]
final class TestAdminUserModelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'hashed-password',
            'activated' => $this->faker->boolean(),
            'forbidden' => false,
            'language' => 'en',
            'last_login_at' => $this->faker->dateTime(),
        ];
    }
}
