<?php

namespace Ddeboer\Imap;

class Message extends Message\Part
{
    protected $stream;
    protected $id;
    protected $headers;
    protected $parts;
    protected $body;

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
     * Get message recipients (from headers)
     *
     * @return EmailAddress[]
     */
    public function getTo()
    {
        return $this->getHeaders()->get('to');
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
            // \imap_header is much faster than \imap_fetchheader
            // \imap_header returns only a subset of all mail headers,
            // but it does include the message flags.
            $headers = \imap_header($this->stream, $this->messageNumber);
            $this->headers = new Message\Headers($headers);
        }

        return $this->headers;
    }

    /**
     * Get an array of all parts for this message
     *
     * @return Message\Part[]
     */
    public function getParts()
    {
        return $this->parts;
    }

    public function getBodyHtml()
    {
        $parts = $this->getParts();

        return $parts[1]->getContent();
    }

    public function getBodyText()
    {
        $parts = $this->getParts();

        return $parts[0]->getContent();
    }

    protected function loadStructure()
    {
        $structure = \imap_fetchstructure($this->stream, $this->messageNumber);
        $this->parseStructure($structure);
    }
}