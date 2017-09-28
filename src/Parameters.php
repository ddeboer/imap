<?php

declare(strict_types=1);

namespace Ddeboer\Imap;

class Parameters
{
    protected $parameters = [];

    public function __construct(array $parameters = [])
    {
        $this->add($parameters);
    }

    public function add(array $parameters = [])
    {
        foreach ($parameters as $parameter) {
            $key = strtolower($parameter->attribute);
            $value = $this->decode($parameter->value);
            $this->parameters[$key] = $value;
        }
    }

    public function get(string $key)
    {
        if (isset($this->parameters[$key])) {
            return $this->parameters[$key];
        }

        return;
    }

    protected function decode(string $value): string
    {
        $decoded = '';
        $parts = imap_mime_header_decode($value);
        foreach ($parts as $part) {
            $text = $part->text;
            if ('default' !== $part->charset) {
                $text = Message\Transcoder::decode($text, $part->charset);
            }

            $decoded .= $text;
        }

        return $decoded;
    }
}
