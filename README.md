IMAP library
============

[![Build Status](https://travis-ci.org/ddeboer/imap.svg?branch=master)](https://travis-ci.org/ddeboer/imap)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ddeboer/imap/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ddeboer/imap/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/ddeboer/imap/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/ddeboer/imap/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/ddeboer/imap/v/stable.svg)](https://packagist.org/packages/ddeboer/imap)
[![Total Downloads](https://poser.pugx.org/ddeboer/imap/downloads.png)](https://packagist.org/packages/ddeboer/imap)

A PHP 7.0+ library to read and process e-mails over IMAP.

This library requires both [IMAP](https://secure.php.net/manual/en/book.imap.php) and [Multibyte String](https://secure.php.net/manual/en/book.mbstring.php) extensions installed.

Installation
------------

The recommended way to install the IMAP library is through [Composer](https://getcomposer.org):

```bash
$ composer require ddeboer/imap
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Usage
-----

### Connect and Authenticate

```php
use Ddeboer\Imap\Server;

$server = new Server('imap.gmail.com');

// $connection is instance of \Ddeboer\Imap\Connection
$connection = $server->authenticate('my_username', 'my_password');
```

#### Options

You can specify port, [flags and parameters](https://secure.php.net/manual/en/function.imap-open.php)
to the server:

```php
$server = new Server(
    $hostname, // required
    $port,     // defaults to '993'
    $flags,    // defaults to '/imap/ssl/validate-cert'
    $parameters
);
```

### Mailboxes

Retrieve mailboxes (also known as mail folders) from the mail server and iterate
over them:

```php
$mailboxes = $connection->getMailboxes();

foreach ($mailboxes as $mailbox) {
    // Skip container-only mailboxes
    // @see https://secure.php.net/manual/en/function.imap-getmailboxes.php
    if ($mailbox->getAttributes() & \LATT_NOSELECT) {
        continue;
    }

    // $mailbox is instance of \Ddeboer\Imap\Mailbox
    printf('Mailbox "%s" has %s messages', $mailbox->getName(), $mailbox->count());
}
```

Or retrieve a specific mailbox:

```php
$mailbox = $connection->getMailbox('INBOX');
```

Delete a mailbox:

```php
$connection->deleteMailbox($mailbox);
```

You can bulk set, or clear, any [flag](https://secure.php.net/manual/en/function.imap-setflag-full.php) of mailbox messages (by UIDs):

```php
$mailbox->setFlag('\\Seen \\Flagged', ['1:5', '7', '9']);
$mailbox->setFlag('\\Seen', '1,3,5,6:8');

$mailbox->clearFlag('\\Flagged', '1,3');
```

**WARNING** You must retrieve new Message instances in case of bulk modify flags to refresh the single Messages flags.

### Messages

Retrieve messages (e-mails) from a mailbox and iterate over them:

```php
$messages = $mailbox->getMessages();

foreach ($messages as $message) {
    // $message is instance of \Ddeboer\Imap\Message
}
```

#### Searching for Messages

```php
use Ddeboer\Imap\SearchExpression;
use Ddeboer\Imap\Search\Email\To;
use Ddeboer\Imap\Search\Text\Body;

$search = new SearchExpression();
$search
    ->addCondition(new To('me@here.com'))
    ->addCondition(new Body('contents'))
;

$messages = $mailbox->getMessages($search);
```

**WARNING** We are currently unable to have both spaces _and_ double-quotes
escaped together. Only spaces are currently escaped correctly.
You can use `Ddeboer\Imap\Search\RawExpression` to write the complete search
condition by yourself.

**WARNING** `OR` condition has never been reported as correctly functioning.

##### Ordered search

Reference: [imap_sort](https://secure.php.net/manual/en/function.imap-sort.php)

```php
$messages = $mailbox->getMessages(
    new Ddeboer\Imap\Search\LogicalOperator\All(),
    \SORTDATE, // Sort criteria
    true // Descending order
);
```

#### Message Properties and Operations

Get message number and unique [message id](https://en.wikipedia.org/wiki/Message-ID)
in the form <...>:

```php
$message->getNumber();
$message->getId();
```

Get other message properties:

```php
$message->getSubject();
$message->getFrom();
$message->getTo();
$message->getDate();
$message->isAnswered();
$message->isDeleted();
$message->isDraft();
$message->isSeen();
```

Get message headers as a [\Ddeboer\Imap\Message\Headers](/src/Ddeboer/Imap/Message/Headers.php) object:

```php
$message->getHeaders();
```

Get message body as HTML or plain text:

```php
$message->getBodyHtml();
$message->getBodyText();
```

Reading the message body keeps the message as unseen.
If you want to mark the message as seen:

```php
$message->maskAsSeen();
```

Or you can set, or clear, any [flag](https://secure.php.net/manual/en/function.imap-setflag-full.php):

```php
$message->setFlag('\\Seen \\Flagged');
$message->clearFlag('\\Flagged');
```

Move a message to another mailbox:

```php
$mailbox = $connection->getMailbox('another-mailbox');
$message->move($mailbox);
$connection->expunge();
```

Deleting messages:

```php
$mailbox->getMessage(1)->delete();
$mailbox->getMessage(2)->delete();
$connection->expunge();
```

### Message Attachments

Get message attachments (both inline and attached) and iterate over them:

```php
$attachments = $message->getAttachments();

foreach ($attachments as $attachment) {
    // $attachment is instance of \Ddeboer\Imap\Message\Attachment
}
```

Download a message attachment to a local file:

```php
// getDecodedContent() decodes the attachment’s contents automatically:
file_put_contents(
    '/my/local/dir/' . $attachment->getFilename(),
    $attachment->getDecodedContent()
);
```


### Embedded Messages

Check if attachment is embedded message and get it:

```php
$attachments = $message->getAttachments();

foreach ($attachments as $attachment) {
    if ($attachment->isEmbeddedMessage()) {
        $embeddedMessage = $attachment->getEmbeddedMessage();
        // $embeddedMessage is instance of \Ddeboer\Imap\Message\EmbeddedMessage
    }
}
```

An EmbeddedMessage has the same API as a normal Message, apart from flags
and operations like copy, move or delete.

Running the Tests
-----------------

This library is functionally tested on [Travis CI](https://travis-ci.org/ddeboer/imap)
against a local Dovecot server.

If you have your own IMAP (test) account, you can run the tests locally by
providing your IMAP credentials:

```bash
$ composer install
$ IMAP_SERVER_NAME="my.imap.server.com" IMAP_SERVER_PORT="60993" IMAP_USERNAME="johndoe" IMAP_PASSWORD="p4ssword" vendor/bin/phpunit
```

You can also copy `phpunit.xml.dist` file to a custom `phpunit.xml` and put
these environment variables in it:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit
    bootstrap="./vendor/autoload.php"
    colors="true"
    verbose="true"
>
    <testsuites>
        <testsuite name="ddeboer/imap">
            <directory>./tests/</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="IMAP_SERVER_NAME" value="my.imap.server.com" />
        <env name="IMAP_SERVER_PORT" value="60993" />
        <env name="IMAP_USERNAME" value="johndoe" />
        <env name="IMAP_PASSWORD" value="p4ssword" />
    </php>
</phpunit>
```

**WARNING**: currently the tests create new mailboxes without removing them.
