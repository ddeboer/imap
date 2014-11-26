IMAP library
============
[![Build Status](https://travis-ci.org/ddeboer/imap.svg?branch=master)](https://travis-ci.org/ddeboer/imap)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ddeboer/imap/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ddeboer/imap/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/ddeboer/imap/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/ddeboer/imap/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/ddeboer/imap/v/stable.svg)](https://packagist.org/packages/ddeboer/imap) 

A PHP 5.3+ library to read and process e-mails over IMAP.

Installation
------------

The recommended way to install the IMAP library is through [Composer](http://getcomposer.org):

```bash
$ composer require jhonn921007/imap:@stable
```

Usage
-----

### Connect and authenticate

```php
use Ddeboer\Imap\Server;

$server = new Server('imap.gmail.com');

// $connection is instance of \Ddeboer\Imap\Connection
$connection = $server->authenticate('my_username', 'my_password');
```

### Mailboxes

Retrieve mailboxes (also known as mail folders) from mailserver and iterate
over them:

```php
$mailboxes = $connection->getMailboxes();

foreach ($mailboxes as $mailbox) {
    // $mailbox is instance of \Ddeboer\Imap\Mailbox
    printf('Mailbox %s has %s messages', $mailbox->getName(), $mailbox->count());
}
```

Delete a mailbox:

```php
$mailbox->delete();
```

### Messages

Retrieve messages (e-mails) from a mailbox and iterate over them:

```php
$messages = $mailbox->getMessages();

foreach ($messages as $message) {
    // $message is instance of \Ddeboer\Imap\Message
}
```

Get message number and unique [message id](http://en.wikipedia.org/wiki/Message-ID)
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

Reading the message body marks the message as seen. If you want to keep the
message unseen:

```php
$message->keepUnseen()->getBodyHtml();
```

Move a message to another mailbox:

```php
$mailbox = $connection->getMailbox('another-mailbox');
$message->move($mailbox);
```

Deleting messages:

```php
$mailbox->getMessage(1)->delete();
$mailbox->getMessage(2)->delete();
$mailbox->expunge();
```

### Message attachments

Get message attachments (both inline and attached) and iterate over them:

```php
$attachments = $message->getAttachments();

foreach ($attachments as $attachment) {
    // $attachment is instance of \Ddeboer\Imap\Message\Attachment
}
```

Download a message attachment to a local file:

```php
// getDecodedContent() decodes the attachments contents automatically:
\file_put_contents(
    '/my/local/dir/' . $attachment->getFilename(),
    $attachment->getDecodedContent()
);
```

Running the tests
-----------------

This library is functionally tested on [Travis CI](https://travis-ci.org/ddeboer/imap)
against the Gmail IMAP server.

If you have your own Gmail (test) account, you can run the tests locally:

```bash
$ composer install --dev
$ export EMAIL_USERNAME="your_gmail_username"
$ export EMAIL_PASSWORD="your_gmail_password"
$ vendor/bin/phpunit
```

