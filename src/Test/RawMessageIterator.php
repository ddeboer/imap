<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Test;

use Ddeboer\Imap\MessageInterface;
use Ddeboer\Imap\MessageIteratorInterface;

/**
 * Represents a raw expression.
 */
final class RawMessageIterator extends \ArrayIterator implements MessageIteratorInterface
{
    public function current(): MessageInterface
    {
        return parent::current();
    }
}
