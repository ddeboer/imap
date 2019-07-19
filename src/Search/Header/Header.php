<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Search\Header;

use Ddeboer\Imap\Search\AbstractText;

/**
 * Class Header.
 *
 * @author Philip Maaß <PhilipMaaß@aol.com>
 */
class Header extends AbstractText
{
    /**
     * @return string
     */
    protected function getKeyword(): string
    {
        return 'HEADER';
    }
}
