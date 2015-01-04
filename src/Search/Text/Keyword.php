<?php

namespace Ddeboer\Imap\Search\Text;

/**
 * Represents a keyword text contains condition. Messages must have a keyword
 * matching the specified text in order to match the condition.
 */
class Keyword extends Text
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword()
    {
        return 'KEYWORD';
    }
}
