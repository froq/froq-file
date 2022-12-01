<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

use froq\file\system\{Path, File, Directory};
use froq\common\interface\{Arrayable, Objectable};

/**
 * An extended SplFileInfo class.
 *
 * @package froq\file
 * @class   froq\file\Info
 * @author  Kerem Güneş
 * @since   6.0
 */
class Info extends \SplFileInfo implements Arrayable, Objectable, \ArrayAccess
{
    /** Path */
    public readonly string $path;

    /** Original path. */
    public readonly string $pathOrig;

    /** Resolved path info. */
    public readonly array|null $pathInfo;

    /**
     * Constructor.
     *
     * @param  string $path
     * @throws froq\file\InfoException
     */
    public function __construct(string $path)
    {
        if (str_contains($path, "\0")) {
            throw new InfoException('Invalid path, path contains NULL-bytes');
        } elseif (trim($path) == '') {
            throw new InfoException('Invalid path, empty path given');
        }

        // Keep original path.
        $this->pathOrig = $path;

        // This resolves real path as well.
        $this->pathInfo = get_path_info($path);

        // Prevent link resolutions.
        if (is_link($path)) {
            $this->path = $path;
        } else {
            $this->path = $this->pathInfo['path'];
        }

        parent::__construct($this->path);
    }

    /**
     * @magic
     */
    public function __toString(): string
    {
        return $this->path;
    }

    /**
     * @override
     */
    #[\ReturnTypeWillChange]
    public function getType(): string|null
    {
        return $this->pathInfo['type'];
    }

    /**
     * @override
     */
    #[\ReturnTypeWillChange]
    public function getExtension(): string|null
    {
        return $this->pathInfo['extension'];
    }

    /**
     * @override
     */
    #[\ReturnTypeWillChange]
    public function getFilename(): string|null
    {
        return $this->pathInfo['filename'];
    }

    /**
     * @missing
     */
    public function getDirname(): string
    {
        return $this->pathInfo['dirname'];
    }

    /**
     * Get file mime.
     *
     * @return string|null
     */
    public function getMime(): string|null
    {
        return \froq\file\File::getMime($this->getPathname());
    }

    /**
     * Check file existence.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return file_exists($this->getPathname());
    }

    /**
     * Check whether a file is available for read/write operations.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->toPath()->isAvailable();
    }

    /**
     * Check whether a file is available for given operation(s).
     *
     * @param  string $op
     * @return bool
     */
    public function isAvailableFor(string $op): bool
    {
        return $this->toPath()->isAvailableFor($op);
    }

    /**
     * Create a system Path instance with self path.
     *
     * @return froq\file\system\Path
     */
    public function toPath(): Path
    {
        return new Path($this->getPathname());
    }

    /**
     * Create a system File instance with self path.
     *
     * @return froq\file\system\File
     */
    public function toFile(): File
    {
        return new File($this->getPathname());
    }

    /**
     * Create a system Directory instance with self path.
     *
     * @return froq\file\system\Directory
     */
    public function toDir(): Directory
    {
        return new Directory($this->getPathname());
    }

    /**
     * @alias toDir()
     */
    public function toDirectory()
    {
        return $this->toDir();
    }

    /**
     * @alias isDir()
     */
    public function isDirectory()
    {
        return $this->isDir();
    }

    /**
     * @inheritDoc froq\common\interface\Arrayable
     */
    public function toArray(): array
    {
        return $this->pathInfo;
    }

    /**
     * @inheritDoc froq\common\interface\Objectable
     */
    public function toObject(): object
    {
        return (object) $this->pathInfo;
    }

    /**
     * Normalize given path.
     *
     * @param  string $path
     * @return string
     */
    public static function normalizePath(string $path): string
    {
        return get_real_path($path, check: false);
    }

    /**
     * @inheritDoc ArrayAccess
     */
    public function offsetExists(mixed $key): bool
    {
        return array_key_exists($key, $this->pathInfo);
    }
    /**
     * @inheritDoc ArrayAccess
     */
    public function offsetGet(mixed $key): mixed
    {
        return $this->pathInfo[$key] ?? null;
    }

    /**
     * @inheritDoc ArrayAccess
     * @throws     ReadonlyError
     */
    public function offsetSet(mixed $key, mixed $_): never
    {
        throw new \ReadonlyError($this);
    }

    /**
     * @inheritDoc ArrayAccess
     * @throws     ReadonlyError
     */
    public function offsetUnset(mixed $key): never
    {
        throw new \ReadonlyError($this);
    }
}
