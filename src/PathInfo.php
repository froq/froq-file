<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * A re-written `SplFileInfo` class for directories / files.
 *
 * @package froq\file
 * @class   froq\file\PathInfo
 * @author  Kerem Güneş
 * @since   6.0, 7.0
 */
class PathInfo implements \Stringable
{
    /** Path name. */
    private string $path;

    /** Resolved info. */
    private array $info;

    /**
     * Constructor.
     *
     * @param  string $path
     * @throws froq\file\PathInfoException
     */
    public function __construct(string $path)
    {
        if (str_contains($path, "\0")) {
            throw PathInfoException::forInvalidPath('Path contains NULL-bytes');
        } elseif (trim($path) === '') {
            throw PathInfoException::forInvalidPath('Path is empty');
        }

        $this->info = get_path_info($path);
        $this->path = $this->info['path'];
    }

    /**
     * @magic
     */
    public function __toString(): string
    {
        return $this->path;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get info.
     *
     * @return array
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    /**
     * Get directory info.
     *
     * @return froq\file\PathInfo
     */
    public function getDirectoryInfo(): PathInfo|null
    {
        return $this->getDirname() ? new PathInfo($this->getDirname()) : null;
    }

    /**
     * Get directory.
     *
     * @return string|null
     */
    public function getDirectory(): string|null
    {
        return $this->getDirname();
    }

    /**
     * Get root directory (java.nio.file.Path#getRoot).
     *
     * @return string|null
     */
    public function getRootDirectory(): string|null
    {
        if (($depth = substr_count($this->path, DIRECTORY_SEPARATOR)) > 1) {
            return dirname($this->path, $depth - 1);
        }

        return null;
    }

    /**
     * Get parent directory.
     *
     * @return string|null
     */
    public function getParentDirectory(): string|null
    {
        if (($dirname = dirname($this->path)) !== '') {
            return $dirname !== $this->path ? $dirname : null;
        }

       return null;
    }

    /**
     * Get mime.
     *
     * @return string|null
     */
    public function getMime(): string|null
    {
        return @file_mime($this->path);
    }

    /**
     * Get type.
     *
     * @return string|null
     */
    public function getType(): string|null
    {
        return $this->info['type'];
    }

    /**
     * Get extension.
     *
     * @return string|null
     */
    public function getExtension(): string|null
    {
        return $this->info['extension'];
    }

    /**
     * Get dirname.
     *
     * @return string|null
     */
    public function getDirname(): string|null
    {
        return $this->info['dirname'];
    }

    /**
     * Get basename.
     *
     * @return string|null
     */
    public function getBasename(): string|null
    {
        return $this->info['basename'];
    }

    /**
     * Get filename.
     *
     * @return string|null
     */
    public function getFilename(): string|null
    {
        return $this->info['filename'];
    }

    /**
     * Get real path.
     *
     * @return string|null
     */
    public function getRealPath(): string|null
    {
        return $this->info['realpath'];
    }

    /**
     * Get link target.
     *
     * @return string|null
     */
    public function getLinkTarget(): string|null
    {
        return @readlink($this->path) ?: null;
    }

    /**
     * Get link info.
     *
     * @return int|null
     */
    public function getLinkInfo(): int|null
    {
        return @linkinfo($this->path) ?: null;
    }

    /**
     * Get size.
     *
     * @return int|null
     */
    public function getSize(): int|null
    {
        return $this->stat('size');
    }

    /**
     * Get ctime.
     *
     * @return int|null
     */
    public function getCTime(): int|null
    {
        return $this->stat('ctime');
    }

    /**
     * Get atime.
     *
     * @return int|null
     */
    public function getATime(): int|null
    {
        return $this->stat('atime');
    }

    /**
     * Get mtime.
     *
     * @return int|null
     */
    public function getMTime(): int|null
    {
        return $this->stat('mtime');
    }

    /**
     * Get inode.
     *
     * @return int|null
     */
    public function getInode(): int|null
    {
        return $this->stat('ino');
    }

    /**
     * Get group.
     *
     * @return int|null
     */
    public function getGroup(): int|null
    {
        return $this->stat('gid');
    }

    /**
     * Get owner.
     *
     * @return int|null
     */
    public function getOwner(): int|null
    {
        return $this->stat('uid');
    }

    /**
     * Get perms.
     *
     * @return int|null
     */
    public function getPerms(): int|null
    {
        return $this->stat('mode');
    }

    /**
     * Get stat.
     *
     * @return array|null
     */
    public function getStat(): array|null
    {
        return $this->stat();
    }

    /**
     * Clear stat.
     *
     * @return void
     */
    public function clearStat(): void
    {
        clearstatcache(true, $this->path);
    }

    /**
     * Check existence.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return @file_exists($this->path);
    }

    /**
     * Check whether path is a directory.
     *
     * @return bool
     */
    public function isDirectory(): bool
    {
        return @is_dir($this->path);
    }

    /**
     * Check whether path is a file.
     *
     * @return bool
     */
    public function isFile(): bool
    {
        return @is_file($this->path);
    }

    /**
     * Check whether path is a link.
     *
     * @return bool
     */
    public function isLink(): bool
    {
        return @is_link($this->path);
    }

    /**
     * Check whether path is readable.
     *
     * @return bool
     */
    public function isReadable(): bool
    {
        return @is_readable($this->path);
    }

    /**
     * Check whether path is writable.
     *
     * @return bool
     */
    public function isWritable(): bool
    {
        return @is_writable($this->path);
    }

    /**
     * Check whether path is executable.
     *
     * @return bool
     */
    public function isExecutable(): bool
    {
        return @is_executable($this->path);
    }

    /**
     * Check whether path is hidden.
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->getBasename()[0] === '.';
    }

    /**
     * Check whether path is available for read/write operations.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->isAvailableFor('read|write');
    }

    /**
     * Check whether path is available for given (read/write/execute) operations.
     *
     * @param  string $ops
     * @return bool
     */
    public function isAvailableFor(string $ops): bool
    {
        static $opsValid = ['read', 'write', 'execute'];

        if ($this->exists()) {
            $opsGiven = [];

            foreach (explode('|', $ops) as $op) {
                $opsGiven[] = $op;

                if ($op === 'read' && !$this->isReadable()) {
                    return false;
                }
                if ($op === 'write' && !$this->isWritable()) {
                    return false;
                }
                if ($op === 'execute' && !$this->isExecutable()) {
                    return false;
                }
            }

            // Validates given ops as well.
            return $opsGiven && array_contains($opsValid, ...$opsGiven);
        }

        return false;
    }

    /**
     * @alias isDirectory()
     */
    public function isDir()
    {
        return $this->isDirectory();
    }

    /**
     * @alias getDirectoryInfo()
     */
    public function getDirInfo()
    {
        return $this->getDirectoryInfo();
    }

    /**
     * @internal
     */
    private function stat(int|string $key = null): int|array|null
    {
        $stat = @file_stat($this->path);

        return $key ? $stat[$key] ?? null : $stat;
    }
}
