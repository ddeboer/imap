<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Exception\NotEmbeddedMessageException;
use Ddeboer\Imap\Message\AbstractMessage;
use Ddeboer\Imap\Message\Attachment;
use Ddeboer\Imap\Message\EmailAddress;
use Ddeboer\Imap\Message\EmbeddedMessage;
use Ddeboer\Imap\Message\PartInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractMessage::class)]
#[CoversClass(Attachment::class)]
#[CoversClass(EmbeddedMessage::class)]
final class EmbeddedMessageTest extends AbstractTestCase
{
    public function testEmbeddedMessage(): void
    {
        $mailbox = $this->createMailbox();
        $raw     = $this->getFixture('embedded_email');
        $mailbox->addMessage($raw);

        $message    = $mailbox->getMessage(1);
        $attachment = $message->getAttachments()[0];
        self::assertTrue($attachment->isEmbeddedMessage());

        $emailDate       = new \DateTimeImmutable('29 Jan 2016 14:22:13 +0100');
        $embeddedMessage = $attachment->getEmbeddedMessage();
        self::assertNull($embeddedMessage->getBodyHtml());
        self::assertSame('demo text', $embeddedMessage->getBodyText());
        self::assertSame([], $embeddedMessage->getCc());

        $actualDate = $embeddedMessage->getDate();
        self::assertInstanceOf(\DateTimeImmutable::class, $actualDate);
        self::assertSame($emailDate->format(\DATE_ISO8601), $actualDate->format(\DATE_ISO8601));

        self::assertSame('demo', $embeddedMessage->getSubject());

        $actualFrom = $embeddedMessage->getFrom();
        self::assertInstanceOf(EmailAddress::class, $actualFrom);
        self::assertSame('demo-from@cerstor.cz', $actualFrom->getFullAddress());

        self::assertSame('demo-to@cerstor.cz', $embeddedMessage->getTo()[0]->getFullAddress());

        self::assertFalse($message->isSeen());
    }

    public function testEmbeddedAttachment(): void
    {
        $mailbox = $this->createMailbox();
        $raw     = $this->getFixture('embedded_email');
        $mailbox->addMessage($raw);

        $embeddedMessage = $mailbox->getMessage(1)->getAttachments()[0]->getEmbeddedMessage();

        $embeddedAttachment = $embeddedMessage->getAttachments()[0];
        self::assertSame('testfile.txt', $embeddedAttachment->getFilename());
        self::assertSame(29, $embeddedAttachment->getSize());
        self::assertSame('attachment', $embeddedAttachment->getDisposition());
        self::assertSame('IHRoaXMgaXMgY29udGVudCBvZiB0ZXN0IGZpbGU=', $embeddedAttachment->getContent());
        self::assertSame('base64', $embeddedAttachment->getEncoding());
        self::assertSame(PartInterface::TYPE_TEXT, $embeddedAttachment->getType());
        self::assertSame(PartInterface::SUBTYPE_PLAIN, $embeddedAttachment->getSubtype());
        self::assertSame(' this is content of test file', $embeddedAttachment->getDecodedContent());
        self::assertSame('testfile.txt', $embeddedAttachment->getFilename());

        self::assertFalse($embeddedAttachment->isEmbeddedMessage());

        $this->expectException(NotEmbeddedMessageException::class);

        $embeddedAttachment->getEmbeddedMessage();
    }

