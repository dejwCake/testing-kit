<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Functional;

//phpcs:disable SlevomatCodingStandard.Classes.TraitUseSpacing.IncorrectLinesCountAfterLastUse

use DejwCake\TestingKit\Snapshot\SnapshotAsserts;

abstract class WebTestCase extends HttpTestCase
{
    use SnapshotAsserts;
}
