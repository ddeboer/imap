<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests\TestAsset;

use Ddeboer\Imap\Search\ConditionInterface;

final class UnknownCriterion implements ConditionInterface
{
    public function toString(): string
    {
        return uniqid('NON_EXISTENT_CRITERION_');
    }
}
