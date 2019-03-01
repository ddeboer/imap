<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Exception\NotEmbeddedMessageException;
use Ddeboer\Imap\Message\AttachmentInterface;
use Ddeboer\Imap\Message\EmailAddress;
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

        $actualDate = $embeddedMessage->getDate();
        $this->assertInstanceOf(\DateTimeImmutable::class, $actualDate);
        $this->assertSame($emailDate->format(\DATE_ISO8601), $actualDate->format(\DATE_ISO8601));

        $this->assertSame('demo', $embeddedMessage->getSubject());

        $actualFrom = $embeddedMessage->getFrom();
        $this->assertInstanceOf(EmailAddress::class, $actualFrom);
        $this->assertSame('demo-from@cerstor.cz', $actualFrom->getFullAddress());

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
        $this->assertSame(29, $embeddedAttachment->getSize());
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

    public function testRecursiveEmbeddedAttachment()
    {
        $mailbox = $this->createMailbox();
        $raw = $this->getFixture('four_nested_emails');
        $mailbox->addMessage($raw);

        $message = $mailbox->getMessage(1);
        $this->assertSame('3-third-subject', $message->getSubject());
        $this->assertSame('3-third-content', $message->getBodyText());

        $attachments = $message->getAttachments();
        $this->assertCount(3, $attachments);

        $attachment = \current($attachments);
        $this->assertTrue($attachment->isEmbeddedMessage());

        $embeddedMessage = $attachment->getEmbeddedMessage();
        $this->assertSame('2-second-subject', $embeddedMessage->getSubject());
        $this->assertSame('2-second-content', $embeddedMessage->getBodyText());

        $embeddedAttachments = $embeddedMessage->getAttachments();
        $this->assertCount(2, $embeddedAttachments);

        /** @var AttachmentInterface $embeddedAttachment */
        $embeddedAttachment = \current($embeddedAttachments);
        $this->assertTrue($embeddedAttachment->isEmbeddedMessage());

        $secondEmbeddedMessage = $embeddedAttachment->getEmbeddedMessage();
        $this->assertSame('1-first-subject', $secondEmbeddedMessage->getSubject());
        $this->assertSame('1-first-content', $secondEmbeddedMessage->getBodyText());

        $secondEmbeddedAttachments = $secondEmbeddedMessage->getAttachments();
        $this->assertCount(1, $secondEmbeddedAttachments);

        $secondEmbeddedAttachment = \current($secondEmbeddedAttachments);
        $this->assertTrue($secondEmbeddedAttachment->isEmbeddedMessage());

        $thirdEmbeddedMessage = $secondEmbeddedAttachment->getEmbeddedMessage();
        $this->assertSame('0-zero-subject', $thirdEmbeddedMessage->getSubject());
        $this->assertSame('0-zero-content', $thirdEmbeddedMessage->getBodyText());

        $this->assertCount(0, $thirdEmbeddedMessage->getAttachments());
    }
}
