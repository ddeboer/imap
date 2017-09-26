<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Search;

/**
 * Represents a condition that can be used in a search expression.
 */
abstract class AbstractCondition implements ConditionInterface
{
    /**
     * Converts the condition to a string that can be sent to the IMAP server.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->getKeyword();
    }

    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    abstract protected function getKeyword(): string;
}
