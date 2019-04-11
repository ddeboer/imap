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
        $raw     = $this->getFixture('embedded_email');
        $mailbox->addMessage($raw);

        $message    = $mailbox->getMessage(1);
        $attachment = $message->getAttachments()[0];
        static::assertTrue($attachment->isEmbeddedMessage());

        $emailDate       = new \DateTimeImmutable('29 Jan 2016 14:22:13 +0100');
        $embeddedMessage = $attachment->getEmbeddedMessage();
        static::assertNull($embeddedMessage->getBodyHtml());
        static::assertSame('demo text', $embeddedMessage->getBodyText());
        static::assertSame([], $embeddedMessage->getCc());

        $actualDate = $embeddedMessage->getDate();
        static::assertInstanceOf(\DateTimeImmutable::class, $actualDate);
        static::assertSame($emailDate->format(\DATE_ISO8601), $actualDate->format(\DATE_ISO8601));

        static::assertSame('demo', $embeddedMessage->getSubject());

        $actualFrom = $embeddedMessage->getFrom();
        static::assertInstanceOf(EmailAddress::class, $actualFrom);
        static::assertSame('demo-from@cerstor.cz', $actualFrom->getFullAddress());

        static::assertSame('demo-to@cerstor.cz', $embeddedMessage->getTo()[0]->getFullAddress());

        static::assertFalse($message->isSeen());
    }

    public function testEmbeddedAttachment()
    {
        $mailbox = $this->createMailbox();
        $raw     = $this->getFixture('embedded_email');
        $mailbox->addMessage($raw);

        $embeddedMessage = $mailbox->getMessage(1)->getAttachments()[0]->getEmbeddedMessage();

        $embeddedAttachment = $embeddedMessage->getAttachments()[0];
        static::assertSame('testfile.txt', $embeddedAttachment->getFilename());
        static::assertSame(29, $embeddedAttachment->getSize());
        static::assertSame('attachment', $embeddedAttachment->getDisposition());
        static::assertSame('IHRoaXMgaXMgY29udGVudCBvZiB0ZXN0IGZpbGU=', $embeddedAttachment->getContent());
        static::assertSame('base64', $embeddedAttachment->getEncoding());
        static::assertSame(PartInterface::TYPE_TEXT, $embeddedAttachment->getType());
        static::assertSame(PartInterface::SUBTYPE_PLAIN, $embeddedAttachment->getSubtype());
        static::assertSame(' this is content of test file', $embeddedAttachment->getDecodedContent());
        static::assertSame('testfile.txt', $embeddedAttachment->getFilename());

        static::assertFalse($embeddedAttachment->isEmbeddedMessage());

        $this->expectException(NotEmbeddedMessageException::class);

        $embeddedAttachment->getEmbeddedMessage();
    }

    public function testRecursiveEmbeddedAttachment()
    {
        $mailbox = $this->createMailbox();
        $raw     = $this->getFixture('four_nested_emails');
        $mailbox->addMessage($raw);

        $message = $mailbox->getMessage(1);
        static::assertSame('3-third-subject', $message->getSubject());
        static::assertSame('3-third-content', $message->getBodyText());

        $attachments = $message->getAttachments();
        static::assertCount(3, $attachments);

        $attachment = \current($attachments);
        static::assertTrue($attachment->isEmbeddedMessage());

        $embeddedMessage = $attachment->getEmbeddedMessage();
        static::assertSame('2-second-subject', $embeddedMessage->getSubject());
        static::assertSame('2-second-content', $embeddedMessage->getBodyText());

        $embeddedAttachments = $embeddedMessage->getAttachments();
        static::assertCount(2, $embeddedAttachments);

        /** @var AttachmentInterface $embeddedAttachment */
        $embeddedAttachment = \current($embeddedAttachments);
        static::assertTrue($embeddedAttachment->isEmbeddedMessage());

        $secondEmbeddedMessage = $embeddedAttachment->getEmbeddedMessage();
        static::assertSame('1-first-subject', $secondEmbeddedMessage->getSubject());
        static::assertSame('1-first-content', $secondEmbeddedMessage->getBodyText());

        $secondEmbeddedAttachments = $secondEmbeddedMessage->getAttachments();
        static::assertCount(1, $secondEmbeddedAttachments);

        $secondEmbeddedAttachment = \current($secondEmbeddedAttachments);
        static::assertTrue($secondEmbeddedAttachment->isEmbeddedMessage());

        $thirdEmbeddedMessage = $secondEmbeddedAttachment->getEmbeddedMessage();
        static::assertSame('0-zero-subject', $thirdEmbeddedMessage->getSubject());
        static::assertSame('0-zero-content', $thirdEmbeddedMessage->getBodyText());

        static::assertCount(0, $thirdEmbeddedMessage->getAttachments());
    }

    public function testEmbeddedMessageWithoutContentDisposition()
    {
        $mailbox = $this->createMailbox();
        $raw     = $this->getFixture('embedded_email_without_content_disposition');
        $mailbox->addMessage($raw);

        $message     = $mailbox->getMessage(1);
        $attachments = $message->getAttachments();
        static::assertCount(6, $attachments);

        $attachment = \current($attachments);
        static::assertNotEmpty($attachment->getContent());
        static::assertSame('file.jpg', $attachment->getFilename());

        $attachment = \next($attachments);
        static::assertTrue($attachment->isEmbeddedMessage());

        $attachment = \next($attachments);
        static::assertNotEmpty($attachment->getContent());
        static::assertSame('file1.xlsx', $attachment->getFilename());

        $attachment = \next($attachments);
        static::assertNotEmpty($attachment->getContent());
        static::assertSame('file2.xlsx', $attachment->getFilename());

        $attachment = \next($attachments);
        static::assertNotEmpty($attachment->getContent());
        static::assertSame('file3.xlsx', $attachment->getFilename());

        $attachment = \next($attachments);
        static::assertNotEmpty($attachment->getContent());
        static::assertSame('file4.zip', $attachment->getFilename());
    }
}
