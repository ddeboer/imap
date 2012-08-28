<?php

namespace Ddeboer\Imap\Search;

class DateRange
{
    protected $from;
    protected $until;

    public function getFrom()
    {
        return $this->from;
    }

    public function setFrom(\DateTime $from)
    {
        $this->from = $from;

        return $this;
    }

    public function getUntil()
    {
        return $this->until;
    }

    public function setUntil(\DateTime $until)
    {
        $this->until = $until;

        return $this;
    }
}

