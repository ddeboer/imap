<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Search\LogicalOperator;

use Ddeboer\Imap\Search\AbstractCondition;

/**
 * Represents an ALL operator. Messages must match all conditions following this
 * operator in order to match the expression.
 */
final class All extends AbstractCondition
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    protected function getKeyword(): string
    {
        return 'ALL';
    }
}
