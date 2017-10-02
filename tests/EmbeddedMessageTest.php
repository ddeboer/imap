<?php

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Message\EmailAddress;

class EmbeddedMessageTest extends AbstractTest
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

    /**
     * @group embeddedMessage
     */
    public function testEmbeddedMessage()
    {
        $raw  = $this->getFixture("testemail");
        $this->mailbox->addMessage($raw);

        $message = $this->mailbox->getMessage(1);
        $attachment = $message->getAttachments()[0];
        $this->assertTrue($attachment->isEmbeddedMessage());

        $embeddedMessage = $attachment->getEmbeddedMessage();
        $this->assertFalse($embeddedMessage->getBodyHtml());
        $this->assertEquals('demo text', $embeddedMessage->getBodyText());
        $this->assertEquals([], $embeddedMessage->getCc());
        $this->assertEquals(new \DateTime('29 Jan 2016 14:22:13 +0100'), $embeddedMessage->getDate());
        $this->assertEquals(new EmailAddress('demo', 'cerstor.cz', ''), $embeddedMessage->getFrom());
        $this->assertEquals('demo', $embeddedMessage->getSubject());
        $this->assertEquals([new EmailAddress('demo', 'cerstor.cz', '')], $embeddedMessage->getTo());
    }

    /**
     * @group embeddedMessage
     */
    public function testEmbeddedAttachment()
    {
        $raw  = $this->getFixture("testemail");
        $this->mailbox->addMessage($raw);

        $embeddedMessage = $this->mailbox->getMessage(1)->getAttachments()[0]->getEmbeddedMessage();

        $embeddedAttachment = $embeddedMessage->getAttachments()[0];
        $this->assertEquals('testfile.txt', $embeddedAttachment->getFilename());
        $this->assertEquals(27, $embeddedAttachment->getSize());
        $this->assertEquals('attachment', $embeddedAttachment->getDisposition());
        $this->assertEquals(null, $embeddedAttachment->getBoundary());
        $this->assertEquals('', $embeddedAttachment->getCharset());
        $this->assertEquals('IHRoaXMgaXMgY29udGVudCBvZiB0ZXN0IGZpbGU=', $embeddedAttachment->getContent());
        $this->assertEquals('base64', $embeddedAttachment->getContentTransferEncoding());
        $this->assertEquals('text/plain', $embeddedAttachment->getContentType());
        $this->assertEquals(' this is content of test file', $embeddedAttachment->getDecodedContent());
        $this->assertEquals('testfile.txt', $embeddedAttachment->getName());
        $this->assertFalse($embeddedAttachment->isEmbeddedMessage());
    }
}
