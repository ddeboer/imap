<?php
namespace Ddeboer\Imap;

abstract class SortExpression
{
    protected $order;

    abstract public function getKeyword();

    public function __construct($order = 'ASC')
    {
        switch ($order) {
            case 'ASC':
                $this->order = 0;
                break;

            case 'DESC':
                $this->order = 1;
                break;

            default:
                throw new \InvalidArgumentException('Invalid $order passed');
                break;
        }
    }

    public function getOrder()
    {
        return $this->order;
    }
}
