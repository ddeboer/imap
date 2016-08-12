<?php
namespace Ddeboer\Imap\Sort;

use Ddeboer\Imap\SortExpression;

class Date extends SortExpression
{
    public function getKeyword()
    {
        return '\SORTDATE';
    }
}