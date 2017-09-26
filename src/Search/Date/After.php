<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Search\Date;

/**
 * Represents a date after condition. Messages must have a date after the
 * specified date in order to match the condition.
 */
final class After extends AbstractDate
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    protected function getKeyword(): string
    {
        return 'SINCE';
    }
}
