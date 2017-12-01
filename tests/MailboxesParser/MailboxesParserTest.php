<?php
/**
 * Created by PhpStorm.
 * User: Lukasz
 * Date: 2017-11-10
 * Time: 19:51
 */

namespace MailboxesParser;

use Ddeboer\Imap\MailboxesParser\MailboxesParser;
use Ddeboer\Imap\MailboxesParser\ParsedMailbox;
use Ddeboer\Imap\MailboxInterface;
use PHPUnit\Framework\TestCase;

final class MailboxesParserTest extends TestCase
{
    /** @var MailboxInterface[] */
    protected $mailboxes;

    protected function setUp()
    {
        parent::setUp();
        $this->mailboxes = [
            $this->createMailboxMock('INBOX.Drafts'),
            $this->createMailboxMock('INBOX.Sent'),
            $this->createMailboxMock('INBOX.Sent.sub'),
            $this->createMailboxMock('INBOX.normal'),
            $this->createMailboxMock('INBOX'),
            $this->createMailboxMock('INBOX.normal.sub'),
            $this->createMailboxMock('INBOX.trash'),
            $this->createMailboxMock('Outside'),
        ];
    }

    public function testParser()
    {
        $parser = new MailboxesParser($this->mailboxes);
        $folders = $parser->getFolders();
        $this->assertCount(8, $folders);
        $this->assertEquals('INBOX', $folders[0]->getMailboxName());
        $this->assertEquals('Inbox', $folders[0]->getName());
        $this->assertInstanceOf(MailboxInterface::class, $folders[0]->getMailbox());
        $order = [];
        /** @var ParsedMailbox $parsedMailbox */
        foreach ($folders AS $parsedMailbox) {
            $order[] = ['order' => $parsedMailbox->getOrder(), 'name' => $parsedMailbox->getMailboxName()];
        }
        $expected = [
            ['order' => 1, 'name' => 'INBOX'],
            ['order' => 2, 'name' => 'INBOX.Sent'],
            ['order' => 2.0001, 'name' => 'INBOX.Sent.sub'],
            ['order' => 3, 'name' => 'INBOX.Drafts'],
            ['order' => 10, 'name' => 'Outside'],
            ['order' => 101, 'name' => 'INBOX.normal'],
            ['order' => 101.0001, 'name' => 'INBOX.normal.sub'],
            ['order' => 30000, 'name' => 'INBOX.trash'],
        ];
        $this->assertEquals($expected, $order);
    }

    public function testSetLanguage()
    {
        $mailboxes = [
            $this->createMailboxMock('INBOX'),
            $this->createMailboxMock('INBOX.Wysłane'),
        ];
        $parser = new MailboxesParser($mailboxes);
        $parser->setLanguage('pl');
        $folders = $parser->getFolders();
        $this->assertEquals('INBOX', $folders[0]->getMailboxName());
        $this->assertEquals('Odebrane', $folders[0]->getName());
        $this->assertEquals('INBOX.Wysłane', $folders[1]->getMailboxName());
        $this->assertEquals('Wysłane', $folders[1]->getName());
    }

    private function createMailboxMock($mailboxName)
    {
        $mailbox = $this->createMock(MailboxInterface::class);
        $mailbox->method('getName')
            ->willReturn($mailboxName);

        $mailbox->method('getDelimiter')
            ->willReturn('.');

        return $mailbox;
    }
}
