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
     * Get sender
     *
     * @return EmailAddress
     */
    public function getFrom()
    {
        return $this->getHeaders()->get('from');
    }

    /**
     * Get recipients
     *
     * @return EmailAddress[]
     */
    public function getTo()
    {
        return $this->getHeaders()->get('to');
    }

    /**
     * Get message number
     *
     * @return int
     */
    public function getNumber()
    {
        return $this->getHeaders()->get('msgno');
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->getHeaders()->get('date');
    }

    public function getSize()
    {
        return $this->getHeaders()->get('size');
    }

    public function isAnswered()
    {
        return $this->getHeaders()->get('answered');
    }

    public function isDeleted()
    {
        return $this->getHeaders()->get('deleted');
    }

    public function isDraft()
    {
        return $this->getHeaders()->get('draft');
    }

    public function getSubject()
    {
        return $this->getHeaders()->get('subject');
    }

    /**
     *
     * @return HeaderCollection
     */
    public function getHeaders()
    {
        if (null === $this->headers) {
            $this->headers = new HeaderCollection(\imap_header($this->stream, $this->number));
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