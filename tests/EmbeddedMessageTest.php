<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Exception\NotEmbeddedMessageException;
use Ddeboer\Imap\Message\PartInterface;

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
        $this->assertSame('demo text', $embeddedMessage->getBodyText());
        $this->assertSame([], $embeddedMessage->getCc());
        $this->assertSame($emailDate->format(\DATE_ISO8601), $embeddedMessage->getDate()->format(\DATE_ISO8601));
        $this->assertSame('demo', $embeddedMessage->getSubject());
        $this->assertSame('demo-from@cerstor.cz', $embeddedMessage->getFrom()->getFullAddress());
        $this->assertSame('demo-to@cerstor.cz', $embeddedMessage->getTo()[0]->getFullAddress());

        $this->assertFalse($message->isSeen());
    }

    public function testEmbeddedAttachment()
    {
        $mailbox = $this->createMailbox();
        $raw = $this->getFixture('embedded_email');
        $mailbox->addMessage($raw);

        $embeddedMessage = $mailbox->getMessage(1)->getAttachments()[0]->getEmbeddedMessage();

        $embeddedAttachment = $embeddedMessage->getAttachments()[0];
        $this->assertSame('testfile.txt', $embeddedAttachment->getFilename());
        $this->assertSame('29', $embeddedAttachment->getSize());
        $this->assertSame('attachment', $embeddedAttachment->getDisposition());
        $this->assertSame('IHRoaXMgaXMgY29udGVudCBvZiB0ZXN0IGZpbGU=', $embeddedAttachment->getContent());
        $this->assertSame('base64', $embeddedAttachment->getEncoding());
        $this->assertSame(PartInterface::TYPE_TEXT, $embeddedAttachment->getType());
        $this->assertSame(PartInterface::SUBTYPE_PLAIN, $embeddedAttachment->getSubtype());
        $this->assertSame(' this is content of test file', $embeddedAttachment->getDecodedContent());
        $this->assertSame('testfile.txt', $embeddedAttachment->getFilename());

        $this->assertFalse($embeddedAttachment->isEmbeddedMessage());

        $this->expectException(NotEmbeddedMessageException::class);

        $embeddedAttachment->getEmbeddedMessage();
    }
}
