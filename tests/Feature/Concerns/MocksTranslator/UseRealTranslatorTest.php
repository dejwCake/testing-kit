<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Feature\Concerns\MocksTranslator;

use DejwCake\TestingKit\Tests\TestCase;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory;

final class UseRealTranslatorTest extends TestCase
{
    public function testPlaceholdersAreReplacedOnceTheRealTranslatorIsBack(): void
    {
        $this->useRealTranslator();

        $translator = $this->app->make(Translator::class);

        self::assertSame(
            'Hello Ada, you have 3 messages',
            $translator->get('Hello :name, you have :count messages', ['name' => 'Ada', 'count' => 3]),
        );
    }

    public function testTheValidatorUsesTheRealTranslatorToo(): void
    {
        $this->useRealTranslator();

        $validator = $this->app->make(Factory::class)
            ->make(['email' => 'not-an-email'], ['email' => 'email']);

        self::assertTrue($validator->fails());
        self::assertStringNotContainsString(
            'validation.email',
            $validator->errors()->first('email'),
            'A real message, not the translation key the mock returns.',
        );
    }

    public function testTheValidatorIsRebuiltEvenWhenItWasResolvedFirst(): void
    {
        $this->app->make(Factory::class)->make([], []);

        $this->useRealTranslator();

        $validator = $this->app->make(Factory::class)
            ->make(['email' => 'not-an-email'], ['email' => 'email']);

        self::assertStringNotContainsString('validation.email', $validator->errors()->first('email'));
    }
}
