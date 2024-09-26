<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

use froq\util\Util;

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
    private array $info;

    /**
     * Constructor.
     *
     * @param  string|Path|PathInfo $path
     * @throws froq\file\StatException
     * @causes froq\file\StatException
     */
    public function __construct(string|Path|PathInfo $path)
    {
        try {
            $pathInfo = new PathInfo($path);

            $this->path = $pathInfo->path;
        } catch (\Throwable $e) {
            throw StatException::exception($e);
        }

        $this->info = $this->stat();
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
     * Get size info as formatted.
     *
     * @return string
     */
    public function getSizeInfo(): string
    {
        return Util::formatBytes($this->getSize());
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
     * Get perms info.
     *
     * @return array
     */
    public function getPermsInfo(): array
    {
        return [
            'read' => $this->isReadable(),
            'write' => $this->isWritable(),
            'execute' => $this->isExecutable()
        ];
    }

    /**
     * Check if path is a directory.
     *
     * @param  bool $clear
     * @return bool
     */
    public function isDirectory(bool $clear = false): bool
    {
        return $this->check('dir', $clear);
    }

    /**
     * Check if path is a file.
     *
     * @param  bool $clear
     * @return bool
     */
    public function isFile(bool $clear = false): bool
    {
        return $this->check('file', $clear);
    }

    /**
     * Check if path is a link.
     *
     * @param  bool $clear
     * @return bool
     */
    public function isLink(bool $clear = false): bool
    {
        return $this->check('link', $clear);
    }

    /**
     * Check whether path is readable.
     *
     * @param  bool $clear
     * @return bool
     */
    public function isReadable(bool $clear = false): bool
    {
        return $this->check('readable', $clear);
    }

    /**
     * Check whether path is writable.
     *
     * @param  bool $clear
     * @return bool
     */
    public function isWritable(bool $clear = false): bool
    {
        return $this->check('writable', $clear);
    }

    /**
     * Check whether path is executable.
     *
     * @param  bool $clear
     * @return bool
     */
    public function isExecutable(bool $clear = false): bool
    {
        return $this->check('executable', $clear);
    }

    /**
     * @alias isDirectory()
     */
    public function isDir($clear = false)
    {
        return $this->isDirectory($clear);
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
     * Clear stat cache.
     *
     * @return self
     */
    public function clear(): self
    {
        clearstatcache(true, $this->path);

        return $this;
    }

    /**
     * Reset stat info.
     *
     * @return self
     * @causes froq\file\StatException
     */
    public function reset(): self
    {
        $this->info = $this->stat();

        return $this;
    }

    /**
     * Get an info field.
     *
     * @link https://en.wikipedia.org/wiki/Stat_(system_call)
     */
    private function info(int|string $key): int
    {
        return $this->info[$key] ?? -1;
    }

    /**
     * Get stat info.
     *
     * @throws froq\file\StatException
     */
    private function stat(): array
    {
        return @file_stat($this->path) ?? throw StatException::error();
    }

    /**
     * Check file state by given function.
     */
    private function check(string $func, bool $clear): bool
    {
        $clear && clearstatcache(true, $this->path);

        return @('is_' . $func)($this->path);
    }
}
