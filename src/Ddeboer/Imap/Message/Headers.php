<?php

namespace Ddeboer\Imap\Message;

class Headers
{
    protected $array = array();

    public function __construct(\stdClass $headers)
    {
        $headers = $this->decodeHeaders($headers);

        // Store all headers as lowercase
        $this->array = array_change_key_case((array) $headers);

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

    private function decodeHeaders($header)
    {
        if (is_scalar($header)) {
            return \imap_utf8($header);

        } elseif (is_array($header) || $header instanceof \stdClass) {
            foreach ($header as $key => &$value) {
                $value = $this->decodeHeaders($value);
            }

            return $header;

        } else {
            $hint = is_object($header) ? get_class($header) : gettype($header);
            throw new \InvalidArgumentException("Invalid \$header argument. Expected scalar, array or \\stdClass instance, {$hint} given");
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
