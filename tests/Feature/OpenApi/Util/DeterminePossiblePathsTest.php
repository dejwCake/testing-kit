<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Feature\OpenApi\Util;

use DejwCake\TestingKit\OpenApi\Util;
use DejwCake\TestingKit\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class DeterminePossiblePathsTest extends TestCase
{
    /**
     * @param list<string> $expected
     */
    #[DataProvider('cases')]
    public function testDeterminesPossiblePaths(string $path, array $expected): void
    {
        self::assertSame($expected, Util::determinePossiblePaths($path));
    }

    /**
     * @return iterable<string, array{0: string, 1: list<string>}>
     */
    public static function cases(): iterable
    {
        yield 'plain segment' => ['users', ['users']];
        yield 'required parameter' => ['users/{id}', ['users/{id}']];
        yield 'single optional parameter' => ['users/{id?}', ['users', 'users/{id}']];
        yield 'nested required + optional' => [
            'posts/{post}/comments/{comment?}',
            ['posts/{post}/comments', 'posts/{post}/comments/{comment}'],
        ];
        yield 'two trailing optionals' => [
            'a/{x?}/{y?}',
            ['a', 'a/{x}', 'a/{x}/{y}'],
        ];
        yield 'optional then required then optional treats middle as required prefix' => [
            'a/{x?}/b/{y?}',
            ['a/{x}/b', 'a/{x}/b/{y}'],
        ];
    }
}
