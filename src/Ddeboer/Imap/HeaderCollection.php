<?php

namespace Ddeboer\Imap;

class HeaderCollection implements \Iterator
{
    protected $array = array();

    public function __construct(\stdClass $headers)
    {
        // Store all headers as lowercase
        $this->array = array_change_key_case((array) $headers);

        $this->array['draft'] = (bool) $this->array['draft'];
        $this->array['date'] = new \DateTime($this->array['date']);
        $this->array['msgno'] = (int) $this->array['msgno'];

        $from = current($this->array['from']);
        $this->array['from'] = new EmailAddress($from->mailbox, $from->host, $from->personal);

        $recipients = array();
        foreach ($this->array['to'] as $to) {
            $recipients[] = new EmailAddress($to->mailbox, $to->host, $to->personal);
        }
        $this->array['to'] = $recipients;

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