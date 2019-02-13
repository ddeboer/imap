<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Lukasz
 * Date: 2017-12-01
 * Time: 10:22.
 */

namespace Ddeboer\Imap\MailboxesParser;

/**
 * Class MailboxesParser.
 */
interface MailboxesParserInterface
{
    /**
     * Set language for parser.
     *
     * @param string $lang
     */
    public function setLanguage(string $lang);

    /**
     * @return ParsedMailbox[]
     */
    public function getFolders(): array;

    /**
     * @return array
     */
    public function getTreeStructure(): array;

    /**
     * @param array $specialFoldersNames
     */
    public function setSpecialFoldersNames(array $specialFoldersNames);

    /**
     * @param $special
     *
     * @return null|string
     */
    public function getMailboxNameForSpecial($special);

    /**
     * @param string $specialFolder Name of special folder
     * @param string $id            Id for that special folder
     */
    public function addSpecialFolderId($specialFolder, $id);
}
