<?php
namespace Ddeboer\Imap\Sort;

use Ddeboer\Imap\SortExpression;

class Subject extends SortExpression
{
    public function getKeyword()
    {
        return '\SORTSUBJECT';
    }
}