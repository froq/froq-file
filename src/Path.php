<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * Base class for directory / file classes.
 *
 * @package froq\file
 * @class   froq\file\Path
 * @author  Kerem Güneş
 * @since   7.0
 */
abstract class Path
{
    /** Path name. */
    private string $path;

    /** Path info. */
    private PathInfo $info;

    /**
     * Constructor.
     *
     * @param  string $path
     * @throws froq\file\PathException
     */
    public function __construct(string $path)
    {
        try {
            $this->info = new PathInfo($path);
            $this->path = $this->info->getPath();
        } catch (\Throwable $e) {
            throw PathException::exception($e);
        }
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
     * Get path info.
     *
     * @return froq\file\PathInfo
     */
    public function getPathInfo(): PathInfo
    {
        return $this->info;
    }

    /**
     * Check existence of this path.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->info->exists();
    }

    /**
     * Check permissions of this path.
     *
     * @param  bool $read
     * @param  bool $write
     * @param  bool $execute
     * @return bool
     * @throws froq\file\PathException If all arguments are false.
     */
    public function okay(bool $read = true, bool $write = false, bool $execute = false): bool
    {
        // Some speed & less work.
        if ($read && !$write && !$execute) {
            return $this->info->exists();
        }

        $ops = [];

        $read && $ops[] = 'read';
        $write && $ops[] = 'write';
        $execute && $ops[] = 'execute';

        try {
            return $this->info->isAvailableFor($ops);
        } catch (\Throwable $e) {
            throw new PathException($e);
        }
    }

    /**
     * Change mode of this path.
     *
     * @param  int $mode
     * @return bool
     * @throws froq\file\PathException
     */
    public function mode(int $mode): bool
    {
        return @chmod($this->path, $mode) ?: throw PathException::error();
    }

    /**
     * Touch this path.
     *
     * @param  int|null $mtime
     * @param  int|null $atime
     * @return bool
     * @throws froq\file\PathException
     */
    public function touch(int $mtime = null, int $atime = null): bool
    {
        return @touch($this->path, $mtime, $atime) ?: throw PathException::error();
    }

    /**
     * Make a link for this path.
     *
     * @param  string $link
     * @param  bool   $symlink
     * @return bool
     * @throws froq\file\PathException
     */
    public function link(string $link, bool $symlink = true): bool
    {
        if ($symlink) {
            return @symlink($this->path, $link) ?: throw PathException::error();
        }
        return @link($this->path, $link) ?: throw PathException::error();
    }

    /**
     * Unlink this path if it's a link, otherwise throw a `PathException`.
     *
     * @return bool
     * @throws froq\file\PathException
     */
    public function unlink(): bool
    {
        if (is_link($this->path)) {
            return @unlink($this->path) ?: throw PathException::error();
        }
        throw PathException::forCannotUnlink($this->path);
    }

    /**
     * Rename this path.
     *
     * @param  string $to
     * @param  bool   $force
     * @return bool
     * @throws froq\file\PathException
     */
    public function rename(string $to, bool $force = false): bool
    {
        $force || throw PathException::forCannotRename($this->path);

        return @rename($this->path, $to) ?: throw PathException::error();
    }

    /**
     * Remove this path.
     *
     * @param  bool $force
     * @return bool
     * @throws froq\file\PathException
     */
    public function remove(bool $force = false): bool
    {
        $force || throw PathException::forCannotRemove($this->path);

        if ($this instanceof File) {
            return @unlink($this->getPath()) ?: throw PathException::error();
        }

        if ($this instanceof Directory) {
            return @rmdir($this->getPath()) ?: throw PathException::error();
        }

        return false;
    }

    /**
     * Create a directory / file with this path.
     *
     * @param  int|null $mode
     * @return bool
     * @throws froq\file\PathException
     */
    public function create(int $mode = null): bool
    {
        if ($this instanceof File) {
            return @mkfile($this->path, $mode ?? File::MODE)
                ?: throw PathException::error();
        }

        if ($this instanceof Directory) {
            return @mkdir($this->path, $mode ?? Directory::MODE, true)
                ?: throw PathException::error();
        }

        return false;
    }

    /**
     * Clear this path, if it's a directory empty it.
     *
     * @param  bool $force
     * @param  bool $exec
     * @return bool
     * @throws froq\file\PathException
     */
    public function clear(bool $force = false, bool $exec = false): bool
    {
        $force || throw PathException::forCannotClear($this->path);

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
     * @param  bool $exec
     * @return bool
     * @throws froq\file\PathException
     */
    public function drop(bool $force = false, bool $exec = false): bool
    {
        $force || throw PathException::forCannotDrop($this->path);

        if ($this instanceof File) {
            return $this->dropFile($this->path);
        }

        if ($this instanceof Directory) {
            foreach ($this->read(sort: false) as $path) {
                if (is_file($path) || is_link($path)) {
                    $this->dropFile($path);
                } elseif (is_dir($path)) {
                    $this->dropDirectory($path, $exec);
                }
            }

            return $this->dropDirectory($this->path, $exec);
        }

        return false;
    }

    /**
     * Drop a file.
     *
     * @throws froq\file\PathException
     */
    private function dropFile(string $path): bool
    {
        clearstatcache(true, $path);

        return @unlink($path) ?: throw PathException::error();
    }

    /**
     * Drop a directory clearing inside recursively.
     *
     * Note: Option `$exec` for only Unix systems to bypass `rm` command limit
     * when the count of files too large.
     *
     * @throws froq\file\PathException
     */
    private function dropDirectory(string $path, bool $exec = false): bool
    {
        if (!$exec) {
            // Use glob utility.
            $rmrf = function (string $root): bool {
                $ret = false;

                foreach (glob($root . '/*', GLOB_NOSORT) as $path) {
                    if (is_file($path) || is_link($path)) {
                        $ret = $this->dropFile($path);
                    } elseif (is_dir($path)) {
                        $ret = $this->dropDirectory($path);
                    }

                    if (!$ret) {
                        return false;
                    }
                }

                return rmdir($root);
            };
        } else {
            // Fastest way so far & for Unix only.
            $rmrf = function (string $root): bool {
                try {
                    exec(
                        'find ' . escapeshellarg($root) . ' ' .
                        '-type f -print0 | xargs -0 rm 2>/dev/null'
                    );

                    return $this->dropDirectory($root);
                } catch (\Throwable) {
                    return false;
                }
            };
        }

        clearstatcache(true, $path);

        return @$rmrf($path, $exec) ?: throw PathException::error();
    }
}
