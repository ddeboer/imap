<?php

namespace Ddeboer\Imap\Tests;

class ConnectionTest extends AbstractTest
{
    public function testCount()
    {
        $this->assertInternalType('int', $this->getConnection()->count());
    }

    public function testGetMailboxes()
    {
        $mailboxes = $this->getConnection()->getMailboxes();
        $this->assertInternalType('array', $mailboxes);

        foreach ($mailboxes as $mailbox) {
            $this->assertInstanceOf('\Ddeboer\Imap\Mailbox', $mailbox);
        }
    }

    public function testGetMailbox()
    {
        $mailbox = $this->getConnection()->getMailbox('INBOX');
        $this->assertInstanceOf('\Ddeboer\Imap\Mailbox', $mailbox);
    }

    /**
     * @expectedException \Ddeboer\Imap\Exception\MailboxDoesNotExistException
     */
    public function testGetInvalidMailbox()
    {
        $this->getConnection()->getMailbox('does-not-exist');
    }
}