<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests\Search\Date;

use DateTimeImmutable;
use Ddeboer\Imap\Tests\AbstractTest;

/**
 * @covers \Ddeboer\Imap\Search\AbstractDate
 */
final class AbstractDateTest extends AbstractTest
{
    protected $date;

    protected function setUp()
    {
        $this->date = new DateTimeImmutable('2017-03-02');
    }

    public function testDefaultFormat()
    {
        $condition = new TestAsset\FooDate($this->date);

        $this->assertSame('BAR "2-Mar-2017"', $condition->toString());
    }

    public function testCustomFormat()
    {
        $condition = new TestAsset\FooDate($this->date, 'j F Y');

        $this->assertSame('BAR "2 March 2017"', $condition->toString());
    }
}
