<?php

namespace Ddeboer\Imap\Search\Date;

/**
 * Represents a date before condition. Messages must have a date before the
 * specified date in order to match the condition.
 */
class Before extends AbstractDate
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword()
    {
        return 'BEFORE';
    }
}
