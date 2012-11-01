<?php

namespace Ddeboer\Imap;

use Ddeboer\Imap\Exception\AuthenticationFailedException;

class Server
{
    protected $hostname;
    protected $port;
    protected $connection;
    protected $mailboxes;

    /**
     * Constructor
     *
     * @param string $hostname
     * @param int    $port
     * @param string $flags
     */
    public function __construct($hostname, $port = 993, $flags = '/imap/ssl/validate-cert')
    {
        $this->server = "{{$hostname}:{$port}{$flags}}";
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
        $resource = @\imap_open($this->server, $username, $password, null, 1);

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
}
