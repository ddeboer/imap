<?php

namespace Ddeboer\Imap;

use Ddeboer\Imap\Search\LogicalOperator;
use Ddeboer\Imap\Search\Date;
use Ddeboer\Imap\Search\Text;
use Ddeboer\Imap\Search\Email;
use Ddeboer\Imap\Search\AbstractCondition;
use DateTime;

class Expr extends AbstractCondition
{
    protected $operators = [];

    public function getKeyword()
    {
        return implode(' ',$this->operators);
    }

    public function on(DateTime $date )
    {
        $this->operators[] =  new Date\On($date);
        return $this;
    }

    public function orX()
    {
        $this->operators[] =  new LogicalOperator\OrConditions();

        return $this;
    }

    public function since(DateTime $date)
    {
        $this->operators[] =  new Date\Since($date);
        return $this;
    }

    public function before(DateTime $date)
    {
        $this->operators[] =  new Date\Before($date);
        return $this;
    }

    public function subject($text)
    {
        $this->operators[] =  new Text\Subject($text);
        return $this;
    }

    public function from($text)
    {
        $this->operators[] =  new Email\FromAddress($text);
        return $this;
    }

    public function to($text)
    {
        $this->operators[] =  new Email\To($text);
        return $this;
    }
}
