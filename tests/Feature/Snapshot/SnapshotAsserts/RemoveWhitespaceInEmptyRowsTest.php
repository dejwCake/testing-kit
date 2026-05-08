<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Tests\Feature\Snapshot\SnapshotAsserts;

use DejwCake\TestingKit\Snapshot\SnapshotAsserts;
use DejwCake\TestingKit\Tests\TestCase;

final class RemoveWhitespaceInEmptyRowsTest extends TestCase
{
    use SnapshotAsserts;

    public function testTrailingSpacesAreStripped(): void
    {
        $input = "first line   \n   \nthird line";
        $expected = "first line\n\nthird line";

        self::assertSame($expected, $this->removeWhitespaceInEmptyRows($input));
    }

    public function testInnerSpacesArePreserved(): void
    {
        $input = "a   b\nc   d";

        self::assertSame($input, $this->removeWhitespaceInEmptyRows($input));
    }
}
