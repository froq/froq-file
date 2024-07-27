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
    /** Default make mode. */
    public const MODE = 0755;

    /** Stream handle. */
    private ?Stream $stream = null;

    /** Sort option. */
    private bool $sort = true;

    /**
     * @throws froq\file\DirectoryException
     * @override
     */
    public function __construct(string|Path $path, array $options = null)
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
                $this->open();
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
     * @return froq\file\DirectoryList<string>
     * @causes froq\file\DirectoryException
     */
    public function read(callable $filter = null, bool $sort = null): DirectoryList
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
            return new DirectoryList($tmp);
        }

        // Because they are not sorted.
        $tmp->uasort(function ($a, $b): int {
            // To move directories up.
            $x = +!is_dir($a); $y = +!is_dir($b);

            // If equal (0) do normal comparison.
            return ($x - $y) ?: strcasecmp($a, $b);
        });

        $ret = new DirectoryList();

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
     * Check if this directory has (sub)directories.
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
     * Get all (sub)directories of this directory if any.
     *
     * @param  callable|null $filter
     * @return bool|null     $sort
     * @return froq\file\DirectoryList<froq\file\Directory>
     * @causes froq\file\DirectoryException
     */
    public function getDirectories(callable $filter = null, bool $sort = null): DirectoryList
    {
        $ret = new DirectoryList();

        foreach ($this->read($filter, $sort) as $path) {
            if (is_dir($path)) {
                $ret[] = new Directory($path, ['sort' => $sort ?? $this->sort]);
            }
        }

        return $ret;
    }

    /**
     * Get all (sub)directory names of this directory if any.
     *
     * @param  callable|null $filter
     * @return bool|null     $sort
     * @return froq\file\DirectoryList<string>
     * @causes froq\file\DirectoryException
     */
    public function getDirectoryNames(callable $filter = null, bool $sort = null): DirectoryList
    {
        $ret = new DirectoryList();

        foreach ($this->read($filter, $sort) as $path) {
            if (is_dir($path)) {
                $ret[] = $path;
            }
        }

        return $ret;
    }

    /**
     * Check if this directory has files.
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
     * @return froq\file\FileList<froq\file\File>
     * @causes froq\file\DirectoryException
     */
    public function getFiles(callable $filter = null, bool $sort = null): FileList
    {
        $ret = new FileList();

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
     * @return froq\file\FileList<string>
     * @causes froq\file\DirectoryException
     */
    public function getFileNames(callable $filter = null, bool $sort = null): FileList
    {
        $ret = new FileList();

        foreach ($this->read($filter, $sort) as $path) {
            if (is_file($path)) {
                $ret[] = $path;
            }
        }

        return $ret;
    }

    /**
     * Check if this directory has links.
     *
     * @param  callable|null $filter
     * @return bool
     * @causes froq\file\DirectoryException
     */
    public function hasLinks(callable $filter = null): bool
    {
        foreach ($this->read($filter, false) as $path) {
            if (is_link($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all links of this directory if any.
     *
     * @param  callable|null $filter
     * @return bool|null     $sort
     * @return froq\file\LinkList<froq\file\Directory|froq\file\File>
     * @causes froq\file\DirectoryException
     */
    public function getLinks(callable $filter = null, bool $sort = null): LinkList
    {
        $ret = new LinkList();

        foreach ($this->read($filter, $sort) as $path) {
            if (is_link($path)) {
                if (is_dir($path)) {
                    $ret[] = new Directory($path);
                } elseif (is_file($path)) {
                    $ret[] = new File($path);
                }
            }
        }

        return $ret;
    }

    /**
     * Get all link names of this directory if any.
     *
     * @param  callable|null $filter
     * @return bool|null     $sort
     * @return froq\file\LinkList<string>
     * @causes froq\file\DirectoryException
     */
    public function getLinkNames(callable $filter = null, bool $sort = null): LinkList
    {
        $ret = new LinkList();

        foreach ($this->read($filter, $sort) as $path) {
            if (is_link($path)) {
                $ret[] = $path;
            }
        }

        return $ret;
    }

    /**
     * Check if this directory has a (sub)directory.
     *
     * @param  string $basename
     * @return bool
     */
    public function hasChild(string $basename): bool
    {
        $path = Path::of($this->path->name, $basename);

        return $path->isDirectory() && ($path->dirname === $this->path->name);
    }

    /**
     * Get a (sub)directory of this directory if any.
     *
     * @param  string     $basename
     * @param  array|null $options
     * @return froq\file\Directory|null
     */
    public function getChild(string $basename, array $options = null): Directory|null
    {
        $path = Path::of($this->path->name, $basename);

        return $path->isDirectory() && ($path->dirname === $this->path->name)
             ? $path->toDirectory($options) : null;
    }

    /**
     * Check if this directory has a file.
     *
     * @param  string $basename
     * @return bool
     */
    public function hasFile(string $basename): bool
    {
        $path = Path::of($this->path->name, $basename);

        return $path->isFile() && ($path->dirname === $this->path->name);
    }

    /**
     * Get a file of this directory if any.
     *
     * @param  string     $basename
     * @param  array|null $options
     * @return froq\file\File|null
     */
    public function getFile(string $basename, array $options = null): File|null
    {
        $path = Path::of($this->path->name, $basename);

        return $path->isFile() && ($path->dirname === $this->path->name)
             ? $path->toFile($options) : null;
    }

    /**
     * Check if this directory has a link.
     *
     * @param  string $basename
     * @return bool
     */
    public function hasLink(string $basename): bool
    {
        $path = Path::of($this->path->name, $basename);

        return $path->isLink();
    }

    /**
     * Get a link of this directory if any.
     *
     * @param  string     $basename
     * @param  array|null $options
     * @return froq\file\{Directory|File}|null
     */
    public function getLink(string $basename, array $options = null): Directory|File|null
    {
        $path = Path::of($this->path->name, $basename);

        return $path->isLink() ? match (true) {
            $path->isDirectory() => $path->toDirectory($options),
            $path->isFile()      => $path->toFile($options),
            default              => null
        } : null;
    }

    /**
     * Check if this directory has a sub-path.
     *
     * @param  string $pathname
     * @return bool
     */
    public function hasSubPath(string $pathname): bool
    {
        $path = Path::of($this->path->name, $pathname);

        return $path->exists();
    }

    /**
     * Get a sub-path of this directory if any.
     *
     * @param  string $pathname
     * @param  bool   $useRealPath
     * @return froq\file\Path|null
     */
    public function getSubPath(string $pathname, bool $useRealPath = false): Path|null
    {
        $path = Path::of($this->path->name, $pathname);
        $path->useRealPath = $useRealPath;

        return $path->exists() ? $path : null;
    }

    /**
     * @alias PathInfo.getParentDirectory()
     */
    public function hasParent(): bool
    {
        return $this->path->getParentDirectory() !== null;
    }

    /**
     * @alias PathObject.getParentDirectory()
     */
    public function getParent(bool $sort = null): Directory|null
    {
        return $this->getParentDirectory($sort);
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
     * Get list of paths in this directory.
     *
     * @param  callable|null $filter
     * @param  bool|null     $sort
     * @return froq\file\PathList
     */
    public function list(callable $filter = null, bool $sort = null): PathList
    {
        return new PathList($this->read($filter, $sort)->map(
            fn($path) => new Path($path)
        ));
    }

    /**
     * Find entries in this directory.
     *
     * @param  string $pattern
     * @param  int    $flags
     * @return RegexIterator<SplFileInfo>
     * @throws froq\file\DirectoryException
     */
    public function find(string $pattern, int $flags = 0): \RegexIterator
    {
        try {
            return (new Finder($this->path->name))->find($pattern, $flags);
        } catch (\Throwable $e) {
            throw new DirectoryException($e);
        }
    }

    /**
     * Find entries in this directory recursively.
     *
     * @param  string $pattern
     * @param  int    $flags
     * @return RegexIterator<SplFileInfo>
     * @throws froq\file\DirectoryException
     */
    public function findAll(string $pattern, int $flags = 0): \RegexIterator
    {
        try {
            return (new Finder($this->path->name))->findAll($pattern, $flags);
        } catch (\Throwable $e) {
            throw new DirectoryException($e);
        }
    }

    /**
     * Glob this directory.
     *
     * @param  string $pattern
     * @param  int    $flags
     * @return GlobIterator<SplFileInfo>
     * @throws froq\file\DirectoryException
     */
    public function glob(string $pattern, int $flags = 0): \GlobIterator
    {
        try {
            return (new Finder($this->path->name))->glob($pattern, $flags);
        } catch (\Throwable $e) {
            throw new DirectoryException($e);
        }
    }

    /**
     * X-Glob this directory.
     *
     * @param  string $pattern
     * @param  int    $flags
     * @param  bool   $map
     * @param  bool   $list
     * @return XArray<SplFileInfo|string>
     * @throws froq\file\DirectoryException
     */
    public function xglob(string $pattern, int $flags = 0, bool $map = true, bool $list = null): \XArray
    {
        try {
            return (new Finder($this->path->name))->xglob($pattern, $flags, $map, $list);
        } catch (\Throwable $e) {
            throw new DirectoryException($e);
        }
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
    public function getIterator(): \Traversable
    {
        return $this->read(sort: true);
    }

    /**
     * @internal
     */
    private function fullpath(string $entry): string
    {
        return FileSystem::joinPaths([$this->path->name, $entry], false);
    }
}
