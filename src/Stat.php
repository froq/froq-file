<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * Stat class for directories / files.
 *
 * @package froq\file
 * @class   froq\file\Stat
 * @author  Kerem Güneş
 * @since   7.0
 */
class Stat implements \ArrayAccess
{
    /** Stat path. */
    public readonly string $path;

    /** Stat info. */
    public readonly array $info;

    /**
     * Constructor.
     *
     * @param  string|Path|PathInfo $path
     * @throws froq\file\StatException
     */
    public function __construct(string|Path|PathInfo $path)
    {
        try {
            $info = new PathInfo($path);

            $this->path = $info->path;
        } catch (\Throwable $e) {
            throw StatException::exception($e);
        }

        $this->info = @file_stat($this->path) ?? throw StatException::error();
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
     * Get size.
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->info('size');
    }

    /**
     * Get ctime.
     *
     * @return int
     */
    public function getCTime(): int
    {
        return $this->info('ctime');
    }

    /**
     * Get atime.
     *
     * @return int
     */
    public function getATime(): int
    {
        return $this->info('atime');
    }

    /**
     * Get mtime.
     *
     * @return int
     */
    public function getMTime(): int
    {
        return $this->info('mtime');
    }

    /**
     * Get inode.
     *
     * @return int
     */
    public function getInode(): int
    {
        return $this->info('ino');
    }

    /**
     * Get group.
     *
     * @return int
     */
    public function getGroup(): int
    {
        return $this->info('gid');
    }

    /**
     * Get owner.
     *
     * @return int
     */
    public function getOwner(): int
    {
        return $this->info('uid');
    }

    /**
     * Get perms.
     *
     * @return int
     */
    public function getPerms(): int
    {
        return $this->info('mode');
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
     * @alias isDirectory()
     */
    public function isDir()
    {
        return $this->isDirectory();
    }

    /**
     * @inheritDoc ArrayAccess
     */
    public function offsetExists(mixed $key): bool
    {
        return $this->info($key) !== -1;
    }

    /**
     * @inheritDoc ArrayAccess
     */
    public function offsetGet(mixed $key): mixed
    {
        return $this->info($key);
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

    /**
     * @link https://en.wikipedia.org/wiki/Stat_(system_call)
     * @internal
     */
    private function info(int|string $key): int
    {
        return $this->info[$key] ?? -1;
    }
}
