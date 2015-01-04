<?php

namespace Ddeboer\Imap\Search\Flag;

use Ddeboer\Imap\Search\AbstractCondition;

/**
 * Represents an RECENT flag condition. Messages must have the \\RECENT flag
 * set in order to match the condition.
 */
class Recent extends AbstractCondition
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword()
    {
        return 'RECENT';
    }
}
