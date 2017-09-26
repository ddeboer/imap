<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Search\State;

use Ddeboer\Imap\Search\AbstractCondition;

/**
 * Represents a NEW condition. Only new messages will match this condition.
 */
final class NewMessage extends AbstractCondition
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    protected function getKeyword(): string
    {
        return 'NEW';
    }
}
