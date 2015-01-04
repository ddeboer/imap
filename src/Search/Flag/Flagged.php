<?php

namespace Ddeboer\Imap\Search\Flag;

use Ddeboer\Imap\Search\AbstractCondition;

/**
 * Represents a FLAGGED flag condition. Messages must have the \\FLAGGED flag
 * (i.e. urgent or important) set in order to match the condition.
 */
class Flagged extends AbstractCondition
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword()
    {
        return 'FLAGGED';
    }
}
