<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests\Search\Date;

use Ddeboer\Imap\Tests\AbstractTest;

/**
 * @covers \Ddeboer\Imap\Search\AbstractDate
 */
final class AbstractDateTest extends AbstractTest
{
    /**
     * @var \DateTimeImmutable
     */
    protected $date;

    protected function setUp(): void
    {
        $this->date = new \DateTimeImmutable('2017-03-02');
    }

    public function testDefaultFormat(): void
    {
        $condition = new TestAsset\FooDate($this->date);

        static::assertSame('BAR "2-Mar-2017"', $condition->toString());
    }

    public function testCustomFormat(): void
    {
        $condition = new TestAsset\FooDate($this->date, 'j F Y');

        static::assertSame('BAR "2 March 2017"', $condition->toString());
    }
}
