<?php

namespace Ddeboer\Imap\Search\State;

use Ddeboer\Imap\Search\Condition;

/**
 * Represents a DELETED condition. Messages must have been marked for deletion
 * but not yet expunged in order to match the condition.
 */
class Deleted extends Condition
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword()
    {
        return 'DELETED';
    }
}
