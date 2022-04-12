<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file;

use froq\file\system\{Path, File, Directory};
use froq\common\interface\{Arrayable, Objectable};

/**
 * An extended SplFileInfo class.
 *
 * @package froq\file
 * @object  froq\file\Info
 * @author  Kerem Güneş
 * @since   6.0
 */
class Info extends \SplFileInfo implements Arrayable, Objectable
{
    /** @var string */
    public readonly string $path;

    /** @var string */
    public readonly string $pathOrig;

    /** @var array|null */
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

    /** @magic */
    public function __toString(): string
    {
        return $this->path;
    }

    /** @override */ #[\ReturnTypeWillChange]
    public final function getType(): string|null
    {
        return $this->pathInfo['type'];
    }

    /** @override */ #[\ReturnTypeWillChange]
    public final function getExtension(): string|null
    {
        return $this->pathInfo['extension'];
    }

    /** @override */ #[\ReturnTypeWillChange]
    public final function getFilename(): string|null
    {
        return $this->pathInfo['filename'];
    }

    /** @missing */
    public final function getDirname(): string
    {
        return $this->pathInfo['dirname'];
    }

    /**
     * Get file mime.
     *
     * @return string|null
     */
    public final function getMime(): string|null
    {
        return \froq\file\File::getMime($this->getPathname());
    }

    /**
     * Check file existence.
     *
     * @return bool
     */
    public final function exists(): bool
    {
        return file_exists($this->getPathname());
    }

    /**
     * Check whether a file is available for read/write operations.
     *
     * @return bool
     */
    public final function isAvailable(): bool
    {
        return $this->toPath()->isAvailable();
    }

    /**
     * Check whether a file is available for given operation(s).
     *
     * @return bool
     */
    public final function isAvailableFor(string $op): bool
    {
        return $this->toPath()->isAvailableFor($op);
    }

    /**
     * Create a system Path instance with self path.
     *
     * @return froq\file\system\Path
     */
    public final function toPath(): Path
    {
        return new Path($this->getPathname());
    }

    /**
     * Create a system File instance with self path.
     *
     * @return froq\file\system\File
     */
    public final function toFile(): File
    {
        return new File($this->getPathname());
    }

    /**
     * Create a system Directory instance with self path.
     *
     * @return froq\file\system\Directory
     */
    public final function toDir(): Directory
    {
        return new Directory($this->getPathname());
    }

    /** @alias toDir() */
    public final function toDirectory()
    {
        return $this->toDir();
    }

    /** @alias SplFileInfo.isDir() */
    public final function isDirectory()
    {
        return parent::isDir();
    }

    /**
     * @inheritDoc froq\common\interface\Arrayable
     */
    public final function toArray(): array
    {
        return $this->pathInfo;
    }

    /**
     * @inheritDoc froq\common\interface\Objectable
     */
    public final function toObject(): object
    {
        return (object) $this->pathInfo;
    }

    /**
     * Normalize given path.
     *
     * @param  string $path
     * @return string
     */
    public final static function normalizePath(string $path): string
    {
        return get_real_path($path, check: false);
    }
}
