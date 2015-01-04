<?php

namespace Ddeboer\Imap\Search\Text;

/**
 * Represents a keyword text does not contain condition. Messages must not have
 * a keyword matching the specified text in order to match the condition.
 */
class Unkeyword extends Text
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword()
    {
        return 'UNKEYWORD';
    }
}
