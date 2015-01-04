<?php

namespace Ddeboer\Imap\Search\Text;

/**
 * Represents a body text contains condition. Messages must have a body
 * containing the specified text in order to match the condition.
 */
class Body extends Text
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword()
    {
        return 'BODY';
    }
}
