<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\glob;

use froq\common\interface\Arrayable;

/**
 * Glob class for files & directories.
 *
 * @package froq\file\glob
 * @class   froq\file\glob\Glob
 * @author  Kerem Güneş
 * @since   6.1
 */
class Glob implements Arrayable, \Countable, \IteratorAggregate, \ArrayAccess
{
    /** Internal iterator object. */
    private \Iterator $iterator;

    /**
     * Constructor.
     *
     * @param  string      $pattern
     * @param  int         $flags
     * @param  string|null $fileClass
     * @param  string|null $infoClass
     * @throws froq\file\glob\GlobException
     */
    public function __construct(string $pattern, int $flags = 0, string $fileClass = null, string $infoClass = null)
    {
        $this->iterator = new \AppendIterator();

        try {
            // Since GlobIterator keeps paths as keys, using
            // here ArrayIterator to change keys to int keys.
            $iterator = new \ArrayIterator();

            $globator = new \GlobIterator($pattern, $flags);
            $infoClass && $globator->setInfoClass($infoClass);

            /** @var SplFileInfo $info */
            foreach ($globator as $info) {
                $fileClass && $info->setFileClass($fileClass);
                $iterator[] = $info;
            }

            $this->iterator->append($iterator);
        } catch (\Throwable $e) {
            throw new GlobException($e, extract: true);
        }
    }

    /**
     * Get an item.
     *
     * @param  int $index
     * @return mixed
     */
    public function get(int $index): mixed
    {
        return $this->getArrayIterator()[$index] ?? null;
    }

    /**
     * Get first item.
     *
     * @return mixed
     */
    public function getFirst(): mixed
    {
        return $this->get(0);
    }

    /**
     * Get last item.
     *
     * @return mixed
     */
    public function getLast(): mixed
    {
        return $this->get($this->count() - 1);
    }

    /**
     * Call a function for each item.
     *
     * @param  callable $func
     * @return void
     */
    public function each(callable $func): void
    {
        foreach ($this->iterator as $item) {
            $func($item);
        }
    }

    /**
     * Apply a filter function for each item.
     *
     * @param  callable $func
     * @return self
     */
    public function filter(callable $func): self
    {
        $iterator = new \ArrayIterator();

        foreach ($this->iterator as $item) {
            $func($item) && $iterator[] = $item;
        }

        return $this->updateIterator($iterator);
    }

    /**
     * Apply a map function for each item.
     *
     * @param  callable $func
     * @return self
     */
    public function map(callable $func): self
    {
        $iterator = new \ArrayIterator();

        foreach ($this->iterator as $item) {
            $iterator[] = $func($item);
        }

        return $this->updateIterator($iterator);
    }

    /**
     * Apply a reduce function for each item.
     *
     * @param  mixed    $carry
     * @param  callable $func
     * @return mixed
     */
    public function reduce(mixed $carry, callable $func): mixed
    {
        return array_reduce($this->toArray(), $func, $carry);
    }

    /**
     * Reverse items.
     *
     * @return self
     */
    public function reverse(): self
    {
        $iterator = new \ArrayIterator();

        for ($i = $this->count() - 1; $i > -1; $i--) {
            $iterator[] = $this->get($i);
        }

        return $this->updateIterator($iterator);
    }

    /**
     * Sort items.
     *
     * @param  callable $func
     * @return self
     */
    public function sort(callable $func): self
    {
        $sorted = $this->getArrayIterator();
        $sorted->uasort($func);

        $iterator = new \ArrayIterator();

        foreach ($sorted as $item) {
            $iterator[] = $item;
        }

        return $this->updateIterator($iterator);
    }

    /**
     * @inheritDoc froq\common\interface\Arrayable
     */
    public function toArray(): array
    {
        return $this->getArrayIterator()->getArrayCopy();

        // @cancel: Above is faster.
        // return iterator_to_array($this->iterator);
    }

    /**
     * Get items in an XArray container.
     *
     * @return XArray
     */
    public function toXArray(): \XArray
    {
        return new \XArray($this->iterator);
    }

    /**
     * @inheritDoc Countable
     */
    public function count(): int
    {
        return $this->getArrayIterator()->count();

        // @cancel: Above is faster.
        // return iterator_count($this->iterator);
    }

    /**
     * @inheritDoc IteratorAggregate
     */
    public function getIterator(): \Iterator
    {
        return $this->iterator;
    }

    /**
     * Return first iterator.
     *
     * @return ArrayIterator
     */
    public function getArrayIterator(): \ArrayIterator
    {
        return $this->iterator->getArrayIterator()[0]
            ?? new \ArrayIterator(); // Fallback.
    }

    /**
     * @inheritDoc ArrayAccess
     */
    public function offsetExists(mixed $index): bool
    {
        return $this->get($index) !== null;
    }

    /**
     * @inheritDoc ArrayAccess
     */
    public function offsetGet(mixed $index): mixed
    {
        return $this->get($index);
    }

    /**
     * @inheritDoc ArrayAccess
     * @throws     ReadonlyError
     */
    public function offsetSet(mixed $index, mixed $_): never
    {
        throw new \ReadonlyError($this);
    }

    /**
     * @inheritDoc ArrayAccess
     * @throws     ReadonlyError
     */
    public function offsetUnset(mixed $index): never
    {
        throw new \ReadonlyError($this);
    }

    /**
     * Method for modifier methods such as `sort()` etc.
     * to create and update (overwrite) internal iterator.
     */
    private function updateIterator(\ArrayIterator $iterator): self
    {
        $this->iterator = new \AppendIterator();
        $this->iterator->append($iterator);

        return $this;
    }
}
