<?php

declare(strict_types=1);

namespace Ddeboer\Imap;

final class MessageIterator extends \ArrayIterator
{
    private $resource;

    /**
     * Constructor.
     *
     * @param ImapResourceInterface $resource       IMAP resource
     * @param array                 $messageNumbers Array of message numbers
     */
    public function __construct(ImapResourceInterface $resource, array $messageNumbers)
    {
        $this->resource = $resource;

        parent::__construct($messageNumbers);
    }

    /**
     * Get current message.
     *
     * @return Message
     */
    public function current(): Message
    {
        return new Message($this->resource, parent::current());
    }
}
