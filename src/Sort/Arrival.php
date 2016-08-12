<?php
namespace Ddeboer\Imap\Sort;

use Ddeboer\Imap\SortExpression;

class Arrival extends SortExpression
{
    public function getKeyword()
    {
        return '\SORTARRIVAL';
    }
}