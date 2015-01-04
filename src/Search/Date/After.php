<?php

namespace Ddeboer\Imap\Search\Date;

/**
 * Represents a date after condition. Messages must have a date after the
 * specified date in order to match the condition.
 */
class After extends AbstractDate
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword()
    {
        return 'SINCE';
    }
}
