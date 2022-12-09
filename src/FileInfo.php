<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * An extended `SplFileInfo` class.
 *
 * @package froq\file
 * @class   froq\file\FileInfo
 * @author  Kerem Güneş
 * @since   6.0
 */
class FileInfo extends \SplFileInfo
{
    /** Path name. */
    public readonly string $path;

    /** Resolved info. */
    public readonly array $info;

    /**
     * Constructor.
     *
     * @param  string $path
     * @throws froq\file\FileInfoException
     */
    public function __construct(string $path)
    {
        if (str_contains($path, "\0")) {
            throw new FileInfoException('Invalid path, path contains NULL-bytes');
        } elseif (trim($path) === '') {
            throw new FileInfoException('Invalid path, empty path given');
        }

        $this->info = get_path_info($path);
        $this->path = $this->info['path'];

        parent::__construct($this->path);
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
     * Get mime.
     *
     * @return string|false
     */
    public function getMime(): string|false
    {
        return @file_mime($this->path) ?? false;
    }

    /**
     * @override
     */
    public function getType(): string|false
    {
        return $this->info['type'] ?? false;
    }

    /**
     * @missing
     */
    public function getDirname(): string
    {
        return (string) $this->info['dirname'];
    }

    /**
     * @override
     */
    public function getFilename(): string
    {
        return (string) $this->info['filename'];
    }

    /**
     * @override
     */
    public function getExtension(): string
    {
        return (string) $this->info['extension'];
    }

    /**
     * @override
     */
    public function getLinkTarget(): string|false
    {
        return @readlink($this->path);
    }

    /**
     * @override
     */
    public function getSize(): int|false
    {
        return $this->getStat('size');
    }

    /**
     * @override
     */
    public function getATime(): int|false
    {
        return $this->getStat('atime');
    }

    /**
     * @override
     */
    public function getMTime(): int|false
    {
        return $this->getStat('mtime');
    }

    /**
     * @override
     */
    public function getCTime(): int|false
    {
        return $this->getStat('ctime');
    }

    /**
     * @override
     */
    public function getInode(): int|false
    {
        return $this->getStat('ino');
    }

    /**
     * @override
     */
    public function getGroup(): int|false
    {
        return $this->getStat('gid');
    }

    /**
     * @override
     */
    public function getOwner(): int|false
    {
        return $this->getStat('uid');
    }

    /**
     * @override
     */
    public function getPerms(): int|false
    {
        return $this->getStat('mode');
    }

    /**
     * Get stat.
     *
     * @param  int|string $key
     * @return int|false
     */
    public function getStat(int|string $key): int|false
    {
        return $this->getStats()[$key] ?? false;
    }

    /**
     * Get stats.
     *
     * @return array|null
     */
    public function getStats(): array|null
    {
        $stats = false;

        if ($this->exists()) {
            // For fresh stats.
            $this->clearStats();

            // @tome: SplFileInfo uses real path.
            if ($this->isLink()) {
                $stats = @lstat($this->getRealPath());
            } else {
                $stats = @stat($this->getRealPath());
            }
        }

        return ($stats !== false) ? $stats : null;
    }

    /**
     * Clear stats.
     *
     * @return void
     */
    public function clearStats(): void
    {
        clearstatcache(true, $this->getRealPath());
    }

    /**
     * Get dir info (more suggestive than SplFileInfo::getPathInfo()).
     *
     * @return froq\file\FileInfo
     */
    public function getDirInfo(): FileInfo
    {
        return new FileInfo($this->getDirname());
    }

    /**
     * Check whether this file/directory is available for read/write operations.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->isAvailableFor('read|write');
    }

    /**
     * Check whether this file/directory is available for given (read/write/execute) operations.
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
     * @alias isDir()
     */
    public function isDirectory()
    {
        return $this->isDir();
    }

    /**
     * @alias getDirInfo()
     */
    public function getDirectoryInfo()
    {
        return $this->getDirInfo();
    }
}
