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

        if (isset($this->array['cc'])) {
            $recipients = array();
            foreach ($this->array['cc'] as $cc) {
                $recipients[] = new EmailAddress(
                    str_replace('\'', '', $cc->mailbox),
                    str_replace('\'', '', $cc->host),
                    isset($cc->personal) ? \imap_utf8($cc->personal) : null
                );
            }
            $this->array['cc'] = $recipients;
        } else {
            $this->array['cc'] = array();
        }

        if (isset($this->array['sender'])) {
            $senders = array();
            foreach ($this->array['sender'] as $sender) {
                $senders[] = new EmailAddress(
                    str_replace('\'', '', $sender->mailbox),
                    str_replace('\'', '', $sender->host),
                    isset($sender->personal) ? \imap_utf8($sender->personal) : null
                );
            }
            $this->array['sender'] = $senders;
        } else {
            $this->array['sender'] = array();
        }

        if (isset($this->array['reply_to'])) {
            $recipients = array();
            foreach ($this->array['reply_to'] as $reply_to) {
                $recipients[] = new EmailAddress(
                    str_replace('\'', '', $reply_to->mailbox),
                    str_replace('\'', '', $reply_to->host),
                    isset($reply_to->personal) ? \imap_utf8($reply_to->personal) : null
                );
            }
            $this->array['reply_to'] = $recipients;
        } else {
            $this->array['reply_to'] = array();
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
