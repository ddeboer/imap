<?php

namespace Ddeboer\Imap\Message;

class Headers
{
    protected $array = array();

    public function __construct(\stdClass $headers)
    {
        // Store all headers as lowercase
        $this->array = array_change_key_case((array) $headers);

        // Decode subject, as it may be UTF-8 encoded
        if (isset($headers->subject)) {
            $subject = '';
            foreach (\imap_mime_header_decode($headers->subject) as $part) {
                // $part->charset can also be 'default', i.e. plain US-ASCII
                $charset = $part->charset == 'default' ? 'auto' : $part->charset;
                $subject .= \mb_convert_encoding($part->text, 'UTF-8', $charset);
            }
            $this->array['subject'] = $subject;
        }

        $this->array['msgno'] = (int) $this->array['msgno'];

        foreach (array('answered', 'deleted', 'draft') as $flag) {
            $this->array[$flag] = (bool) trim($this->array[$flag]);
        }

        if (isset($this->array['date'])) {
            $this->array['date'] = preg_replace('/([^\(]*)\(.*\)/', '$1', $this->array['date']);
            $this->array['date'] = new \DateTime($this->array['date']);
        }

        if (isset($this->array['from'])) {
            $from = current($this->array['from']);
            $this->array['from'] = new EmailAddress(
                $from->mailbox,
                $from->host,
                isset($from->personal) ? \imap_utf8($from->personal) : null
            );
        }

        if (isset($this->array['to'])) {
            $recipients = array();
            foreach ($this->array['to'] as $to) {
                $recipients[] = new EmailAddress(
                    str_replace('\'', '', $to->mailbox),
                    str_replace('\'', '', $to->host),
                    isset($to->personal) ? \imap_utf8($to->personal) : null
                );
            }
            $this->array['to'] = $recipients;
        } else {
            $this->array['to'] = array();
        }
    }

    public function current()
    {
        return current($this->array);
    }

    public function key()
    {
        return key($this->array);
    }

    public function next()
    {
        return next($this->array);
    }

    public function rewind()
    {
        return rewind($this->array);
    }

    public function valid()
    {
        return valid($this->array);
    }

    public function get($key)
    {
        $key = strtolower($key);

        if (isset($this->array[$key])) {
            return $this->array[$key];
        }
    }
}
