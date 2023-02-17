<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests\Search\Date;

use Ddeboer\Imap\Search\AbstractDate;
use Ddeboer\Imap\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractDate::class)]
final class AbstractDateTest extends AbstractTestCase
{
    private \DateTimeImmutable $date;

    protected function setUp(): void
    {
        $this->date = new \DateTimeImmutable('2017-03-02');
    }

    public function testDefaultFormat(): void
    {
        $condition = new TestAsset\FooDate($this->date);

        self::assertSame('BAR "2-Mar-2017"', $condition->toString());
    }

    public function testCustomFormat(): void
    {
        $condition = new TestAsset\FooDate($this->date, 'j F Y');

        self::assertSame('BAR "2 March 2017"', $condition->toString());
    }
}
