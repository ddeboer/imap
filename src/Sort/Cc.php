<?php
namespace Ddeboer\Imap\Sort;

use Ddeboer\Imap\SortExpression;

class Cc extends SortExpression
{
    public function getKeyword()
    {
        return '\SORTCC';
    }
}