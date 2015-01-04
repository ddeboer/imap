<?php

namespace Ddeboer\Imap\Search\LogicalOperator;

use Ddeboer\Imap\Search\AbstractCondition;

/**
 * Represents an OR operator. Messages only need to match one of the conditions
 * after this operator to match the expression.
 */
class OrConditions extends AbstractCondition
{
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword()
    {
        return 'OR';
    }
}
