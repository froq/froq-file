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
class PathInfo implements \Stringable, \ArrayAccess
{
    /** Path name. */
    public readonly string $path;

    /** Resolved info. */
    public readonly array $info;

    /**
     * Constructor.
     *
     * @param  string|Path|PathInfo $path
     * @throws froq\file\PathInfoException
     */
    public function __construct(string|Path|PathInfo $path)
    {
        if (is_string($path)) {
            // Validate.
            if (str_empty($path)) {
                throw PathInfoException::forInvalidPath('Path is empty');
            } elseif (str_contains($path, "\0")) {
                throw PathInfoException::forInvalidPath('Path contains NULL-bytes');
            }

            $this->info = get_path_info($path);
            $this->path = $this->info['path'];
        } else {
            // Already validated.
            $this->info = $path->info;
            $this->path = $path->path;
        }
    }

    /**
     * @magic
     */
    public function __toString(): string
    {
        return $this->path;
    }

    /**
     * Get an info field if exists, or return null if absent.
     *
     * @causes froq\file\PathInfoException
     * @magic
     */
    public function __get(string $key): mixed
    {
        return $this->info($key);
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
     * @return froq\file\PathInfo|null
     */
    public function getDirectoryInfo(): PathInfo|null
    {
        return $this->info['dirname'] ? new PathInfo($this->info['dirname']) : null;
    }

    /**
     * Get directory.
     *
     * @param  int|null $level
     * @return string|null
     */
    public function getDirectory(int $level = null): string|null
    {
        $dirname = $this->info['dirname'];

        if ($dirname !== null) {
            if ($level !== null) {
                $count = substr_count($this->path, DIRECTORY_SEPARATOR);

                // Prevents invalid levels too.
                if ($count >= 1 && $level <= $count) {
                    // Root & Parent.
                    if ($level === -1) {
                        $level = PHP_INT_MAX;
                    } elseif ($level === -2) {
                        $level = 2;
                    }

                    $dirname = dirname($this->path, $level);
                } else {
                    $dirname = null;
                }
            }
        }

        return $dirname;
    }

    /**
     * Get root directory (java.nio.file.Path#getRoot).
     *
     * @return string|null
     */
    public function getRootDirectory(): string|null
    {
        return $this->getDirectory(-1);
    }

    /**
     * Get parent directory.
     *
     * @return string|null
     */
    public function getParentDirectory(): string|null
    {
        return $this->getDirectory(-2);
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
     * Get dir name.
     *
     * @return string|null
     */
    public function getDirName(): string|null
    {
        return $this->info['dirname'];
    }

    /**
     * Get base name.
     *
     * @return string|null
     */
    public function getBaseName(): string|null
    {
        return $this->info['basename'];
    }

    /**
     * Get file name.
     *
     * @return string|null
     */
    public function getFileName(): string|null
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
     * Get perms info.
     *
     * @return array|null
     */
    public function getPermsInfo(): array|null
    {
        return $this->exists() ? [
            'read' => $this->isReadable(),
            'write' => $this->isWritable(),
            'execute' => $this->isExecutable()
        ] : null;
    }

    /**
     * Get stat.
     *
     * @param  bool $clear
     * @return array|null
     */
    public function getStat(bool $clear = true): array|null
    {
        return $this->stat(null, $clear);
    }

    /**
     * Clear stat.
     *
     * @return self
     */
    public function clearStat(): self
    {
        clearstatcache(true, $this->path);

        return $this;
    }

    /**
     * Check existence of this path.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return @file_exists($this->path);
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
     * Check if path is readable.
     *
     * @param  bool $clear
     * @return bool
     */
    public function isReadable(bool $clear = false): bool
    {
        return $this->check('readable', $clear);
    }

    /**
     * Check if path is writable.
     *
     * @param  bool $clear
     * @return bool
     */
    public function isWritable(bool $clear = false): bool
    {
        return $this->check('writable', $clear);
    }

    /**
     * Check if path is executable.
     *
     * @param  bool $clear
     * @return bool
     */
    public function isExecutable(bool $clear = false): bool
    {
        return $this->check('executable', $clear);
    }

    /**
     * Check if path is in temp directory.
     *
     * @return bool
     */
    public function isTemp(): bool
    {
        return is_tmpdir($this->path) || is_tmpnam($this->path);
    }

    /**
     * Check if path is hidden.
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->info['basename'] && $this->info['basename'][0] === '.';
    }

    /**
     * Check if path is an image file.
     *
     * @return bool
     */
    public function isImage(): bool
    {
        return str_starts_with((string) $this->getMime(), 'image/');
    }

    /**
     * Check if path is available for read/write operations.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->isAvailableFor(['read', 'write']);
    }

    /**
     * Check if path is available for given (read/write/execute) operations.
     *
     * @param  string|array $ops
     * @return bool
     * @throws froq\file\PathInfoException If no ops given.
     */
    public function isAvailableFor(string|array $ops): bool
    {
        static $opsValid = ['read', 'write', 'execute'];

        if ($this->exists()) {
            $opsGiven = [];

            // Convert to eg. "read|write" form.
            is_array($ops) || $ops = explode('|', $ops);

            foreach ($ops as $op) {
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

            $opsGiven || throw PathInfoException::forNoOpsGiven();

            return array_contains($opsValid, ...$opsGiven);
        }

        return false;
    }

    /**
     * @alias isDirectory()
     */
    public function isDir($clear = false)
    {
        return $this->isDirectory($clear);
    }

    /**
     * @alias getDirectoryInfo()
     */
    public function getDirInfo()
    {
        return $this->getDirectoryInfo();
    }

    /**
     * @inheritDoc ArrayAccess
     */
    public function offsetExists(mixed $key): bool
    {
        return $this->info($key, false) !== null;
    }

    /**
     * @inheritDoc ArrayAccess
     * @causes     froq\file\PathInfoException
     */
    public function offsetGet(mixed $key): mixed
    {
        return $this->info($key, true);
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
     * Get an info field.
     *
     * @throws froq\file\PathInfoException
     */
    private function info(string $key, bool $throw = true): string|null
    {
        static $camelCaseKeys = [
            'realPath' => 'realpath', 'dirName'  => 'dirname',
            'fileName' => 'filename', 'baseName' => 'basename'
        ];

        // Lookup for lower-case key.
        $key = $camelCaseKeys[$key] ?? $key;

        if (is_array_key($this->info, $key)) {
            return $this->info[$key];
        }

        if ($throw) {
            throw new PathInfoException(
                $message = format('Undefined info key %q', $key),
                cause: new \KeyError($message)
            );
        }

        return null;
    }

    /**
     * Get stat info.
     */
    private function stat(int|string $key = null, bool $clear = true): int|array|null
    {
        $stat = @file_stat($this->path, $clear, check: false);

        return $key ? $stat[$key] ?? null : $stat;
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
