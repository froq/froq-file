<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file;

use froq\file\mime\{Mime, MimeException};
use froq\file\system\{Path, File, Directory};
use SplFileInfo;

/**
 * Info.
 *
 * An extended SplFileInfo class.
 *
 * @package froq\file
 * @object  froq\file\Info
 * @author  Kerem Güneş
 * @since   6.0
 */
class Info extends SplFileInfo
{
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
        } elseif (trim($path) === '') {
            throw new InfoException('Invalid path, empty path given');
        }

        // Keep original path.
        $this->pathOrig = $path;

        // This will resolve real path as well.
        $this->pathInfo = get_path_info($path);

        // Prevent link resolutions.
        if (!is_link($path)) {
            $path = $this->pathInfo['path'];
        }

        parent::__construct($path);
    }

    /** @magic __get() */
    public function __get(string $property): mixed
    {
        if ($this->pathInfo && array_key_exists($property, $this->pathInfo)) {
            return $this->pathInfo[$property];
        }

        trigger_error(
            'Undefined property: '. $this::class .'::$'. $property,
            E_USER_WARNING // Act like original.
        );

        return null;
    }

    /** @magic __toString() */
    public function __toString(): string
    {
        return $this->path;
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

    /** @missing */
    public final function getDirname(): string
    {
        return $this->pathInfo['dirname'];
    }

    /** @override */
    public final function getFilename(): string
    {
        return $this->pathInfo['filename'];
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

    /** @aliasOf toDir() */
    public final function toDirectory() { return $this->toDir(); }

    /** @aliasOf SplFileInfo.isDir() */
    public final function isDirectory() { return parent::isDir(); }

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
