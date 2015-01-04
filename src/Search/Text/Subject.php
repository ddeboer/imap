<?php

namespace Ddeboer\Imap\Search\Text;

/**
 * Represents a subject contains condition. Messages must have a subject
 * containing the specified text in order to match the condition.
 */
class Subject extends Text
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword()
    {
        return 'SUBJECT';
    }
}
