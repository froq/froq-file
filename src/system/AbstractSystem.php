<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file\system;

use froq\file\mime\{Mime, MimeException};
use froq\util\Util;

/**
 * An abstract class for working with file system objects.
 *
 * @package froq\file\system
 * @object  froq\file\system\AbstractSystem
 * @author  Kerem Güneş
 * @since   6.0
 */
abstract class AbstractSystem
{
    /** @const int */
    public final const MODE_ALL   = 7, MODE_READ    = 1,
                       MODE_WRITE = 2, MODE_EXECUTE = 4;

    /** @const string */
    public final const MODE_OP_ALL   = 'all',   MODE_OP_READ    = 'read',
                       MODE_OP_WRITE = 'write', MODE_OP_EXECUTE = 'execute';

    /** @const array */
    public final const MODE_OPS = ['all', 'read', 'write', 'execute'];

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
     * @causes froq\file\{PathException|FileException|DirectoryException}
     */
    public function __construct(string $path)
    {
        if (str_contains($path, "\0")) {
            self::throw('Invalid path, path contains NULL-bytes');
        } elseif (trim($path) === '') {
            self::throw('Invalid path, empty path given');
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
    }

    /** @magic */
    public function __toString(): string
    {
        return $this->path;
    }

    /**
     * Get path.
     *
     * @return string.
     */
    public final function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get original path.
     *
     * @return string.
     */
    public final function getPathOrig(): string
    {
        return $this->pathOrig;
    }

    /**
     * Get real path.
     *
     * @param  bool $check
     * @return string|null
     */
    public final function getRealPath(bool $check = true): string|null
    {
        return $check ? $this->pathInfo['realpath'] : $this->pathInfo['path'];
    }

    /**
     * Get path info.
     *
     * @param  string|null $component
     * @return string|array|null
     */
    public final function getPathInfo(string $component = null): string|array|null
    {
        return $component ? $this->pathInfo[$component] : $this->pathInfo;
    }

    /**
     * Get path type.
     *
     * @return string|null
     */
    public final function getPathType(): string|null
    {
        return $this->pathInfo['type'];
    }

    /**
     * Get name.
     *
     * @return string
     */
    public final function getName(): string
    {
        if ($this instanceof File) {
            return $this->pathInfo['filename'];
        } else {
            return $this->path;
        }
    }

    /**
     * Get dirname.
     *
     * @return string
     */
    public final function getDirname(): string
    {
        return $this->pathInfo['dirname'];
    }

    /**
     * Get basename.
     *
     * @return string
     */
    public final function getBasename(): string
    {
        return $this->pathInfo['basename'];
    }

    /**
     * Get pathname.
     *
     * @return string
     */
    public final function getPathname(): string
    {
        return $this->path;
    }

    /**
     * Get filename.
     *
     * @return string
     */
    public final function getFilename(): string
    {
        return $this->pathInfo['filename'];
    }

    /**
     * Get extension.
     *
     * @return string
     */
    public final function getExtension(): string|null
    {
        return $this->pathInfo['extension'];
    }

    /**
     * Check existence state.
     *
     * @return bool
     */
    public final function exists(): bool
    {
        return file_exists($this->path);
    }

    /**
     * Check whether path is a dir.
     *
     * @return bool
     */
    public final function isDir(): bool
    {
        return is_dir($this->path);
    }

    /**
     * Check whether path is a file.
     *
     * @return bool
     */
    public final function isFile(): bool
    {
        return is_file($this->path);
    }

    /**
     * Check whether path is a link.
     *
     * @return bool
     */
    public final function isLink(): bool
    {
        return is_link($this->path);
    }

    /**
     * Check readable state.
     *
     * @return bool
     */
    public final function isReadable(): bool
    {
        return is_readable($this->path);
    }

    /**
     * Check writable state.
     *
     * @return bool
     */
    public final function isWritable(): bool
    {
        return is_writable($this->path);
    }

    /**
     * Check executable state.
     *
     * @return bool
     */
    public final function isExecutable(): bool
    {
        return is_executable($this->path);
    }

    /**
     * Check whether path is available for read/write operations.
     *
     * @return bool
     */
    public final function isAvailable(): bool
    {
        return (bool) $this->hasAccess('read|write');
    }

    /**
     * Check whether path is available for given operation(s).
     *
     * @return bool
     */
    public final function isAvailableFor(string $op): bool
    {
        return (bool) $this->hasAccess($op);
    }

    /**
     * Check whether path has given access mode.
     *
     * @return bool
     * @causes froq\file\{PathException|FileException|DirectoryException}
     */
    public final function hasAccess(int|string $mode): bool|null
    {
        if (!$this->exists()) {
            return null;
        }

        if (is_string($mode)) {
            $ops = array_filter(
                array_map('strtolower', split('|', $mode)),
                fn($op) => in_array($op, self::MODE_OPS, true)
            );
            $ops || self::throw('Invalid mode `%s` [valids: %a]', [$mode, self::MODE_OPS]);

            $mode = 0;
            foreach ($ops as $op) {
                if ($op == self::MODE_OP_ALL) {
                    $mode |= self::MODE_READ | self::MODE_WRITE | self::MODE_EXECUTE;
                    break;
                }
                if ($op == self::MODE_OP_READ) {
                    $mode |= self::MODE_READ;
                }
                if ($op == self::MODE_OP_WRITE) {
                    $mode |= self::MODE_WRITE;
                }
                if ($op == self::MODE_OP_EXECUTE) {
                    $mode |= self::MODE_EXECUTE;
                }
            }
        }

        if ($mode == self::MODE_ALL) {
            return $this->isReadable() && $this->isWritable() && $this->isExecutable();
        }
        if (($mode & self::MODE_READ) == self::MODE_READ && !$this->isReadable()) {
            return false;
        }
        if (($mode & self::MODE_WRITE) == self::MODE_WRITE && !$this->isWritable()) {
            return false;
        }
        if (($mode & self::MODE_EXECUTE) == self::MODE_EXECUTE && !$this->isExecutable()) {
            return false;
        }
        return true;
    }

    /**
     * Set path access mode.
     *
     * @param  int $mode
     * @return string|null
     */
    public final function setMode(int $mode): string|null
    {
        try {
            return \froq\file\File::mode($this->path, $mode);
        }  catch (\froq\file\FileException) {
            return null;
        }
    }

    /**
     * Get path access mode.
     *
     * @param  bool $octstr
     * @return string|null
     */
    public final function getMode(bool $octstr = true): string|null
    {
        try {
            return \froq\file\File::mode($this->path, ($octstr ? $octstr : null));
        }  catch (\froq\file\FileException) {
            return null;
        }
    }

    /**
     * Get path MIME type.
     *
     * @return string|null
     */
    public final function getMime(): string|null
    {
        return \froq\file\File::getMime($this->path);
    }

    /**
     * Get path type.
     *
     * @return string|null
     */
    public final function getType(): string|null
    {
        return $this->pathInfo['type'];
    }

    /**
     * Get path size.
     *
     * @param  bool $format
     * @return int|string|null
     */
    public final function getSize(bool $format = false): int|string|null
    {
        if ($this->isReadable()) {
            $return = fn($s) => $format ? Util::formatBytes($s) : $s;

            switch (true) {
                case ($this instanceof File):
                    clearstatcache();
                    return $return(filesize($this->path));
                case ($this instanceof Directory):
                    $size = 0;
                    $iter = $this->getDirectoryIterator(recursive: true);
                    foreach (new \RecursiveIteratorIterator($iter) as $path) {
                        $size += $path->getSize();
                    }
                    return $return($size);
                case ($this instanceof Path):
                    if ($this->isFile()) {
                        clearstatcache();
                        return $return(filesize($this->path));
                    } elseif ($this->isDirectory()) {
                        $size = 0;
                        $iter = $this->getDirectoryIterator(recursive: true);
                        foreach (new \RecursiveIteratorIterator($iter) as $path) {
                            $size += $path->getSize();
                        }
                        return $return($size);
                    }
            }
        }

        return null;
    }

    /**
     * Check whether path is empty.
     *
     * @return bool
     */
    public final function isEmpty(): bool|null
    {
        if ($this->isReadable()) {
            $return = fn($s) => empty($s);

            switch (true) {
                case ($this instanceof File):
                    clearstatcache();
                    return $return(filesize($this->path));
                case ($this instanceof Directory):
                    return $return($this->getIterator()->valid());
                case ($this instanceof Path):
                    if ($this->isFile()) {
                        clearstatcache();
                        return $return(filesize($this->path));
                    } elseif ($this->isDirectory()) {
                        return $return($this->getIterator()->valid());
                    }
            }
        }

        return null;
    }

    /**
     * Get iterator.
     *
     * @param  string $class
     * @return iterable|null
     * @causes froq\file\{PathException|FileException|DirectoryException}
     */
    public final function getIterator(string $class = 'FilesystemIterator'): iterable|null
    {
        $this->isFile() && self::throw('Cannot get iterator for files');

        try {
            return new $class($this->path);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Get iterator.
     *
     * @param  string $class
     * @param  bool   $recursive
     * @return iterable|null
     * @causes froq\file\{PathException|FileException|DirectoryException}
     */
    public final function getDirectoryIterator(string $class = 'DirectoryIterator', bool $recursive = false): iterable|null
    {
        $this->isFile() && self::throw('Cannot get iterator for files');

        if ($recursive) {
            $class = 'RecursiveDirectoryIterator';
        }

        try {
            return new $class($this->path);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get atime.
     *
     * @return int|null
     */
    public final function getATime(): int|null
    {
        return file_exists($this->path) ? fileatime($this->path) : null;
    }

    /**
     * Get ctime.
     *
     * @return int|null
     */
    public final function getCTime(): int|null
    {
        return file_exists($this->path) ? filectime($this->path) : null;
    }

    /**
     * Get mtime.
     *
     * @return int|null
     */
    public final function getMTime(): int|null
    {
        return file_exists($this->path) ? filemtime($this->path) : null;
    }

    /**
     * Get group.
     *
     * @return int|null
     */
    public final function getGroup(): int|null
    {
        return file_exists($this->path) ? filegroup($this->path) : null;
    }

    /**
     * Get group name.
     *
     * @return int|null
     */
    public final function getGroupName(): string|null
    {
        return file_exists($this->path) ? posix_getgrgid(filegroup($this->path))['name'] : null;
    }

    /**
     * Get inode.
     *
     * @return int|null
     */
    public final function getInode(): int|null
    {
        return file_exists($this->path) ? fileinode($this->path) : null;
    }

    /**
     * Get owner.
     *
     * @return int|null
     */
    public final function getOwner(): int|null
    {
        return file_exists($this->path) ? fileowner($this->path) : null;
    }

    /**
     * Get owner name.
     *
     * @return string|null
     */
    public final function getOwnerName(): string|null
    {
        return file_exists($this->path) ? posix_getpwuid(fileowner($this->path))['name'] : null;
    }

    /**
     * Get perms.
     *
     * @return int|null
     */
    public final function getPerms(): int|null
    {
        return file_exists($this->path) ? fileperms($this->path) : null;
    }

    /**
     * Get stats.
     *
     * @param  bool $link
     * @return array|null
     */
    public final function getStats(bool $link = false): array|null
    {
        if (!file_exists($this->path)) {
            return null;
        }

        clearstatcache();

        $ret = $link ? lstat($this->path) : stat($this->path);

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Get link stats.
     *
     * @return array|null
     */
    public final function getLinkStats(): array|null
    {
        return $this->getStats(true);
    }

    /**
     * Get stats (cache).
     *
     * @return void
     */
    public final function clearStats(): void
    {
        clearstatcache();
    }

    /**
     * Make a file/directory.
     *
     * @param  int  $mode
     * @param  bool $recursive For dirs only.
     * @return bool
     */
    public final function make(int $mode = 0644, bool $recursive = false): bool
    {
        if ($this instanceof File) {
            return mkfile($this->path, $mode);
        } elseif ($this instanceof Directory) {
            return mkdir($this->path, $mode, $recursive);
        } elseif ($this instanceof Path) {
            if ($this->type == Path::TYPE_FILE) {
                return mkfile($this->path, $mode);
            } elseif ($this->type == Path::TYPE_DIRECTORY) {
                return mkdir($this->path, $mode, $recursive);
            }
        }

        return false;
    }

    /**
     * Remove a file/directory.
     *
     * @return bool
     */
    public final function remove(): bool
    {
        if ($this instanceof File) {
            return rmfile($this->path);
        } elseif ($this instanceof Directory) {
            return rmdir($this->path);
        } elseif ($this instanceof Path) {
            if ($this->type == Path::TYPE_FILE) {
                return rmfile($this->path);
            } elseif ($this->type == Path::TYPE_DIRECTORY) {
                return rmdir($this->path);
            } elseif ($this->type == Path::TYPE_LINK) {
                return unlink($this->path);
            }
        }

        return false;
    }

    /**
     * Make a link.
     *
     * @param  string $link
     * @param  bool   $symlink
     * @return bool
     */
    public final function makeLink(string $link, bool $symlink = false): bool
    {
        return $symlink ? symlink($this->path, $link) : link($this->path, $link);
    }

    /**
     * Remove a link.
     *
     * @return bool
     */
    public final function removeLink(): bool
    {
        return is_link($this->path) && unlink($this->path);
    }

    /**
     * Remove a file/link.
     *
     * @return bool
     */
    public final function drop(): bool
    {
        return file_exists($this->path) && unlink($this->path);
    }

    /**
     * Rename a path.
     *
     * @param  string $newPath
     * @return bool
     */
    public final function rename(string $newPath): bool
    {
        return file_exists($this->path) && rename($this->path, $newPath);
    }

    /**
     * Touch a path.
     *
     * @param  int|null $mode  For chmod().
     * @param  int|null $mtime
     * @param  int|null $atime
     * @return bool
     */
    public final function touch(int $mode = null, int $mtime = null, int $atime = null): bool
    {
        $ret = touch($this->path, $mtime, $atime);

        if ($mode !== null && file_exists($this->path)) {
            chmod($this->path, $mode);
        }

        return $ret;
    }

    /**
     * Create a Path instance with self path.
     *
     * @return froq\file\system\Path
     */
    public final function toPath(): Path
    {
        return new Path($this->path);
    }

    /**
     * Create a File instance with self path.
     *
     * @return froq\file\system\File
     */
    public final function toFile(): File
    {
        return new File($this->path);
    }

    /**
     * Create a Directory instance with self path.
     *
     * @return froq\file\system\Directory
     */
    public final function toDirectory(): Directory
    {
        return new Directory($this->path);
    }

    /** @alias isDir() */
    public final function isDirectory()
    {
        return $this->isDir();
    }

    /** @alias toDirectory() */
    public final function toDir()
    {
        return $this->toDirectory();
    }

    /** @alias rename() */
    public final function move(...$args)
    {
        return $this->rename(...$args);
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

    /**
     * Check existence state.
     *
     * @return bool
     */
    public function ok(): bool
    {
        return file_exists($this->path);
    }

    /**
     * Throw a related exception.
     */
    private static function throw(...$args): void
    {
        $exception = match (true) {
            is_class_of(static::class, Path::class)      => PathException::class,
            is_class_of(static::class, File::class)      => FileException::class,
            is_class_of(static::class, Directory::class) => DirectoryException::class,
        };

        throw new $exception(...$args);
    }
}
