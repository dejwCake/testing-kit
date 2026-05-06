<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\Snapshot;

use Override;
use Spatie\Snapshots\Drivers\HtmlDriver as SpatieHtmlDriver;
use Throwable;

final class HtmlDriver extends SpatieHtmlDriver
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     */
    #[Override]
    public function serialize($data): string
    {
        if ($data === '') {
            return '';
        }

        try {
            return parent::serialize($data);
        } catch (Throwable) {
            // suppressing warnings/error with @ not working
            // and @$domDocument->loadHTML($data) call is throwing errors
            // when <!DOCTYPE html> tag is missing in $data
            return $data;
        }
    }
}
