<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Feature\Concerns\MocksTranslator;

use DejwCake\TestingKit\Tests\TestCase;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory;

final class MockTranslatorTest extends TestCase
{
    public function testTranslatorReturnsKeyUnchanged(): void
    {
        $translator = $this->app->make(Translator::class);

        self::assertSame('arbitrary.translation.key', $translator->get('arbitrary.translation.key'));
        self::assertSame('arbitrary.choice.key', $translator->choice('arbitrary.choice.key', 1));
    }

    public function testValidatorFactoryStillResolves(): void
    {
        $factory = $this->app->make(Factory::class);

        $validator = $factory->make(['email' => 'not-an-email'], ['email' => 'email']);

        self::assertTrue($validator->fails());
    }

    public function testTheMockLeavesPlaceholdersUntouched(): void
    {
        $translator = $this->app->make(Translator::class);

        self::assertSame(
            'Hello :name',
            $translator->get('Hello :name', ['name' => 'Ada']),
        );
    }

    public function testTheMockKeepsReturningKeysForTheValidator(): void
    {
        $validator = $this->app->make(Factory::class)
            ->make(['email' => 'not-an-email'], ['email' => 'email']);

        self::assertTrue($validator->fails());
        self::assertSame('validation.email', $validator->errors()->first('email'));
    }
}
