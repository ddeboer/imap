<?php

namespace Ddeboer\Imap\Search;

/**
 * Represents a condition that can be used in a search expression.
 */
abstract class AbstractCondition
{
    /**
     * Converts the condition to a string that can be sent to the IMAP server.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getKeyword();
    }

    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    abstract protected function getKeyword();
}
