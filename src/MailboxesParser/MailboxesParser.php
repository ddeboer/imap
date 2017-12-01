<?php
/**
 * Created by PhpStorm.
 * User: Lukasz
 * Date: 2017-11-10
 * Time: 19:49
 */

namespace Ddeboer\Imap\MailboxesParser;


use Ddeboer\Imap\Exception\MailboxesParserException;
use Ddeboer\Imap\MailboxInterface;

/**
 * Class MailboxesParser
 * @package Ddeboer\Imap\MailboxesParser
 */
final class MailboxesParser implements MailboxesParserInterface
{
    /** @var MailboxInterface[] */
    protected $mailboxes;
    /** @var ParsedMailbox[] */
    protected $folders;
    protected $treeStructure;

    const INBOX = 'inbox';
    const SENT = 'sent';
    const DRAFT = 'drafts';
    const SPAM = 'spam';
    const TRASH = 'trash';
    const TEMPLATES = 'templates';
    const ARCHIVES = 'archives';

    protected $specialFoldersIds = [
        self::INBOX     => ['inbox',],
        self::SENT      => ['sent', 'sent messages', 'INBOX.Sent', '[Gmail]/Sent Mail',],
        self::DRAFT     => ['drafts', 'INBOX.Drafts', '[Gmail]/Drafts',],
        self::SPAM      => ['spam', 'INBOX.spam', '[Gmail]/spam'],
        self::TRASH     => ['trash', 'bin', 'INBOX.trash', '[Gmail]/trash'],
        self::TEMPLATES => ['templates'],
        self::ARCHIVES  => ['archives',],
    ];

    protected $specialFoldersNames = [
        self::DRAFT     => 'Drafts',
        self::INBOX     => 'Inbox',
        self::SENT      => 'Sent',
        self::SPAM      => 'Spam',
        self::TRASH     => 'Trash',
        self::TEMPLATES => 'Templates',
        self::ARCHIVES  => 'Archives',
    ];

    protected $specialFoldersOrder = [
        self::INBOX     => 1,
        self::SENT      => 2,
        self::DRAFT     => 3,
        self::TEMPLATES => 4,
        self::ARCHIVES  => 10000,
        self::SPAM      => 20000,
        self::TRASH     => 30000,
    ];

    /**
     * MailboxesTree constructor.
     *
     * @param MailboxInterface[] $mailboxes
     */
    public function __construct($mailboxes)
    {
        $this->mailboxes = $mailboxes;
    }

    /**
     * Set language for parser
     *
     * @param string $lang
     */
    public function setLanguage(string $lang)
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . 'names.php';
        if (!is_file($path)) {
            throw new  MailboxesParserException(\sprintf('File for language names %s does not exist', $path));
        }
        $names = require $path;
        $this->setSpecialFoldersNames($names);

