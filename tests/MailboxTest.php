<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Exception;
use Ddeboer\Imap\Mailbox;
use Ddeboer\Imap\Search\Email\To;
use Ddeboer\Imap\Search\Text\Body;
use Ddeboer\Imap\SearchExpression;

/**
 * @covers \Ddeboer\Imap\Mailbox
 */
class MailboxTest extends AbstractTest
{
    /**
     * @var Mailbox
     */
    protected $mailbox;

    protected function setUp()
    {
        $this->mailbox = $this->createMailbox();

        $this->createTestMessage($this->mailbox, 'Message 1');
        $this->createTestMessage($this->mailbox, 'Message 2');
        $this->createTestMessage($this->mailbox, 'Message 3');
    }

    public function testGetName()
    {
        $this->assertSame($this->mailboxName, $this->mailbox->getName());
    }

    public function testGetFullEncodedName()
    {
        $this->assertContains(\getenv('IMAP_SERVER_PORT'), $this->mailbox->getFullEncodedName());
        $this->assertNotContains($this->mailboxName, $this->mailbox->getFullEncodedName());
        $this->assertContains(imap_utf7_encode($this->mailboxName), $this->mailbox->getFullEncodedName());
    }

    public function testGetAttributes()
    {
        $this->assertGreaterThan(0, $this->mailbox->getAttributes() & LATT_HASNOCHILDREN);
    }

    public function testGetDelimiter()
    {
        $this->assertSame('.', $this->mailbox->getDelimiter());
    }

    public function testGetMessages()
    {
        $directMethodInc = 0;
        foreach ($this->mailbox->getMessages() as $message) {
            ++$directMethodInc;
        }

        $this->assertSame(3, $directMethodInc);

        $aggregateIteratorMethodInc = 0;
        foreach ($this->mailbox as $message) {
            ++$aggregateIteratorMethodInc;
        }

        $this->assertSame(3, $aggregateIteratorMethodInc);
    }

    public function testGetMessageThrowsException()
    {
        $this->expectException(Exception\MessageDoesNotExistException::class);
        $this->expectExceptionMessageRegExp('/E_WARNING.+Message 999 does not exist.+Bad message number/s');

        $this->mailbox->getMessage(999);
    }

    public function testCount()
    {
        $this->assertEquals(3, $this->mailbox->count());
    }

    public function testSearch()
    {
        $this->createTestMessage($this->mailbox, 'Result', 'Contents');

        $search = new SearchExpression();
        $search->addCondition(new To('me@here.com'))
            ->addCondition(new Body('Contents'))
        ;

        $messages = $this->mailbox->getMessages($search);
        $this->assertCount(1, $messages);
        $this->assertEquals('Result', $messages->current()->getSubject());
    }

    public function testSearchNoResults()
    {
        $search = new SearchExpression();
        $search->addCondition(new To('nope@nope.com'));
        $this->assertCount(0, $this->mailbox->getMessages($search));
    }

    public function testDelete()
    {
        $this->mailbox->delete();

        $this->expectException(Exception\Exception::class);

        $this->mailbox->count();
    }
}
