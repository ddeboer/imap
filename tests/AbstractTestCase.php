<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\ConnectionInterface;
use Ddeboer\Imap\MailboxInterface;
use Ddeboer\Imap\Server;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\TextPart;

abstract class AbstractTestCase extends TestCase
{
    public const IMAP_FLAGS = '/imap/ssl/novalidate-cert';

    public const SPECIAL_CHARS = 'A_\|!"£$%&()=?àèìòùÀÈÌÒÙ<>-@#[]_ß_б_π_€_✔_你_يد_Z_';

    protected ?string $mailboxName;
    protected ?string $altName;

    final protected function getConnection(): ConnectionInterface
    {
        static $connection;
        if (null === $connection) {
            $connection = $this->createConnection();
        }

        return $connection;
    }

    final protected function createConnection(): ConnectionInterface
    {
        $server = new Server((string) \getenv('IMAP_SERVER_NAME'), (string) \getenv('IMAP_SERVER_PORT'), self::IMAP_FLAGS);

        return $server->authenticate((string) \getenv('IMAP_USERNAME'), (string) \getenv('IMAP_PASSWORD'));
    }

    final protected function createMailbox(?ConnectionInterface $connection = null): MailboxInterface
    {
        $connection ??= $this->getConnection();
        $this->mailboxName = \uniqid('mailbox_' . self::SPECIAL_CHARS);
        $this->altName     = \uniqid('mailbox_' . self::SPECIAL_CHARS);

        return $connection->createMailbox($this->mailboxName);
    }

    final protected function createTestMessage(
        MailboxInterface $mailbox,
        string $subject,
        ?string $contents = null,
        ?string $encoding = null,
        ?string $charset = null,
        ?string $overwriteCharset = null,
    ): void {
        $textPart = new TextPart(
            $contents ?? \uniqid($subject),
            $charset,
            'plain',
            $encoding,
        );

        $message = new Email();
        $message->from('from@here.com');
        $message->to('to@there.com');
        $message->subject($subject);
        $message->setBody($textPart);

        $messageString = $message->toString();
        if (null !== $overwriteCharset) {
            $messageString = \preg_replace(
                \sprintf('/charset="%s"/', \preg_quote((string) $charset)),
                \sprintf('charset="%s"', $overwriteCharset),
                $messageString
            );
        }

        self::assertIsString($messageString);
        $mailbox->addMessage($messageString);
    }

    final protected function getFixture(string $fixture): string
    {
        $content = \file_get_contents(\sprintf('%s/fixtures/%s.eml', __DIR__, $fixture));
        self::assertIsString($content);

        return $content;
    }
}
