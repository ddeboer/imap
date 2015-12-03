<?php

namespace Ddeboer\Imap;

use Ddeboer\Imap\Exception\MessageDoesNotExistException;
use Ddeboer\Imap\Message\EmailAddress;
use Ddeboer\Imap\Exception\MessageDeleteException;
use Ddeboer\Imap\Exception\MessageMoveException;

/**
 * An IMAP message (e-mail)
 */
class Message extends Message\Part
{
    private $headers;
    private $attachments;

    /**
     * @var boolean
     */
    private $keepUnseen = false;

    /**
     * Constructor
     *
     * @param resource $stream        IMAP stream
     * @param int      $messageNumber Message number
     */
    public function __construct($stream, $messageNumber)
    {
        $this->stream = $stream;
        $this->messageNumber = $messageNumber;

        $this->loadStructure();
    }

    /**
     * Get message id
     *
     * A unique message id in the form <...>
     *
     * @return string
     */
    public function getId()
    {
        return $this->getHeaders()->get('message_id');
    }

    /**
     * Get message sender (from headers)
     *
     * @return EmailAddress
     */
    public function getFrom()
    {
        return $this->getHeaders()->get('from');
    }

    /**
     * Get To recipients
     *
     * @return EmailAddress[] Empty array in case message has no To: recipients
     */
    public function getTo()
    {
        return $this->getHeaders()->get('to') ?: [];
    }

    /**
     * Get Cc recipients
     *
     * @return EmailAddress[] Empty array in case message has no CC: recipients
     */
    public function getCc()
    {
        return $this->getHeaders()->get('cc') ?: [];
    }

    /**
     * Get message number (from headers)
     *
     * @return int
     */
    public function getNumber()
    {
        return $this->messageNumber;
    }

    /**
     * Get date (from headers)
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->getHeaders()->get('date');
    }

    /**
     * Get message size (from headers)
     *
     * @return int
     */
    public function getSize()
    {
        return $this->getHeaders()->get('size');
    }

    /**
     * Get raw part content
     *
     * @return string
     */
    public function getContent($keepUnseen = false)
    {
        // Null headers, so subsequent calls to getHeaders() will return
        // updated seen flag
        $this->headers = null;

        return $this->doGetContent($this->keepUnseen ? $this->keepUnseen : $keepUnseen);
    }

    /**
     * Get message answered flag value (from headers)
     *
     * @return boolean
     */
    public function isAnswered()
    {
        return $this->getHeaders()->get('answered');
    }

    /**
     * Get message deleted flag value (from headers)
     *
     * @return boolean
     */
    public function isDeleted()
    {
        return $this->getHeaders()->get('deleted');
    }

    /**
     * Get message draft flag value (from headers)
     *
     * @return boolean
     */
    public function isDraft()
    {
        return $this->getHeaders()->get('draft');
    }

    /**
     * Has the message been marked as read?
     *
     * @return boolean
     */
    public function isSeen()
    {
        return 'U' != $this->getHeaders()->get('unseen');
    }

    /**
     * Get message subject (from headers)
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->getHeaders()->get('subject');
    }

    /**
     * Get message headers
     *
     * @return Message\Headers
     */
    public function getHeaders()
    {
        if (null === $this->headers) {
            // imap_header is much faster than imap_fetchheader
            // imap_header returns only a subset of all mail headers,
            // but it does include the message flags.
            $headers = imap_header($this->stream, imap_msgno($this->stream, $this->messageNumber));
            $this->headers = new Message\Headers($headers);
        }

        return $this->headers;
    }

    /**
     * Get body HTML
     *
     * @return string | null Null if message has no HTML message part
     */
    public function getBodyHtml()
    {
        $iterator = new \RecursiveIteratorIterator($this, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $part) {
            if ($part->getSubtype() == 'HTML') {
                return $part->getDecodedContent($this->keepUnseen);
            }
        }
    }

    /**
     * Get body text
     *
     * @return string
     */
    public function getBodyText()
    {
        $iterator = new \RecursiveIteratorIterator($this, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $part) {
            if ($part->getSubtype() == 'PLAIN') {
                return $part->getDecodedContent($this->keepUnseen);
            }
        }

        // If message has no parts, return content of message itself.
        return $this->getDecodedContent($this->keepUnseen);
    }

    /**
     * Get attachments (if any) linked to this e-mail
     *
     * @return Message\Attachment[]
     */
    public function getAttachments()
    {
        if (null === $this->attachments) {
            $this->attachments = array();
            foreach ($this->getParts() as $part) {
                if ($part instanceof Message\Attachment) {
                    $this->attachments[] = $part;
                }
                if ($part->hasChildren()) {
                    foreach ($part->getParts() as $child_part) {
                        if ($child_part instanceof Message\Attachment) {
                            $this->attachments[] = $child_part;
                        }
                    }
                }
            }
        }

        return $this->attachments;
    }

    /**
     * Does this message have attachments?
     *
     * @return bool
     */
    public function hasAttachments()
    {
        return count($this->getAttachments()) > 0;
    }

    /**
     * Delete message
     *
     * @throws MessageDeleteException
     */
    public function delete()
    {
        // 'deleted' header changed, force to reload headers, would be better to set deleted flag to true on header
        $this->headers = null;

        if (!imap_delete($this->stream, $this->messageNumber, \FT_UID)) {
            throw new MessageDeleteException($this->messageNumber);
        }
    }

    /**
     * Move message to another mailbox
     * @param Mailbox $mailbox
     *
     * @throws MessageMoveException
     * @return Message
     */
    public function move(Mailbox $mailbox)
    {
        if (!imap_mail_move($this->stream, $this->messageNumber, $mailbox->getName(), \CP_UID)) {
            throw new MessageMoveException($this->messageNumber, $mailbox->getName());
        }

        return $this;
    }

    /**
     * Prevent the message from being marked as seen
     *
     * Defaults to true, so messages that are read will be still marked as unseen.
     *
     * @param bool $bool
     *
     * @return Message
     */
    public function keepUnseen($bool = true)
    {
        $this->keepUnseen = (bool) $bool;

        return $this;
    }

    /**
     * Load message structure
     */
    private function loadStructure()
    {
        set_error_handler(
            function ($nr, $error) {
                throw new MessageDoesNotExistException(
                    $this->messageNumber,
                    $error
                );
            }
        );

        $structure = imap_fetchstructure(
            $this->stream,
            $this->messageNumber,
            \FT_UID
        );

        restore_error_handler();

        $this->parseStructure($structure);
    }
}
