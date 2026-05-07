<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Database\Factories;

use DejwCake\TestingKit\Tests\Models\TestUserModel;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

#[UseModel(TestUserModel::class)]
final class TestUserModelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'name' => $this->faker->name(),
            'password' => 'hashed-password',
        ];
    }
}
