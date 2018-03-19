<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Message;

class Parameters extends \ArrayIterator
{
    /**
     * @param array $parameters
     */
    public function __construct(array $parameters = [])
    {
        parent::__construct();

        $this->add($parameters);
    }

    /**
     * @param array $parameters
     */
    public function add(array $parameters = [])
    {
        foreach ($parameters as $parameter) {
            $key = \strtolower($parameter->attribute);
            $value = $this->decode($parameter->value);
            $this[$key] = $value;
        }
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key)
    {
        return $this[$key] ?? null;
    }

    /**
     * Decode value.
     *
     * @param string $value
     *
     * @return string
     */
    final protected function decode(string $value): string
    {
        $parts = \imap_mime_header_decode($value);
        if (!\is_array($parts)) {
            return $value;
        }

        $decoded = '';
        foreach ($parts as $part) {
            $text = $part->text;
            if ('default' !== $part->charset) {
                $text = Transcoder::decode($text, $part->charset);
            }

            $decoded .= $text;
        }

        return $decoded;
    }
}
