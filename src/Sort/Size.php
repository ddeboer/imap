<?php
namespace Ddeboer\Imap\Sort;

use Ddeboer\Imap\SortExpression;

class Size extends SortExpression
{
    public function getKeyword()
    {
        return '\SORTSIZE';
    }
}