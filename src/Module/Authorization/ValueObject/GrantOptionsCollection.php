<?php

namespace App\Module\Authorization\ValueObject;

class GrantOptionsCollection implements \Iterator
{

    private array $options;
    private int $currentIndex = 0;
    public function __construct(GrantOption ...$options)
    {
        $this->options = $options;
    }

    public function current(): GrantOption
    {
        return $this->options[$this->currentIndex];
    }

    public function next(): void
    {
        $this->currentIndex++;
    }

    public function key(): int
    {
        return $this->currentIndex;
    }

    public function valid(): bool
    {
        return isset($this->options[$this->currentIndex]);
    }

    public function rewind(): void
    {
        $this->currentIndex = 0;
    }
}
