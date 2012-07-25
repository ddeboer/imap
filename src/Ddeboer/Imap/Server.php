<?php

namespace Ddeboer\Imap;

class Server
{
    protected $hostname;
    protected $port;
    protected $connection;
    protected $mailboxes;

    public function __construct($hostname, $port = '993')
    {
        $this->server = '{' . $hostname . ':' . $port . '/imap}';
    }

    public function authenticate($username, $password)
    {
        $resource = @\imap_open($this->server, $username, $password, null, 1, array('DISABLE_AUTHENTICATOR' => 'GSSAPI'));

        // These are necessary to get rid of PHP throwing IMAP errors
        imap_errors();
        imap_alerts();

        return new Connection($resource, $this->server);
    }
}