<?php

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Mailbox;
use Ddeboer\Imap\Search\Email\To;
use Ddeboer\Imap\SearchExpression;

class MailboxTest extends AbstractTest
{
    /**
     * @var Mailbox
     */
    protected $mailbox;

    public function setUp()
    {
        $this->mailbox = $this->createMailbox('test-mailbox');

        $this->createTestMessage($this->mailbox, 'Message 1');
        $this->createTestMessage($this->mailbox, 'Message 2');
        $this->createTestMessage($this->mailbox, 'Message 3');
    }

    public function tearDown()
    {
        $this->deleteMailbox($this->mailbox);
    }

    public function testGetName()
    {
        $this->assertStringStartsWith('test-mailbox', $this->mailbox->getName());
    }

    public function testGetMessages()
    {
        $i = 0;
        foreach ($this->mailbox->getMessages() as $message) {
            $i++;
        }

        $this->assertEquals(3, $i);
    }

    public function testCount()
    {
        $this->assertEquals(3, $this->mailbox->count());
    }

    public function testSearch()
    {
        $search = new SearchExpression();
        $condition = new To('me@here.com');
        $search->addCondition($condition);
        $messages = $this->mailbox->getMessages($search);
        $this->assertEquals('Message 1', $messages->current()->getSubject());
    }
}
