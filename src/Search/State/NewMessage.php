<?php

namespace Ddeboer\Imap\Search\State;

use Ddeboer\Imap\Search\Condition;

/**
 * Represents a NEW condition. Only new messages will match this condition.
 */
class NewMessage extends Condition
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword()
    {
        return 'NEW';
    }
}
