<?php

namespace Ddeboer\Imap;

use Ddeboer\Imap\Search\LogicalOperator;
use Ddeboer\Imap\Search\Date;
use Ddeboer\Imap\Search\AbstractCondition;
use DateTime;

class Expr extends AbstractCondition
{
    protected $operators = [];

    //public static function getInstance()
    //{
        //static $inst;
        //if(is_null($inst) ){
            //$inst = new static();
        //}

        //return $inst;
    //}

    public function getKeyword()
    {
        return implode(' ',$this->operators);
    }

    public function on(DateTime $date )
    {
        $this->operatos[] =  new Date\On($date);
        return $this;
    }

    public function orX()
    {
        $this->operatos[] =  new LogicalOperator\OrConditions();

        return $this;
    }

    public function since(DateTime $date)
    {
        $this->operatos[] =  new Date\Since($date);
        return $this;
    }

}
