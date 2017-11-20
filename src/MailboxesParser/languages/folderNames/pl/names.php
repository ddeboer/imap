<?php
declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Lukasz
 * Date: 2017-11-10
 * Time: 20:01
 */

use Ddeboer\Imap\MailboxesParser\MailboxesParser;

return [
    MailboxesParser::INBOX     => 'Odebrane',
    MailboxesParser::SENT      => 'Wysłane',
    MailboxesParser::DRAFT     => 'Szkice',
    MailboxesParser::SPAM      => 'Spam',
    MailboxesParser::TRASH     => 'Kosz',
    MailboxesParser::TEMPLATES => 'Szablony',
    MailboxesParser::ARCHIVES  => 'Archiwum',
];