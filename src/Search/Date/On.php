<?php

namespace Ddeboer\Imap\Search\Date;

/**
 * Represents a date on condition. Messages must have a date matching the
 * specified date in order to match the condition.
 */
class On extends AbstractDate
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword()
    {
        return 'ON';
    }
}
