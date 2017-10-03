<?php

declare(strict_types=1);

namespace Ddeboer\Imap;

use Ddeboer\Imap\Exception\InvalidResourceException;

/**
 * An imap resource stream.
 */
final class ImapResource implements ImapResourceInterface
{
    private $resource;

    /**
     * Constructor.
     *
     * @param resource $resource
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Get IMAP resource stream.
     *
     * @throws InvalidResourceException
     *
     * @return resource
     */
    public function getStream()
    {
        if (false === \is_resource($this->resource) || 'imap' !== \get_resource_type($this->resource)) {
            throw new InvalidResourceException('Supplied resource is not a valid imap resource');
        }

        return $this->resource;
    }
}
