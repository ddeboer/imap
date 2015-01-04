<?php

namespace Ddeboer\Imap\Search\LogicalOperator;

use Ddeboer\Imap\Search\AbstractCondition;

/**
 * Represents an ALL operator. Messages must match all conditions following this
 * operator in order to match the expression.
 */
class All extends AbstractCondition
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword()
    {
        return 'ALL';
    }
}
