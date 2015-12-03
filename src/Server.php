<?php

namespace Ddeboer\Imap;

use Ddeboer\Imap\Exception\AuthenticationFailedException;

/**
 * An IMAP server
 */
class Server
{
    /**
     * @var string Internet domain name or bracketed IP address of server
     */
    private $hostname;

    /**
     * @var int TCP port number
     */
    private $port;

    /**
     * @var string Optional flags
     */
    private $flags;

    /**
     * @var string
     */
    private $connection;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var int Options bitmask
     *
     * OP_READONLY - Open mailbox read-only
     * OP_ANONYMOUS - Don't use or update a .newsrc for news (NNTP only)
     * OP_HALFOPEN - For IMAP and NNTP names, open a connection but don't open a mailbox.
     * CL_EXPUNGE - Expunge mailbox automatically upon mailbox close (see also imap_delete() and imap_expunge())
     * OP_DEBUG - Debug protocol negotiations
     * OP_SHORTCACHE - Short (elt-only) caching
     * OP_SILENT - Don't pass up events (internal use)
     * OP_PROTOTYPE - Return driver prototype
     * OP_SECURE - Don't do non-secure authentication
     */
    private $options;

    private $lastError;
    /**
     * Constructor
     *
     * @param string $hostname   Internet domain name or bracketed IP address
        *                        of server
     * @param int    $port       TCP port number
     * @param string $flags      Optional flags
     * @param array  $parameters Connection parameters
     */
    public function __construct(
        $hostname,
        $port = 993,
        $flags = '/imap/ssl/validate-cert',
        $parameters = array(),
        $options = 0
    ) {
        if (!function_exists('imap_open')) {
            throw new \RuntimeException('IMAP extension must be enabled');
        }

        $this->hostname = $hostname;
        $this->port = $port;
        $this->flags = $flags ? '/' . ltrim($flags, '/') : '';
        $this->parameters = $parameters;
        $this->options = $options;
    }

    /**
     * Authenticate connection
     *
     * @param string $username Username
     * @param string $password Password
     *
     * @return Connection
     * @throws AuthenticationFailedException
     */
    public function authenticate($username, $password)
    {
        // Wrap imap_open, which gives notices instead of exceptions
        set_error_handler([$this,'errorHandler']);
        $this->lastError = null;

        $resource = imap_open(
            $this->getServerString(),
            $username,
            $password,
            $this->options,
            1,
            $this->parameters
        );

        restore_error_handler();
        if($this->lastError){
            throw $this->lastError;
        }

        if (false === $resource) {
            throw new AuthenticationFailedException($username);
        }


        $check = imap_check($resource);
        $mailbox = $check->Mailbox;
        $this->connection = substr($mailbox, 0, strpos($mailbox, '}')+1);

        // These are necessary to get rid of PHP throwing IMAP errors
        imap_errors();
        imap_alerts();

        return new Connection($resource, $this->connection);
    }

    /**
     * Glues hostname, port and flags and returns result
     *
     * @return string
     */
    private function getServerString()
    {
        return sprintf(
            '{%s:%s%s}',
            $this->hostname,
            $this->port,
            $this->flags
        );
    }

    public function errorHandler($nr,$message)
    {
        $this->lastError = new AuthenticationFailedException('not set', $message);
        return true;
    }
}
