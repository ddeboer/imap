<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Exception\InvalidSearchCriteriaException;
use Ddeboer\Imap\Mailbox;
use Ddeboer\Imap\MailboxInterface;
use Ddeboer\Imap\Search;
use Ddeboer\Imap\Search\AbstractDate;
use Ddeboer\Imap\Search\AbstractText;
use Ddeboer\Imap\Search\Date\Before;
use Ddeboer\Imap\Search\Date\On;
use Ddeboer\Imap\Search\Date\Since;
use Ddeboer\Imap\Search\Email\Bcc;
use Ddeboer\Imap\Search\Email\Cc;
use Ddeboer\Imap\Search\Email\From;
use Ddeboer\Imap\Search\Email\To;
use Ddeboer\Imap\Search\Flag\Answered;
use Ddeboer\Imap\Search\Flag\Flagged;
use Ddeboer\Imap\Search\Flag\Recent;
use Ddeboer\Imap\Search\Flag\Seen;
use Ddeboer\Imap\Search\Flag\Unanswered;
use Ddeboer\Imap\Search\Flag\Unflagged;
use Ddeboer\Imap\Search\Flag\Unseen;
use Ddeboer\Imap\Search\LogicalOperator\All;
use Ddeboer\Imap\Search\LogicalOperator\OrConditions;
use Ddeboer\Imap\Search\RawExpression;
use Ddeboer\Imap\Search\State\Deleted;
use Ddeboer\Imap\Search\State\NewMessage;
use Ddeboer\Imap\Search\State\Old;
use Ddeboer\Imap\Search\State\Undeleted;
use Ddeboer\Imap\Search\Text\Body;
use Ddeboer\Imap\Search\Text\Keyword;
use Ddeboer\Imap\Search\Text\Subject;
use Ddeboer\Imap\Search\Text\Text;
use Ddeboer\Imap\Search\Text\Unkeyword;
use Ddeboer\Imap\SearchExpression;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;

#[CoversClass(Mailbox::class)]
#[CoversClass(AbstractDate::class)]
#[CoversClass(AbstractText::class)]
#[CoversClass(Before::class)]
#[CoversClass(On::class)]
#[CoversClass(Since::class)]
#[CoversClass(Bcc::class)]
#[CoversClass(Cc::class)]
#[CoversClass(From::class)]
#[CoversClass(To::class)]
#[CoversClass(Answered::class)]
#[CoversClass(Flagged::class)]
#[CoversClass(Recent::class)]
#[CoversClass(Seen::class)]
#[CoversClass(Unanswered::class)]
#[CoversClass(Unflagged::class)]
#[CoversClass(Unseen::class)]
#[CoversClass(All::class)]
#[CoversClass(OrConditions::class)]
#[CoversClass(RawExpression::class)]
#[CoversClass(Deleted::class)]
#[CoversClass(NewMessage::class)]
#[CoversClass(Old::class)]
#[CoversClass(Undeleted::class)]
#[CoversClass(Body::class)]
#[CoversClass(Keyword::class)]
#[CoversClass(Subject::class)]
#[CoversClass(Text::class)]
#[CoversClass(Unkeyword::class)]
#[CoversClass(SearchExpression::class)]
final class MailboxSearchTest extends AbstractTestCase
{
    private MailboxInterface $mailbox;

    protected function setUp(): void
    {
        $this->mailbox = $this->createMailbox();
    }

    public function testSearchCapabilities(): void
    {
        $firstSubject = \uniqid('first_');
        $this->createTestMessage($this->mailbox, $firstSubject);
        $this->createTestMessage($this->mailbox, \uniqid('second_'));

        $messages = $this->mailbox->getMessages(new Subject($firstSubject));

        self::assertCount(1, $messages);
        self::assertSame($firstSubject, $messages->current()->getSubject());

        $messages = $this->mailbox->getMessages(new Subject(\uniqid('none_')));

        self::assertCount(0, $messages);
    }

    public function testUnknownCriterion(): void
    {
        $this->expectException(InvalidSearchCriteriaException::class);

        $this->mailbox->getMessages(new TestAsset\UnknownCriterion());
    }

    public function testRawExpressionCondition(): void
    {
        $messages = $this->mailbox->getMessages(new RawExpression('ON "1-Oct-2017"'));

        self::assertCount(0, $messages);
    }

    public function testSearchEscapes(): void
    {
        $specialChars = 'A_ spaces _09!#$%&\'*+-/=?^_`{|}~.(),:;<>@[\\]_èπ€_Z';
        $specialEmail = $specialChars . '@example.com';

        $date = new \DateTimeImmutable();

        $conditions = [
            new All(),
            new Since($date),
            new Before($date),
            new On($date),
            new Bcc($specialEmail),
            new Cc($specialEmail),
            new From($specialEmail),
            new To($specialEmail),
            new Answered(),
            new Flagged(),
            new Recent(),
            new Seen(),
            new Unanswered(),
            new Unflagged(),
            new Unseen(),
            new Deleted(),
            new NewMessage(),
            new Old(),
            new Undeleted(),
            new Body($specialChars),
            new Keyword($specialChars),
            new Subject($specialChars),
            new Text($specialChars),
            new Unkeyword($specialChars),
        ];

        $searchExpression = new SearchExpression();
        foreach ($conditions as $condition) {
            $searchExpression->addCondition($condition);
        }

        $messages = $this->mailbox->getMessages($searchExpression);

        self::assertCount(0, $messages);
    }

    public function testSpacesAndDoubleQuoteEscape(): void
    {
        self::markTestIncomplete('Unable to get spaces and double quote search together');

        // $spaceAndDoubleQuoteCondition = new Search\Text\Text('A " Z');
        // $messages = $this->mailbox->getMessages($spaceAndDoubleQuoteCondition);
        // self::assertCount(0, $messages);
    }

    public function testOrConditionFunctionality(): OrConditions
    {
        $orCondition = new OrConditions([
            new Body(\uniqid()),
            new Subject(\uniqid()),
        ]);

        self::assertStringContainsString('(', $orCondition->toString());

        return $orCondition;
    }

    #[Depends('testOrConditionFunctionality')]
    public function testOrConditionUsage(OrConditions $orCondition): void
    {
        self::markTestIncomplete('OR condition isn\'t supported by the current c-client library');

        // $messages = $this->mailbox->getMessages($orCondition);
        // self::assertCount(0, $messages);
    }
}
