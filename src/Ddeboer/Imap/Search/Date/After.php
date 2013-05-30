<?php

namespace Ddeboer\Imap\Search\Date;

use Ddeboer\Imap\Search\Date;
use Ddeboer\Imap\Search\Condition;

use DateTime;

/**
 * Represents a date after condition. Messages must have a date after the
 * specified date in order to match the condition.
 */
class After extends Date
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
