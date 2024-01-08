<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * Base class for directory / file classes (like `SplFileObject` class).
 *
 * @package froq\file
 * @class   froq\file\PathObject
 * @author  Kerem Güneş
 * @since   7.0, 7.1
 * @internal
 */
abstract class PathObject
{
    /** Path instance. */
    public readonly Path $path;

    /**
     * Constructor.
     *
     * @param  string|Path $path
     * @throws froq\file\PathObjectException
     */
    public function __construct(string|Path $path)
    {
        if (is_string($path)) {
            try {
                $path = new Path($path);
            } catch (\Throwable $e) {
                throw PathObjectException::exception($e);
            }
        }

        $this->path = $path;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath(): Path
    {
        return $this->path;
    }

    /**
     * Get path info.
     *
     * @return froq\file\PathInfo
     */
    public function getPathInfo(): PathInfo
    {
        return new PathInfo($this->path);
    }

    /**
     * Get stat.
     *
     * @return froq\file\Stat|null
     */
    public function getStat(): Stat|null
    {
        return $this->exists() ? new Stat($this->path) : null;
    }

    /**
     * Get directory.
     *
     * @param  bool|null $sort
     * @return froq\file\Directory|null
     */
    public function getDirectory(bool $sort = null): Directory|null
    {
        $path   = $this->path->getDirectory();
        $sort ??= $this->sort ?? null; // Directory objects.

        return ($path !== null) ? new Directory($path, ['sort' => $sort]) : null;
    }

    /**
     * Get root directory.
     *
     * @param  bool|null $sort
     * @return froq\file\Directory|null
     */
    public function getRootDirectory(bool $sort = null): Directory|null
    {
        $path   = $this->path->getRootDirectory();
        $sort ??= $this->sort ?? null; // Directory objects.

        return ($path !== null) ? new Directory($path, ['sort' => $sort]) : null;
    }

    /**
     * Get parent directory.
     *
     * @param  bool|null $sort
     * @return froq\file\Directory|null
     */
    public function getParentDirectory(bool $sort = null): Directory|null
    {
        $path   = $this->path->getParentDirectory();
        $sort ??= $this->sort ?? null; // Directory objects.

        return ($path !== null) ? new Directory($path, ['sort' => $sort]) : null;
    }

    /**
     * Check existence of this path.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->path->exists();
    }

    /**
     * Check existence & permissions of this path.
     *
     * @param  bool $read
     * @param  bool $write
     * @param  bool $execute
     * @return bool
     * @throws froq\file\PathObjectException If all arguments are false.
     */
    public function okay(bool $read = true, bool $write = false, bool $execute = false): bool
    {
        // Some speed & less work.
        if ($read && !$write && !$execute) {
            return $this->path->isReadable();
        }

        $ops = [];

        $read && $ops[] = 'read';
        $write && $ops[] = 'write';
        $execute && $ops[] = 'execute';

        try {
            return $this->path->isAvailableFor($ops);
        } catch (\Throwable $e) {
            throw new PathObjectException($e);
        }
    }

    /**
     * Check if path is readable.
     *
     * @param  bool $clear
     * @return bool
     */
    public function isReadable(bool $clear = false): bool
    {
        return $this->path->isReadable($clear);
    }

    /**
     * Check if path is writable.
     *
     * @param  bool $clear
     * @return bool
     */
    public function isWritable(bool $clear = false): bool
    {
        return $this->path->isWritable($clear);
    }

    /**
     * Check if path is executable.
     *
     * @param  bool $clear
     * @return bool
     */
    public function isExecutable(bool $clear = false): bool
    {
        return $this->path->isExecutable($clear);
    }

    /**
     * Check if path is in temporary directory.
     *
     * @return bool
     */
    public function isTemporary(): bool
    {
        return $this->path->isTemporary();
    }

    /**
     * Check if path is hidden.
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->path->isHidden();
    }

    /**
     * @alias create()
     */
    public function make(int $mode = null): bool
    {
        return $this->create($mode);
    }

    /**
     * Change mode of this path.
     *
     * @param  int $mode
     * @return bool
     * @throws froq\file\PathObjectException
     */
    public function mode(int $mode): bool
    {
        return @chmod($this->path->name, $mode)
            ?: throw PathObjectException::error();
    }

    /**
     * Touch this path.
     *
     * @param  int|null $mtime
     * @param  int|null $atime
     * @return bool
     * @throws froq\file\PathObjectException
     */
    public function touch(int $mtime = null, int $atime = null): bool
    {
        return @touch($this->path->name, $mtime, $atime)
            ?: throw PathObjectException::error();
    }

    /**
     * Make a link for this path.
     *
     * @param  string $link
     * @param  bool   $symlink
     * @return bool
     * @throws froq\file\PathObjectException
     */
    public function link(string $link, bool $symlink = true): bool
    {
        if ($symlink) {
            return @symlink($this->path->name, $link)
                ?: throw PathObjectException::error();
        }

        return @link($this->path->name, $link)
            ?: throw PathObjectException::error();
    }

    /**
     * Unlink this path if it's a link, otherwise throw a `PathObjectException`.
     *
     * @return bool
     * @throws froq\file\PathObjectException
     */
    public function unlink(): bool
    {
        if (is_link($this->path->name)) {
            return @unlink($this->path->name)
                ?: throw PathObjectException::error();
        }

        throw PathObjectException::forCannotUnlink($this->path->name);
    }

    /**
     * Rename this path.
     *
     * @param  string $to
     * @param  bool   $force
     * @return bool
     * @throws froq\file\PathObjectException
     */
    public function rename(string $to, bool $force = false): bool
    {
        $force || throw PathObjectException::forCannotRename($this->path->name);

        return @rename($this->path->name, $to)
            ?: throw PathObjectException::error();
    }

    /**
     * Remove this path.
     *
     * @param  bool $force
     * @return bool
     * @throws froq\file\PathObjectException
     */
    public function remove(bool $force = false): bool
    {
        $force || throw PathObjectException::forCannotRemove($this->path->name);

        if ($this instanceof File) {
            return @unlink($this->path->name)
                ?: throw PathObjectException::error();
        }

        if ($this instanceof Directory) {
            return @rmdir($this->path->name)
                ?: throw PathObjectException::error();
        }

        return false;
    }

    /**
     * Create a directory / file with this path.
     *
     * @param  int|null $mode
     * @return bool
     * @throws froq\file\PathObjectException
     */
    public function create(int $mode = null): bool
    {
        if ($this instanceof File) {
            return @mkfile($this->path->name, $mode ?? File::MODE)
                ?: throw PathObjectException::error();
        }

        if ($this instanceof Directory) {
            return @mkdir($this->path->name, $mode ?? Directory::MODE, true)
                ?: throw PathObjectException::error();
        }

        return false;
    }

    /**
     * Clear this path, if it's a directory empty it.
     *
     * @param  bool $force
     * @param  bool $exec For Unix only.
     * @return bool
     * @throws froq\file\PathObjectException
     */
    public function clear(bool $force = false, bool $exec = false): bool
    {
        $force || throw PathObjectException::forCannotClear($this->path->name);

        if ($this instanceof File) {
            return $this->empty();
        }

        if ($this instanceof Directory) {
            return $this->drop(true, $exec) && $this->create();
        }

        return false;
    }

    /**
     * Drop this path, if it's a directory empty it first.
     *
     * @param  bool $force
     * @param  bool $exec For Unix only.
     * @return bool
     * @throws froq\file\PathObjectException
     */
    public function drop(bool $force = false, bool $exec = false): bool
    {
        $force || throw PathObjectException::forCannotDrop($this->path->name);

        if ($this instanceof File) {
            return $this->dropFile($this->path->name);
        }

        if ($this instanceof Directory) {
            return $this->dropDirectory($this->path->name, $exec);
        }

        return false;
    }

    /**
     * Drop a file.
     *
     * @throws froq\file\PathObjectException
     */
    private function dropFile(string $path): bool
    {
        clearstatcache(true, $path);

        return @unlink($path) ?: throw PathObjectException::error();
    }

    /**
     * Drop a directory (clearing inside recursively).
     *
     * @throws froq\file\PathObjectException
     */
    private function dropDirectory(string $path, bool $exec = false): bool
    {
        clearstatcache(true, $path);

        return @$this->rmrf($exec)($path) ?: throw PathObjectException::error();
    }

    /**
     * Create a `rm -rf` function that will completely drop a directory.
     *
     * Note: Option `$exec` for only Unix systems to bypass `rm` command limit
     * when the count of files too large inside.
     */
    private function rmrf(bool $exec): \Closure
    {
        return $exec ?
            // Fastest so far.
            function (string $root): bool {
                try {
                    exec(
                        'find ' . escapeshellarg($root) . ' ' .
                        '-type f -print0 | xargs -0 rm 2>/dev/null'
                    );

                    // @tome: No need for $exec here (recursion!).
                    return $this->dropDirectory($root, false);
                } catch (\Throwable) {
                    return false;
                }
            } :
            // Use glob.
            function (string $root): bool {
                $ret = true;

                foreach (glob($root . '/*', GLOB_NOSORT) as $pat) {
                    if (is_file($pat) || is_link($pat)) {
                        $ret = $this->dropFile($pat);
                    } elseif (is_dir($pat)) {
                        $ret = $this->dropDirectory($pat, false);
                    }
                }

                return $ret && rmdir($root);
            }
        ;
    }
}
