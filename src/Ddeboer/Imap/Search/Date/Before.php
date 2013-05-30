<?php

namespace Ddeboer\Imap\Search\Date;

use Ddeboer\Imap\Search\Date;
use Ddeboer\Imap\Search\Condition;

use DateTime;

/**
 * Represents a date before condition. Messages must have a date before the
 * specified date in order to match the condition.
 */
class Before extends Date
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
