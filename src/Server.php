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
        $parameters = array()
    ) {
        if (!function_exists('imap_open')) {
            throw new \RuntimeException('IMAP extension must be enabled');
        }
        
        $this->hostname = $hostname;
        $this->port = $port;
        $this->flags = $flags ? '/' . ltrim($flags, '/') : '';
        $this->parameters = $parameters;
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
        set_error_handler(
            function ($nr, $message) use ($username) {
                throw new AuthenticationFailedException($username, $message);
            }
        );
        
        $resource = imap_open(
            $this->getServerString(),
            $username,
            $password,
            null,
            1,
            $this->parameters
        );

        if (false === $resource) {
            throw new AuthenticationFailedException($username);
        }
        
        restore_error_handler();

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
}
