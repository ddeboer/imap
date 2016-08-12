<?php
namespace Ddeboer\Imap\Sort;

use Ddeboer\Imap\SortExpression;

class From extends SortExpression
{
    public function getKeyword()
    {
        return '\SORTFROM';
    }
}