<?php

namespace App\Modules\Reports\Production\Model;

class ProductionModelCollection implements \Countable, \Iterator
{
    private $productions = [];
    private $index = 0;

    public function count(): int
    {
        return count($this->productions);
    }

    public function add(ProductionModel $productionModel): self
    {
        $this->productions[] = $productionModel;
        return $this;
    }

    public function current(): ?ProductionModel
    {
        return $this->productions[$this->index];
    }

    public function next(): void
    {
        ++$this->index;
    }

    public function key(): int
    {
        return $this->index;
    }

    public function valid(): bool
    {
        return isset($this->productions[$this->index]);
    }

    public function rewind(): void
    {
        $this->index = 0;
    }
}