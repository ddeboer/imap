IMAP library
============

Receive and send e-mails over IMAP.

Reading e-mail
--------------

```php
$server = new \Ddeboer\Imap\Server('mail.myserver.com');

// Retrieve \Ddeboer\Imap\Connection as $connection:
$connection = $server->authenticate('my_username', 'my_password');

// Retrieve mailboxes (also known as mail folders) from mailserver
$mailboxes = $connection->getMailboxes();

// Iterate over all mailboxes and get all messages in each mailbox
foreach ($mailboxes as $mailbox) {
    // $mailbox is instance of \Ddeboer\Imap\Mailbox
    printf('Mailbox %s has %s messages', $mailbox->getName(), $mailbox->count());

    // Retrieve all messages from this mailbox
    $messages = $mailbox->getMessages();

    foreach ($messages as $message) {
        // $message is instance of \Ddeboer\Imap\Message

        // Get message number in mailbox:
        $message->getNumber();

        // Get unique [message id](http://en.wikipedia.org/wiki/Message-ID) in the form <...>:
        $message->getId();

        // Get message details:
        $message->getSubject();
        $message->getFrom();
        $message->getTo();
        $message->getDate();
        $message->isAnswered();
        $message->isDeleted();
        $message->isDraft();

        // Get message headers as \Ddeboer\Imap\Message\Headers object:
        $message->getHeaders();

        // Get message body as HTML or plain text:
        $message->getBodyHtml();
        $message->getBodyText();

        // Get message attachments:
        foreach ($message->getAttachments() as $attachment) {
            // $attachment is instance of \Ddeboer\Imap\Message\Attachment

            // Download the message attachment. Use getDecodedContent() to
            // decode the contents automatically.
            \file_put_contents(
                '/my/local/dir/' . $attachment->getFilename(),
                $attachment->getDecodedContent()
            );
        }
    }
}
```