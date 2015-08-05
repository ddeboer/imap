<?php

namespace Ddeboer\Imap;

use Ddeboer\Imap\Exception\Exception;
use Ddeboer\Imap\Exception\MailboxDoesNotExistException;

/**
 * A connection to an IMAP server that is authenticated for a user
 */
class Connection
{
    private $server;
    private $resource;
    private $mailboxes;
    private $mailboxList;
    protected $closed = false;

    /**
     * Constructor
     *
     * @param resource $resource
     * @param string   $server
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($resource, $server)
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException('$resource must be a resource');
        }

        $this->resource = $resource;
        $this->server = $server;
    }

    /**
     * Get a list of mailboxes (also known as folders)
     *
     * @return Mailbox[]
     */
    public function getMailboxes()
    {
        if($this->closed){
            return true;
        }
        if (null === $this->mailboxes) {
            foreach ($this->getMailboxList() as $mailbox) {
                $this->mailboxes[] = new Mailbox($mailbox, $this);
            }
        }

        return $this->mailboxes;
    }

    /**
     * Get a mailbox by its name
     *
     * @param string $name Mailbox name
     *
     * @return Mailbox
     * @throws MailboxDoesNotExistException If mailbox does not exist
     */
    public function getMailbox($name)
    {
        if($this->closed){
            return true;
        }
        $list = $this->getMailboxList();

        if (!array_key_exists($name, $list)) {
            throw new MailboxDoesNotExistException($name);
        }

        //no name transcoding, keep it encoded
        return new Mailbox($list[$name], $this);
    }

    /**
     * Count number of messages not in any mailbox
     *
     * @return int
     */
    public function count()
    {
        if($this->closed){
            return true;
        }
        return imap_num_msg($this->resource);
    }

    /**
     * Create mailbox
     *
     * @param $name
     *
     * @return Mailbox
     * @throws Exception
     */
    public function createMailbox($name)
    {
        if($this->closed){
            return true;
        }
        //name must be encoded in utf7
        if (imap_createmailbox($this->resource, $this->server . $name)) {
            $this->mailboxNames = $this->mailboxes = null;

            return $this->getMailbox($name);
        }

        throw new Exception("Can not create '{$name}' mailbox at '{$this->server}'");
    }

    /**
     * Close connection
     *
     * @param int $flag
     *
     * @return bool
     */
    public function close($flag = 0)
    {
        if($this->closed){
            return true;
        }

        $this->closed = true;
        return imap_close($this->resource, $flag);
    }

    public function clearCache()
    {
        if($this->closed){
            return true;
        }

        return imap_gc($this->resource, IMAP_GC_ELT|IMAP_GC_TEXTS|IMAP_GC_ENV);
    }

    public function deleteMailbox(Mailbox $mailbox)
    {
        if($this->closed){
            return false;
        }

        if (false === imap_deletemailbox(
            $this->resource,
            $this->server . $mailbox->getName()
        )) {
            throw new Exception('Mailbox ' . $mailbox->getName() . ' could not be deleted');
        }

        $this->mailboxes = $this->mailboxNames = null;
    }

    /**
     * Get IMAP resource
     *
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Get mailbox names
     *
     * @return array
     */
    public function getMailboxNames()
    {
        $list = $this->getMailboxList();
        if(is_null($list)){
            return array();
        }

        return array_keys($list);

    }

    protected function getMailBoxList()
    {
        if(!is_null($this->mailboxList)){
            return $this->mailboxList;
        }

        $mailboxes = imap_getmailboxes($this->resource, $this->server, '*');
        $this->mailboxList = array();

        foreach ($mailboxes as $mailbox) {
            $name =  str_replace($this->server, '', $mailbox->name);
            $this->mailboxList[$name] = $mailbox;
        }
        return $this->mailboxList;
    }
}
