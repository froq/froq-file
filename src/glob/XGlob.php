<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\glob;

use froq\common\interface\Arrayable;
use froq\file\Finder;

/**
 * X-Glob class for files & directories.
 *
 * @package froq\file\glob
 * @class   froq\file\glob\XGlob
 * @author  Kerem Güneş
 * @since   7.10
 */
class XGlob implements Arrayable, \Countable, \IteratorAggregate, \ArrayAccess
{
    /** Internal iterator object. */
    private \XArray $iterator;

    /**
     * Constructor.
     *
     * @param  string      $pattern
     * @param  int         $flags
     * @param  bool        $map
     * @param  bool        $list
     * @throws froq\file\glob\XGlobException
     */
    public function __construct(string $pattern, int $flags = 0, bool $map = true, bool $list = true)
    {
        $this->iterator = new \XArray();

        try {
            $root = getcwd() . DIRECTORY_SEPARATOR;
            if (str_contains($pattern, DIRECTORY_SEPARATOR)) {
                $root = dirname($pattern);
                $pattern = strsub($pattern, strlen($root));
            }

            $finder = new Finder($root);

            $this->iterator->update(
                $finder->xglob($pattern, $flags, $map, $list),
                merge: false
            );
        } catch (\Throwable $e) {
            throw new XGlobException($e);
        }
    }

    /**
     * @magic
     */
    public function __clone()
    {
        $this->iterator = clone $this->iterator;
    }

    /**
     * Get an item.
     *
     * @param  int $index
     * @return mixed
     */
    public function get(int $index): mixed
    {
        return $this->iterator->get($index);
    }

    /**
     * Get first item.
     *
     * @return mixed
     */
    public function getFirst(): mixed
    {
        return $this->iterator->first();
    }

    /**
     * Get last item.
     *
     * @return mixed
     */
    public function getLast(): mixed
    {
        return $this->iterator->last();
    }

    /**
     * Call a function for each item.
     *
     * @param  callable $func
     * @return void
     */
    public function each(callable $func): void
    {
        $this->iterator->each($func);
    }

    /**
     * Apply a filter function for each item.
     *
     * @param  callable $func
     * @return self
     */
    public function filter(callable $func): self
    {
        $this->iterator->filter($func);

        return $this;
    }

    /**
     * Apply a map function for each item.
     *
     * @param  callable $func
     * @return self
     */
    public function map(callable $func): self
    {
        $this->iterator->map($func);

        return $this;
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
        return $this->iterator->reduce($carry, $func);
    }

    /**
     * Reverse items.
     *
     * @return self
     */
    public function reverse(): self
    {
        $this->iterator->reverse();

        return $this;
    }

    /**
     * Sort items.
     *
     * @param  callable $func
     * @return self
     */
    public function sort(callable $func): self
    {
        $this->iterator->sort($func);

        return $this;
    }

    /**
     * @inheritDoc froq\common\interface\Arrayable
     */
    public function toArray(): array
    {
        return $this->iterator->toArray();
    }

    /**
     * Get items in an XArray container.
     *
     * @return XArray
     */
    public function toXArray(): \XArray
    {
        return clone $this->iterator;
    }

    /**
     * @inheritDoc Countable
     */
    public function count(): int
    {
        return $this->iterator->count();
    }

    /**
     * @inheritDoc IteratorAggregate
     */
    public function getIterator(): \Iterator
    {
        return $this->iterator->getIterator();
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
}
