<?php

namespace LaveroAdmin\Admin\Reporter\Tracer;

class Parser implements \Iterator
{
    protected $trace = '';

    protected $frames = [];

    public function __construct($trace)
    {
        $this->trace = $trace;
    }

    public function parse()
    {
        $frames = explode("\n", $this->trace);
        $this->frames = array_map(function ($frame) {
            return new Frame($frame);
        }, $frames);

        return $this->frames;
    }

    #[\ReturnTypeWillChange]
    public function current(): mixed
    {
        return current($this->frames);
    }

    #[\ReturnTypeWillChange]
    public function next(): void
    {
        next($this->frames);
    }

    #[\ReturnTypeWillChange]
    public function key(): mixed
    {
        return key($this->frames);
    }

    #[\ReturnTypeWillChange]
    public function valid(): bool
    {
        return key($this->frames) !== null;
    }

    #[\ReturnTypeWillChange]
    public function rewind(): void
    {
        reset($this->frames);
    }
}