    public function testRecursiveEmbeddedAttachment(): void
    {
        $mailbox = $this->createMailbox();
        $raw     = $this->getFixture('four_nested_emails');
        $mailbox->addMessage($raw);

        $message = $mailbox->getMessage(1);
        self::assertSame('3-third-subject', $message->getSubject());
        self::assertSame('3-third-content', $message->getBodyText());

        $attachments = $message->getAttachments();
        self::assertCount(3, $attachments);

        $attachment = \current($attachments);
        self::assertNotFalse($attachment);
        self::assertTrue($attachment->isEmbeddedMessage());

        $embeddedMessage = $attachment->getEmbeddedMessage();
        self::assertSame('2-second-subject', $embeddedMessage->getSubject());
        self::assertSame('2-second-content', $embeddedMessage->getBodyText());

        $embeddedAttachments = $embeddedMessage->getAttachments();
        self::assertCount(2, $embeddedAttachments);

        $embeddedAttachment = \current($embeddedAttachments);
        self::assertNotFalse($embeddedAttachment);
        self::assertTrue($embeddedAttachment->isEmbeddedMessage());

        $secondEmbeddedMessage = $embeddedAttachment->getEmbeddedMessage();
        self::assertSame('1-first-subject', $secondEmbeddedMessage->getSubject());
        self::assertSame('1-first-content', $secondEmbeddedMessage->getBodyText());

        $secondEmbeddedAttachments = $secondEmbeddedMessage->getAttachments();
        self::assertCount(1, $secondEmbeddedAttachments);

        $secondEmbeddedAttachment = \current($secondEmbeddedAttachments);
        self::assertNotFalse($secondEmbeddedAttachment);
        self::assertTrue($secondEmbeddedAttachment->isEmbeddedMessage());

        $thirdEmbeddedMessage = $secondEmbeddedAttachment->getEmbeddedMessage();
        self::assertSame('0-zero-subject', $thirdEmbeddedMessage->getSubject());
        self::assertSame('0-zero-content', $thirdEmbeddedMessage->getBodyText());

        self::assertCount(0, $thirdEmbeddedMessage->getAttachments());
    }

    public function testEmbeddedMessageWithoutContentDisposition(): void
    {
        $mailbox = $this->createMailbox();
        $raw     = $this->getFixture('embedded_email_without_content_disposition');
        $mailbox->addMessage($raw);

        $message     = $mailbox->getMessage(1);
        $attachments = $message->getAttachments();
        self::assertCount(6, $attachments);

        $attachment = \current($attachments);
        self::assertNotFalse($attachment);
        self::assertNotEmpty($attachment->getContent());
        self::assertSame('file.jpg', $attachment->getFilename());

        $attachment = \next($attachments);
        self::assertNotFalse($attachment);
        self::assertTrue($attachment->isEmbeddedMessage());

        $embeddedMessage = $attachment->getEmbeddedMessage();
        self::assertSame('embedded_message_subject', $embeddedMessage->getSubject());
        self::assertNotEmpty($embeddedMessage->getBodyText());
        self::assertNotEmpty($embeddedMessage->getBodyHtml());

        $attachment = \next($attachments);
        self::assertNotFalse($attachment);
        self::assertNotEmpty($attachment->getContent());
        self::assertSame('file1.xlsx', $attachment->getFilename());

        $attachment = \next($attachments);
        self::assertNotFalse($attachment);
        self::assertNotEmpty($attachment->getContent());
        self::assertSame('file2.xlsx', $attachment->getFilename());

        $attachment = \next($attachments);
        self::assertNotFalse($attachment);
        self::assertNotEmpty($attachment->getContent());
        self::assertSame('file3.xlsx', $attachment->getFilename());

        $attachment = \next($attachments);
        self::assertNotFalse($attachment);
        self::assertNotEmpty($attachment->getContent());
        self::assertSame('file4.zip', $attachment->getFilename());
    }

    public function testSaveEmbeddedMessage(): void
    {
        $mailbox = $this->createMailbox();
        $raw     = $this->getFixture('embedded_email_without_content_disposition');
        $mailbox->addMessage($raw);

        $message     = $mailbox->getMessage(1);
        $attachments = $message->getAttachments();

        // skip 1. non-embedded attachment (file.jpg) to embedded one
        $attachment = \next($attachments);
        self::assertNotFalse($attachment);
        self::assertTrue($attachment->isEmbeddedMessage());

        $embeddedMessage = $attachment->getEmbeddedMessage();

        $file = \fopen('php://temp', 'w+');
        if (false === $file) {
            self::fail('Unable to create temporary file stream');
        }

        $embeddedMessage->saveRawMessage($file);
        \fseek($file, 0);

        $rawEmbedded = $this->getFixture('embedded_email_without_content_disposition-embedded');
        self::assertSame($rawEmbedded, \stream_get_contents($file));
    }
}
