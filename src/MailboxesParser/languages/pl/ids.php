<?php

declare(strict_types=1);

/*
 * Created by PhpStorm.
 * User: Lukasz
 * Date: 2017-11-10
 * Time: 20:01
 */

use Ddeboer\Imap\MailboxesParser\MailboxesParser;

return [
    MailboxesParser::INBOX => ['INBOX.odebrane', 'odebrane'],
    MailboxesParser::SENT => ['INBOX.Wysłane', 'elementy wysłane', 'wysłane'],
    MailboxesParser::DRAFT => ['szkice'],
    MailboxesParser::SPAM => [],
    MailboxesParser::TRASH => ['kosz'],
    MailboxesParser::TEMPLATES => ['szablony'],
    MailboxesParser::ARCHIVES => ['archiwum'],
];
