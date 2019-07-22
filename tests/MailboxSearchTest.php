<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use DateTimeImmutable;
use Ddeboer\Imap\Exception\InvalidSearchCriteriaException;
use Ddeboer\Imap\MailboxInterface;
use Ddeboer\Imap\Search;
use Ddeboer\Imap\SearchExpression;

/**
 * @covers \Ddeboer\Imap\Mailbox::getMessages
 * @covers \Ddeboer\Imap\Search\AbstractDate
 * @covers \Ddeboer\Imap\Search\AbstractText
 * @covers \Ddeboer\Imap\Search\Date\Before
 * @covers \Ddeboer\Imap\Search\Date\On
 * @covers \Ddeboer\Imap\Search\Date\Since
 * @covers \Ddeboer\Imap\Search\Email\Bcc
 * @covers \Ddeboer\Imap\Search\Email\Cc
 * @covers \Ddeboer\Imap\Search\Email\From
 * @covers \Ddeboer\Imap\Search\Email\To
 * @covers \Ddeboer\Imap\Search\Flag\Answered
 * @covers \Ddeboer\Imap\Search\Flag\Flagged
 * @covers \Ddeboer\Imap\Search\Flag\Recent
 * @covers \Ddeboer\Imap\Search\Flag\Seen
 * @covers \Ddeboer\Imap\Search\Flag\Unanswered
 * @covers \Ddeboer\Imap\Search\Flag\Unflagged
 * @covers \Ddeboer\Imap\Search\Flag\Unseen
 * @covers \Ddeboer\Imap\Search\LogicalOperator\All
 * @covers \Ddeboer\Imap\Search\LogicalOperator\OrConditions
 * @covers \Ddeboer\Imap\Search\RawExpression
 * @covers \Ddeboer\Imap\Search\State\Deleted
 * @covers \Ddeboer\Imap\Search\State\NewMessage
 * @covers \Ddeboer\Imap\Search\State\Old
 * @covers \Ddeboer\Imap\Search\State\Undeleted
 * @covers \Ddeboer\Imap\Search\Text\Body
 * @covers \Ddeboer\Imap\Search\Text\Keyword
 * @covers \Ddeboer\Imap\Search\Text\Subject
 * @covers \Ddeboer\Imap\Search\Text\Text
 * @covers \Ddeboer\Imap\Search\Text\Unkeyword
 * @covers \Ddeboer\Imap\Search\Header\Header
 * @covers \Ddeboer\Imap\SearchExpression
 */
final class MailboxSearchTest extends AbstractTest
{
    /**
     * @var MailboxInterface
     */
    protected $mailbox;

    protected function setUp()
    {
        $this->mailbox = $this->createMailbox();
    }

    public function testSearchCapabilities()
    {
        $firstSubject = \uniqid('first_');
        $this->createTestMessage($this->mailbox, $firstSubject);
        $this->createTestMessage($this->mailbox, \uniqid('second_'));

        $messages = $this->mailbox->getMessages(new Search\Text\Subject($firstSubject));

        static::assertCount(1, $messages);
        static::assertSame($firstSubject, $messages->current()->getSubject());

        $messages = $this->mailbox->getMessages(new Search\Text\Subject(\uniqid('none_')));

        static::assertCount(0, $messages);
    }

    public function testUnknownCriterion()
    {
        $this->expectException(InvalidSearchCriteriaException::class);

        $this->mailbox->getMessages(new TestAsset\UnknownCriterion());
    }

    public function testRawExpressionCondition()
    {
        $messages = $this->mailbox->getMessages(new Search\RawExpression('ON "1-Oct-2017"'));

        static::assertCount(0, $messages);
    }

    public function testSearchEscapes()
    {
        $specialChars = 'A_ spaces _09!#$%&\'*+-/=?^_`{|}~.(),:;<>@[\\]_èπ€_Z';
        $specialEmail = $specialChars . '@example.com';

        $date = new DateTimeImmutable();

        $conditions = [
            new Search\LogicalOperator\All(),
            new Search\Date\Since($date),
            new Search\Date\Before($date),
            new Search\Date\On($date),
            new Search\Email\Bcc($specialEmail),
            new Search\Email\Cc($specialEmail),
            new Search\Email\From($specialEmail),
            new Search\Email\To($specialEmail),
            new Search\Flag\Answered(),
            new Search\Flag\Flagged(),
            new Search\Flag\Recent(),
            new Search\Flag\Seen(),
            new Search\Flag\Unanswered(),
            new Search\Flag\Unflagged(),
            new Search\Flag\Unseen(),
            new Search\State\Deleted(),
            new Search\State\NewMessage(),
            new Search\State\Old(),
            new Search\State\Undeleted(),
            new Search\Text\Body($specialChars),
            new Search\Text\Keyword($specialChars),
            new Search\Text\Subject($specialChars),
            new Search\Text\Text($specialChars),
            new Search\Text\Unkeyword($specialChars),
            new Search\Header\Header($specialChars),
        ];

        $searchExpression = new SearchExpression();
        foreach ($conditions as $condition) {
            $searchExpression->addCondition($condition);
        }

        $messages = $this->mailbox->getMessages($searchExpression);

        static::assertCount(0, $messages);
    }

    public function testSpacesAndDoubleQuoteEscape()
    {
        $spaceAndDoubleQuoteCondition = new Search\Text\Text('A " Z');

        static::markTestIncomplete('Unable to get spaces and double quote search together');

        $messages = $this->mailbox->getMessages($spaceAndDoubleQuoteCondition);

        static::assertCount(0, $messages);
    }

    public function testOrConditionFunctionality()
    {
        $orCondition = new Search\LogicalOperator\OrConditions([
            new Search\Text\Body(\uniqid()),
            new Search\Text\Subject(\uniqid()),
        ]);

        static::assertContains('(', $orCondition->toString());

        return $orCondition;
    }

    /**
     * @depends testOrConditionFunctionality
     *
     * @param mixed $orCondition
     */
    public function testOrConditionUsage($orCondition)
    {
        static::markTestIncomplete('OR condition isn\'t supported by the current c-client library');

        $messages = $this->mailbox->getMessages($orCondition);

        static::assertCount(0, $messages);
    }
}
