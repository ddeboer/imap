<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\ConnectionInterface;
use Ddeboer\Imap\MailboxInterface;
use Ddeboer\Imap\Message\AttachmentInterface;
use Ddeboer\Imap\MessageInterface;
use Ddeboer\Imap\ServerInterface;
use Ddeboer\Imap\Test\RawMessageIterator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ddeboer\Imap\Test\RawMessageIterator
 */
final class MockabilityTest extends TestCase
{
    public function testFullMockedBehaviour()
    {
        // Setup
        $username = \uniqid('username_');
        $password = \uniqid('password_');
        $inboxName = \uniqid('INBOX_');
        $attachmentFilename = \uniqid('filename_');

        $attachmentMock = $this->createMock(AttachmentInterface::class);
        $attachmentMock
            ->expects($this->once())
            ->method('getFilename')
            ->willReturn($attachmentFilename)
        ;

        $messageMock = $this->createMock(MessageInterface::class);
        $messageMock
            ->expects($this->once())
            ->method('getAttachments')
            ->willReturn([$attachmentMock])
        ;

        $mailboxMock = $this->createMock(MailboxInterface::class);
        $mailboxMock
            ->expects($this->once())
            ->method('getMessages')
            ->willReturn(new RawMessageIterator([$messageMock]))
        ;

        $connectionMock = $this->createMock(ConnectionInterface::class);
        $connectionMock
            ->expects($this->once())
            ->method('getMailbox')
            ->with($this->identicalTo($inboxName))
            ->willReturn($mailboxMock)
        ;

        $serverMock = $this->createMock(ServerInterface::class);
        $serverMock
            ->expects($this->once())
            ->method('authenticate')
            ->with(
                $this->identicalTo($username),
                $this->identicalTo($password)
            )
            ->willReturn($connectionMock)
        ;

        // Run
        $connection = $serverMock->authenticate($username, $password);
        $mailbox = $connection->getMailbox($inboxName);
        $messages = $mailbox->getMessages();

        $this->assertCount(1, $messages);

        // This foreach has the solely purpose to trigger code-coverage for
        // RawMessageIterator::current() and prove RawMessageIterator is
        // iterable. There is no need to do this in your app test suite
        foreach ($messages as $message) {
            break;
        }

        $attachments = $message->getAttachments();

        $this->assertCount(1, $attachments);

        $attachment = \current($attachments);

        $this->assertSame($attachmentFilename, $attachment->getFilename());
    }
}
