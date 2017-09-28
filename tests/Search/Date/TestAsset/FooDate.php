<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests\Search\Date\TestAsset;

use Ddeboer\Imap\Search\AbstractDate;

final class FooDate extends AbstractDate
{
    protected function getKeyword(): string
    {
        return 'BAR';
    }
}
