<?php

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Mailbox;
use Ddeboer\Imap\Exception\MailboxDoesNotExistException;

class MailboxTest extends AbstractTest
{
    /**
     * @var Mailbox
     */
    protected $mailbox;

    public function setUp()
    {
        $this->mailbox = $this->createMailbox('test-mailbox');

        $this->createTestMessage($this->mailbox, 'Message 1');
        $this->createTestMessage($this->mailbox, 'Message 2');
        $this->createTestMessage($this->mailbox, 'Message 3');
    }

    public function tearDown()
    {
        $this->deleteMailbox($this->mailbox);
    }

    public function testGetName()
    {
        $this->assertStringStartsWith('test-mailbox', $this->mailbox->getName());
    }

    public function testGetMessages()
    {
        $i = 0;
        foreach ($this->mailbox->getMessages() as $message) {
            $i++;
        }

        $this->assertEquals(3, $i);
    }

    public function testCount()
    {
        $this->assertEquals(3, $this->mailbox->count());
    }
}
