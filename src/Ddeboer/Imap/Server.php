<?php

namespace Ddeboer\Imap;

use Ddeboer\Imap\Exception\AuthenticationFailedException;

/**
 * An IMAP server
 *
 */
class Server
{
    /**
     * @var string Internet domain name or bracketed IP address of server
     */
    protected $hostname;

    /**
     * @var int TCP port number
     */
    protected $port;

    /**
     * @var string Optional flags
     */
    protected $flags;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * Constructor
     *
     * @param string $hostname Internet domain name or bracketed IP address of server
     * @param int    $port     TCP port number
     * @param string $flags    Optional flags
     */
    public function __construct($hostname, $port = 993, $flags = '/imap/ssl/novalidate-cert')
    {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->flags = $flags ? '/' . ltrim($flags, '/') : '';
    }

    /**
     * Authenticate connection
     *
     * @param string $username Username
     * @param string $password Password
     *
     * @return \Ddeboer\Imap\Connection
     * @throws AuthenticationFailedException
     */
    public function authenticate($username, $password)
    {
        $resource = @\imap_open($this->getServerString(), $username, $password, null, 1);

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
    protected function getServerString()
    {
        return "{{$this->hostname}:{$this->port}{$this->flags}}";
    }
}
