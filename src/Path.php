<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * Path class of directories / files, for accessing to info and stat, or opening them.
 *
 * @package froq\file
 * @class   froq\file\Path
 * @author  Kerem Güneş
 * @since   7.0, 7.1
 */
class Path extends PathInfo implements \Countable
{
    /** Path name (shortcut). */
    public readonly string $name;

    /** Use real path. */
    public bool $useRealPath;

    /**
     * Constructor.
     *
     * @param  string|Path|PathInfo $path
     * @param  bool                 $useRealPath For part methods (@fornow).
     * @throws froq\file\PathException
     */
    public function __construct(string|Path|PathInfo $path, bool $useRealPath = false)
    {
        try {
            parent::__construct($path);

            $this->name = $this->path; // Copy.
            $this->useRealPath = $useRealPath;
        } catch (\Throwable $e) {
            throw PathException::exception($e);
        }
    }

    /**
     * Just for concise dumps.
     *
     * @magic
     */
    public function __debugInfo(): array
    {
        return ['name' => $this->name, 'info' => $this->info];
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get this path's tree as string or Path instance, or return null if this
     * option `$useRealPath` is true and no real-path resolved.
     *
     * @param  bool $convert
     * @return array<string|froq\file\Path>|null
     */
    public function getTree(bool $convert = false): array|null
    {
        $path = $this->useRealPath ? $this->realpath : $this->path;

        // Null realpath.
        if ($path === null) {
            return null;
        }

        return FileSystem::getPathTree($path, false, $convert);
    }

    /**
     * Get this path's tree as string or Path instance, or return null if this
     * option `$useRealPath` is true and no real-path resolved.
     *
     * @param  bool $convert
     * @return froq\file\PathList<froq\file\Path>|null
     */
    public function getTreeList(bool $convert = false): PathList|null
    {
        return ($paths = $this->getTree($convert)) ? new PathList($paths) : null;
    }

    /**
     * Split this path, or return null if this option `$useRealPath` is true
     * and no real-path resolved.
     *
     * @param  bool $normalize
     * @return array<string>|null
     */
    public function split(bool $normalize = true): array|null
    {
        $path = $this->useRealPath ? $this->realpath : $this->path;

        // Null realpath.
        if ($path === null) {
            return null;
        }

        return FileSystem::splitPaths($path, $normalize);
    }

    /**
     * Join given paths with this path and return new path, or return null if this
     * option `$useRealPath` is true and no real-path resolved.
     *
     * @param  array<string>|string $parts
     * @param  bool                 $normalize
     * @return string|null
     */
    public function join(array|string $parts, bool $normalize = true): string|null
    {
        $path = $this->useRealPath ? $this->realpath : $this->path;

        // Null realpath.
        if ($path === null) {
            return null;
        }

        return FileSystem::joinPaths([$path, ...(array) $parts], $normalize);
    }

    /**
     * Append given parts & return new Path instance.
     *
     * @param  array<string>|string  $parts
     * @param  bool                  $normalize
     * @return froq\file|Path|null
     */
    public function append(array|string $parts, bool $normalize = true): Path|null
    {
        $path = $this->join($parts, $normalize);

        return $path ? new Path($path) : null;
    }

    /**
     * Open this path as a directory or file.
     *
     * @param  array|null $options
     * @return froq\file\{Directory|File}
     * @throws froq\file\PathException
     */
    public function open(array $options = null): Directory|File
    {
        $this->exists() || throw PathException::forNoFile($this->path);

        return match (true) {
            $this->isDirectory() => FileSystem::openDirectory($this, $options),
            $this->isFile() => FileSystem::openFile($this, $options)
        };
    }

    /**
     * Get this path as a Directory object.
     *
     * @param  array|null $options
     * @return froq\file\Directory
     */
    public function toDirectory(array $options = null): Directory
    {
        return new Directory($this, $options);
    }

    /**
     * Get this path as a File object.
     *
     * @param  array|null $options
     * @return froq\file\File
     */
    public function toFile(array $options = null): File
    {
        return new File($this, $options);
    }

    /**
     * @inheritDoc Countable
     */
    public function count(): int
    {
        $path = $this->useRealPath ? $this->realpath : $this->path;

        // Null realpath.
        if ($path === null) {
            return -1;
        }

        return FileSystem::countPaths($path, false);
    }

    /**
     * Static initializer with given parts.
     *
     * @param  string ...$parts
     * @return froq\file\Path
     * @throws froq\file\PathException
     */
    public static function of(string ...$parts): Path
    {
        $parts || throw PathException::forNoPartsGiven();

        $path = FileSystem::joinPaths($parts, false);

        return new Path($path);
    }
}
