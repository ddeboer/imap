<?php

namespace Ddeboer\Imap\Tests;

class ConnectionTest extends AbstractTest
{
    public function testCount()
    {
        $this->assertInternalType('int', self::getConnection()->count());
    }

    public function testGetMailboxes()
    {
        $mailboxes = self::getConnection()->getMailboxes();
        $this->assertInternalType('array', $mailboxes);

        foreach ($mailboxes as $mailbox) {
            $this->assertInstanceOf('\Ddeboer\Imap\Mailbox', $mailbox);
        }
    }

    public function testGetMailbox()
    {
        $mailbox = self::getConnection()->getMailbox('INBOX');
        $this->assertInstanceOf('\Ddeboer\Imap\Mailbox', $mailbox);
    }

    /**
     * @expectedException \Ddeboer\Imap\Exception\MailboxDoesNotExistException
     */
    public function testGetInvalidMailbox()
    {
        self::getConnection()->getMailbox('does-not-exist');
    }
}