<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Exception\NotEmbeddedMessageException;
use Ddeboer\Imap\Message\Part;

/**
 * @covers \Ddeboer\Imap\Message\AbstractMessage
 * @covers \Ddeboer\Imap\Message\Attachment
 * @covers \Ddeboer\Imap\Message\EmbeddedMessage
 */
final class EmbeddedMessageTest extends AbstractTest
{
    public function testEmbeddedMessage()
    {
        $mailbox = $this->createMailbox();
        $raw = $this->getFixture('embedded_email');
        $mailbox->addMessage($raw);

        $message = $mailbox->getMessage(1);
        $attachment = $message->getAttachments()[0];
        $this->assertTrue($attachment->isEmbeddedMessage());

        $emailDate = new \DateTimeImmutable('29 Jan 2016 14:22:13 +0100');
        $embeddedMessage = $attachment->getEmbeddedMessage();
        $this->assertNull($embeddedMessage->getBodyHtml());
        $this->assertEquals('demo text', $embeddedMessage->getBodyText());
        $this->assertEquals([], $embeddedMessage->getCc());
        $this->assertEquals($emailDate->format(\DATE_ISO8601), $embeddedMessage->getDate()->format(\DATE_ISO8601));
        $this->assertEquals('demo', $embeddedMessage->getSubject());
        $this->assertEquals('demo-from@cerstor.cz', $embeddedMessage->getFrom()->getFullAddress());
        $this->assertEquals('demo-to@cerstor.cz', $embeddedMessage->getTo()[0]->getFullAddress());
    }

    public function testEmbeddedAttachment()
    {
        $mailbox = $this->createMailbox();
        $raw = $this->getFixture('embedded_email');
        $mailbox->addMessage($raw);

        $embeddedMessage = $mailbox->getMessage(1)->getAttachments()[0]->getEmbeddedMessage();

        $embeddedAttachment = $embeddedMessage->getAttachments()[0];
        $this->assertEquals('testfile.txt', $embeddedAttachment->getFilename());
        $this->assertEquals('29', $embeddedAttachment->getSize());
        $this->assertEquals('attachment', $embeddedAttachment->getDisposition());
        $this->assertEquals('IHRoaXMgaXMgY29udGVudCBvZiB0ZXN0IGZpbGU=', $embeddedAttachment->getContent());
        $this->assertEquals('base64', $embeddedAttachment->getEncoding());
        $this->assertEquals(Part::TYPE_TEXT, $embeddedAttachment->getType());
        $this->assertEquals(Part::SUBTYPE_PLAIN, $embeddedAttachment->getSubtype());
        $this->assertEquals(' this is content of test file', $embeddedAttachment->getDecodedContent());
        $this->assertEquals('testfile.txt', $embeddedAttachment->getFilename());

        $this->assertFalse($embeddedAttachment->isEmbeddedMessage());

        $this->expectException(NotEmbeddedMessageException::class);

        $embeddedAttachment->getEmbeddedMessage();
    }
}
