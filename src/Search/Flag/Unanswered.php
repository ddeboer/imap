<?php

namespace Ddeboer\Imap\Search\Flag;

use Ddeboer\Imap\Search\Condition;

/**
 * Represents an UNANSWERED flag condition. Messages must not have the
 * \\ANSWERED flag set in order to match the condition.
 */
class Unanswered extends Condition
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword()
    {
        return 'UNANSWERED';
    }
}
