<?php

namespace Ddeboer\Imap\Tests;

class MessageTest extends AbstractTest
{
    /**
     * @var \Ddeboer\Imap\Mailbox
     */
    protected $mailbox;

    public function setUp()
    {
        $this->mailbox = $this->createMailbox('test-message');
    }

    public function tearDown()
    {
        $this->deleteMailbox($this->mailbox);
    }

    public function testKeepUnseen()
    {
        $this->createTestMessage($this->mailbox, 'Message A');
        $this->createTestMessage($this->mailbox, 'Message B');
        $this->createTestMessage($this->mailbox, 'Message C');

        $message = $this->mailbox->getMessage(1);
        $this->assertFalse($message->isSeen());

        $message->getBodyText();
        $this->assertTrue($message->isSeen());

        $message = $this->mailbox->getMessage(2);
        $this->assertFalse($message->isSeen());

        $message->keepUnseen()->getBodyText();
        $this->assertFalse($message->isSeen());
    }

    public function testSubjectEncoding()
    {
        $this->createTestMessage($this->mailbox, 'lietuviškos raidės', 'lietuviškos raidės');

        $message = $this->mailbox->getMessage(1);
        $this->assertEquals('lietuviškos raidės', $message->getSubject());
        $this->assertEquals('lietuviškos raidės', $message->getBodyText());
    }
} 