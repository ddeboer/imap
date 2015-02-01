<?php

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Mailbox;
use Ddeboer\Imap\Search\Email\To;
use Ddeboer\Imap\Search\Text\Body;
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

    /**
     * @expectedException \Ddeboer\Imap\Exception\MessageDoesNotExistException
     * @expectedExceptionMessageRegExp /Message 666 does not exist.*Bad message number/
     */
    public function testGetMessageThrowsException()
    {
        $this->mailbox->getMessage(666);
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
}
