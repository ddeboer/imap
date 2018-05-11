<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests\Search;

use Ddeboer\Imap\Search\Number;
use Ddeboer\Imap\Tests\AbstractTest;

/**
 * @covers \Ddeboer\Imap\Search\Number
 */
final class NumberTest extends AbstractTest
{
    public function testWithMinimumAndMaximum()
    {
        $condition = new Number(5, 100);

        $this->assertSame('UID 5:100');
    }

    public function testWithoutMinimum()
    {
        $condition = new Number(null, 50);

        $this->assertSame('UID *:50');
    }

    public function testWithoutMaximum()
    {
        $condition = new Number(3820);

        $this->assertSame('UID 3820:*');
    }
}
