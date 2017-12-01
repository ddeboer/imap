<?php
/**
 * Created by PhpStorm.
 * User: Lukasz
 * Date: 2017-11-10
 * Time: 20:09
 */

namespace Ddeboer\Imap\MailboxesParser;


use Ddeboer\Imap\MailboxInterface;

/**
 * Class ParsedMailbox
 * @package Ddeboer\Imap\MailboxesParser
 */
final class ParsedMailbox
{
    /** @var MailboxInterface */
    protected $mailbox;
    protected $order;
    protected $mailboxName;
    protected $name;
    protected $special;
    protected $delimiter = '.';
    protected $level = 1;
    protected $subfolders = 0;

    /**
     * @return MailboxInterface
     */
    public function getMailbox(): MailboxInterface
    {
        return $this->mailbox;
    }

    /**
     * @param MailboxInterface $mailbox
     */
    public function setMailbox(MailboxInterface $mailbox)
    {
        $this->mailbox = $mailbox;
    }

    /**
     * @return mixed
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param mixed $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return mixed
     */
    public function getMailboxName()
    {
        return $this->mailboxName;
    }

    /**
     * @param mixed $mailboxName
     */
    public function setMailboxName($mailboxName)
    {
        $this->mailboxName = $mailboxName;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getSpecial()
    {
        return $this->special;
    }

    /**
     * @param mixed $special
     */
    public function setSpecial($special)
    {
        $this->special = $special;
    }

    /**
     * @return string
     */
    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * @param string $delimiter
     */
    public function setDelimiter(string $delimiter)
    {
        $this->delimiter = $delimiter;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel(int $level)
    {
        $this->level = $level;
    }

    /**
     * @return int
     */
    public function getSubfolders(): int
    {
        return $this->subfolders;
    }

    /**
     * @param int $subfolders
     */
    public function setSubfolders(int $subfolders)
    {
        $this->subfolders = $subfolders;
    }


}