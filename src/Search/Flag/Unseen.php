<?php

namespace Ddeboer\Imap\Search\Flag;

use Ddeboer\Imap\Search\AbstractCondition;

/**
 * Represents an UNSEEN flag condition. Messages must not have the \\SEEN flag
 * set in order to match the condition.
 */
class Unseen extends AbstractCondition
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword()
    {
        return 'UNSEEN';
    }
}
