<?php
namespace Ddeboer\Imap\Sort;

use Ddeboer\Imap\SortExpression;

class To extends SortExpression
{
    public function getKeyword()
    {
        return '\SORTTO';
    }
}