        $path = __DIR__ . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . 'ids.php';
        if (!is_file($path)) {
            throw new  MailboxesParserException(\sprintf('File for language ids %s does not exist', $path));
        }
        $ids = require $path;
        foreach ($ids AS $specialFolder => $idsArray) {
            foreach ($idsArray AS $id) {
                $this->addSpecialFolderId($specialFolder, $id);
            }
        }
    }

    /**
     * @return ParsedMailbox[]
     */
    protected function parse(): array
    {
        $this->folders = [];
        usort($this->mailboxes, [$this, "sortByMailboxName"]);
        foreach ($this->mailboxes AS $k => $mailbox) {
            $mailboxName = $mailbox->getName();
            $folder = new ParsedMailbox();
            $folder->setMailbox($mailbox);
            $folder->setMailboxName($mailboxName);
            $special = $this->getSpecialFolder($mailboxName);
            $folder->setSpecial($special);
            $folder->setName($special ? $this->specialFoldersNames[$special] : $this->getName($mailboxName, $mailbox->getDelimiter()));
            $folder->setOrder($special ? $this->specialFoldersOrder[$special] : $this->getOrder($mailboxName, $mailbox->getDelimiter()));
            $folder->setLevel($this->getFolderLevel($mailboxName, $mailbox->getDelimiter()));
            $folder->setDelimiter($mailbox->getDelimiter());
            $this->folders[] = $folder;
        }

        usort($this->folders, [$this, "sortByOrder"]);

        return $this->folders;
    }

    /**
     * @param MailboxInterface $a
     * @param MailboxInterface $b
     *
     * @return int
     */
    protected function sortByMailboxName(MailboxInterface $a, MailboxInterface $b): int
    {
        return ($a->getName() <=> $b->getName());
    }

    /**
     * @param ParsedMailbox $a
     * @param ParsedMailbox $b
     *
     * @return int
     */
    protected function sortByOrder(ParsedMailbox $a, ParsedMailbox $b): int
    {
        return ($a->getOrder() <=> $b->getOrder());
    }

    /**
     * @return ParsedMailbox[]
     */
    public function getFolders(): array
    {
        if (!$this->folders) {
            $this->parse();
        }

        return $this->folders;
    }

    /**
     * @return array
     */
    public function getTreeStructure(): array
    {
        if (!$this->treeStructure) {
            $treeParser = new MailboxesTreeParser();
            $this->treeStructure = $treeParser->parse($this->getFolders());
        }

        return $this->treeStructure;
    }

    /**
     * @param array $specialFoldersNames
     */
    public function setSpecialFoldersNames(array $specialFoldersNames)
    {
        $this->specialFoldersNames = $specialFoldersNames;
    }

    /**
     * @param $mailboxName
     *
     * @return int|null|string
     */
    protected function getSpecialFolder($mailboxName)
    {
        foreach ($this->specialFoldersIds AS $specialFolderKind => $names) {
            $lower = mb_strtolower($mailboxName);
            foreach ($names AS $name) {
                if ($lower === mb_strtolower($name)) {
                    return $specialFolderKind;
                }
            }
        }

        return null;
    }

    /**
     * @param        $mailboxName
     * @param string $delimiter
     *
     * @return string
     */
    private function getName($mailboxName, $delimiter = '.'): string
    {
        $e = explode($delimiter, $mailboxName);

        return ucfirst($e[count($e) - 1]);
    }

    /**
     * @param $mailboxName
     * @param $delimiter
     *
     * @return float|int|mixed
     */
    private function getOrder($mailboxName, $delimiter)
    {
        if ($this->folders[$mailboxName]['special']) {
            return $this->specialFoldersOrder[$this->folders[$mailboxName]['special']];
        } else {
            $e = explode($delimiter, $mailboxName);

            $level = count($e) - 1;
            if ($e[0] === 'INBOX' && $level === 1) {
                $level = -1;
            }
            if ($e[1]) {
                array_pop($e);
                $parentMailboxName = implode($delimiter, $e);
                if ($level === -1) {
                    $multiplier = 100;
                } else {
                    $power = -1 * ($level * 2);
                    $multiplier = pow(10, ($power));
                }
                $parsedParent = null;
                /** @var ParsedMailbox $parsedMailbox */
                foreach ($this->folders as $parsedMailbox) {
                    if ($parsedMailbox->getMailboxName() === $parentMailboxName) {
                        $parsedParent = $parsedMailbox;
                        break;
                    }
                }
                if ($parsedParent) {
                    $parsedParent->setSubfolders($parsedParent->getSubfolders() + 1);
                    $order = ($parsedParent->getOrder() + ($parsedParent->getSubfolders() * $multiplier));
                } else {
                    $order = 10;
                }
            } else {
                $order = 10;
            }

            return $order;
        }
    }

    /**
     * @param $special
     *
     * @return string|null
     */
    public function getMailboxNameForSpecial($special)
    {
        /** @var ParsedMailbox $parsedMailbox */
        foreach ($this->folders as $parsedMailbox) {
            if ($parsedMailbox->getSpecial() === $special) {
                return $parsedMailbox->getMailboxName();
            }
        }

        return null;
    }

    /**
     * @param string $specialFolder Name of special folder
     * @param string $id            Id for that special folder
     */
    public function addSpecialFolderId($specialFolder, $id)
    {
        $this->specialFoldersIds[$specialFolder][] = $id;
    }

    /**
     * @param $mailboxName
     * @param $delimiter
     *
     * @return int
     */
    private function getFolderLevel($mailboxName, $delimiter): int
    {
        $e = explode($delimiter, $mailboxName);

        return count($e);
    }
}