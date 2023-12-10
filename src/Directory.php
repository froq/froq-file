<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * Directory class for working with directories.
 *
 * @package froq\file
 * @class   froq\file\Directory
 * @author  Kerem Güneş
 * @since   7.0
 */
class Directory extends PathObject implements \Countable, \IteratorAggregate
{
    /** Default mode. */
    public const MODE = 0755;

    /** Stream handle. */
    private ?Stream $stream = null;

    /** Sort option. */
    private bool $sort = true;

    /**
     * @throws froq\file\DirectoryException
     * @override
     */
    public function __construct(string $path, array $options = null)
    {
        try {
            parent::__construct($path);
        } catch (\Throwable $e) {
            throw DirectoryException::exception($e);
        }

        if ($this->path->isFile()) {
            throw DirectoryException::forCannotUseAFile();
        }

        if ($options) {
            $this->sort = $options['sort'] ?? true;

            // Auto-open.
            if (!empty($options['open'])) {
                $this->open($options['open']);
            }
        }
    }

    /**
     * Open stream.
     *
     * @return self
     * @throws froq\file\DirectoryException
     */
    public function open(): self
    {
        if ($this->path->isFile()) {
            throw DirectoryException::forCannotOpenAFile();
        }

        $resource = @opendir($this->path->name) ?: throw DirectoryException::error();

        $this->stream = new Stream($resource);

        return $this;
    }

    /**
     * Close stream.
     *
     * @return bool
     */
    public function close(): bool
    {
        return (bool) $this->stream?->close();
    }

    /**
     * Validate stream.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return (bool) $this->stream?->valid();
    }

    /**
     * Read.
     *
     * @param  callable|null $filter
     * @param  bool|null     $sort
     * @return ArrayIterator<string>
     * @causes froq\file\DirectoryException
     */
    public function read(callable $filter = null, bool $sort = null): \ArrayIterator
    {
        $this->valid() || $this->open();

        $tmp = new \ArrayIterator();

        while (false !== ($entry = @readdir($this->stream->resource()))) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if ($filter && !$filter($entry)) {
                continue;
            }

            $tmp[] = $this->fullpath($entry);
        }

        // Must rewind for next call.
        @rewinddir($this->stream->resource());

        $sort ??= $this->sort;

        // No sort.
        if (!$sort) {
            return $tmp;
        }

        // Because they are not sorted.
        $tmp->uasort(function ($a, $b): int {
            // To move directories up.
            $x = +!is_dir($a); $y = +!is_dir($b);

            // If equal (0) do normal comparison.
            return ($x - $y) ?: strcasecmp($a, $b);
        });

        $ret = new \ArrayIterator();

        // Fix keys after sort.
        foreach ($tmp as $path) {
            $ret[] = $path;
        }

        return $ret;
    }

    /**
     * Rewind.
     *
     * @return void
     * @causes froq\file\DirectoryException
     */
    public function rewind(): void
    {
        $this->valid() || $this->open();

        @rewinddir($this->stream->resource());
    }

    /**
     * Check whether if this directory has subdirectories.
     *
     * @param  callable|null $filter
     * @return bool
     * @causes froq\file\DirectoryException
     */
    public function hasDirectories(callable $filter = null): bool
    {
        foreach ($this->read($filter, false) as $path) {
            if (is_dir($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all subdirectories of this directory if any.
     *
     * @param  callable|null $filter
     * @return bool|null     $sort
     * @return ArrayIterator<froq\file\Directory>
     * @causes froq\file\DirectoryException
     */
    public function getDirectories(callable $filter = null, bool $sort = null): \ArrayIterator
    {
        $ret = new \ArrayIterator();

        foreach ($this->read($filter, $sort) as $path) {
            if (is_dir($path)) {
                $ret[] = new Directory($path, ['sort' => $sort ?? $this->sort]);
            }
        }

        return $ret;
    }

    /**
     * Get all subdirectory names of this directory if any.
     *
     * @param  callable|null $filter
     * @return bool|null     $sort
     * @return ArrayIterator<string>
     * @causes froq\file\DirectoryException
     */
    public function getDirectoryNames(callable $filter = null, bool $sort = null): \ArrayIterator
    {
        $ret = new \ArrayIterator();

        foreach ($this->read($filter, $sort) as $path) {
            if (is_dir($path)) {
                $ret[] = $path;
            }
        }

        return $ret;
    }

    /**
     * Check whether if this directory has files.
     *
     * @param  callable|null $filter
     * @return bool
     * @causes froq\file\DirectoryException
     */
    public function hasFiles(callable $filter = null): bool
    {
        foreach ($this->read($filter, false) as $path) {
            if (is_file($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all files of this directory if any.
     *
     * @param  callable|null $filter
     * @return bool|null     $sort
     * @return ArrayIterator<froq\file\Directory>
     * @causes froq\file\DirectoryException
     */
    public function getFiles(callable $filter = null, bool $sort = null): \ArrayIterator
    {
        $ret = new \ArrayIterator();

        foreach ($this->read($filter, $sort) as $path) {
            if (is_file($path)) {
                $ret[] = new File($path);
            }
        }

        return $ret;
    }

    /**
     * Get all file names of this directory if any.
     *
     * @param  callable|null $filter
     * @return bool|null     $sort
     * @return ArrayIterator<string>
     * @causes froq\file\DirectoryException
     */
    public function getFileNames(callable $filter = null, bool $sort = null): \ArrayIterator
    {
        $ret = new \ArrayIterator();

        foreach ($this->read($filter, $sort) as $path) {
            if (is_file($path)) {
                $ret[] = $path;
            }
        }

        return $ret;
    }

    /**
     * @alias hasDirectories()
     */
    public function hasChildren(...$args)
    {
        return $this->hasDirectories(...$args);
    }

    /**
     * @alias getDirectories()
     */
    public function getChildren(...$args)
    {
        return $this->getDirectories(...$args);
    }

    /**
     * @alias getDirectoryNames()
     */
    public function getChildrenNames(...$args)
    {
        return $this->getDirectoryNames(...$args);
    }

    /**
     * @inheritDoc Countable
     */
    public function count(): int
    {
        return $this->read(sort: false)->count();
    }

    /**
     * @inheritDoc IteratorAggregate
     */
    public function getIterator(): \Iterator
    {
        return $this->read(sort: true);
    }

    /**
     * @internal
     */
    private function fullpath(string $entry): string
    {
        return $this->path->name . DIRECTORY_SEPARATOR . $entry;
    }
}
