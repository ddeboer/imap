<?php

namespace openWebX\Imap\Search\Flag;

use openWebX\Imap\Search\AbstractCondition;

/**
 * Represents an UNANSWERED flag condition. Messages must not have the
 * \\ANSWERED flag set in order to match the condition.
 */
class Unanswered extends AbstractCondition {
    /**
     * Returns the keyword that the condition represents.
     *
     * @return string
     */
    public function getKeyword() {
        return 'UNANSWERED';
    }
}
