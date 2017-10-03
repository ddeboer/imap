<?php

declare(strict_types=1);

namespace Ddeboer\Imap;

final class MessageIterator extends \ArrayIterator implements MessageIteratorInterface
{
    /**
     * @var ImapResourceInterface
     */
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
     * @return MessageInterface
     */
    public function current(): MessageInterface
    {
        return new Message($this->resource, parent::current());
    }
}
