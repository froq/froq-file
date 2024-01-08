<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * Static class with some utilities for file system related works.
 *
 * @package froq\file
 * @class   froq\file\FileSystem
 * @author  Kerem Güneş
 * @since   7.0
 * @static
 */
class FileSystem
{
    /**
     * Get a path stat.
     *
     * @param  string $path
     * @return froq\file\Stat
     * @throws froq\file\FileSystemException
     */
    public static function getStat(string $path): Stat
    {
        try {
            return new Stat($path);
        } catch (StatException $e) {
            throw new FileSystemException($e);
        }
    }

    /**
     * Get a path.
     *
     * @param  string $path
     * @return froq\file\Path
     * @throws froq\file\FileSystemException
     */
    public static function getPath(string $path): Path
    {
        try {
            return new Path($path);
        } catch (PathException $e) {
            throw new FileSystemException($e);
        }
    }

    /**
     * Get a path info.
     *
     * @param  string $path
     * @return froq\file\PathInfo
     * @throws froq\file\FileSystemException
     */
    public static function getPathInfo(string $path): PathInfo
    {
        try {
            return new PathInfo($path);
        } catch (PathInfoException $e) {
            throw new FileSystemException($e);
        }
    }

    /**
     * Open a directory.
     *
     * @param  string|Path $path
     * @param  array|null  $options
     * @return froq\file\Directory
     * @throws froq\file\FileSystemException
     */
    public static function openDirectory(string|Path $path, array $options = null): Directory
    {
        try {
            $ret = new Directory($path, $options);
            return $ret->open();
        } catch (DirectoryException $e) {
            throw new FileSystemException($e);
        }
    }

    /**
     * Open a file.
     *
     * @param  string|Path $path
     * @param  array|null  $options
     * @return froq\file\File
     * @throws froq\file\FileSystemException
     */
    public static function openFile(string|Path $path, array $options = null): File
    {
        try {
            $ret = new File($path, $options);
            return $ret->open($options['mode'] ?? 'rb');
        } catch (FileException $e) {
            throw new FileSystemException($e);
        }
    }

    /**
     * Make a directory, return its path.
     *
     * @param  string $path
     * @param  int    $mode
     * @param  bool   $temp
     * @return string
     * @throws froq\file\FileSystemException
     */
    public static function makeDirectory(string $path, int $mode = Directory::MODE, bool $temp = false): string
    {
        return @dirmake($path, $mode, $temp, check: false)
            ?: throw FileSystemException::error();
    }

    /**
     * Make a file, return its path.
     *
     * @param  string $path
     * @param  int    $mode
     * @param  bool   $temp
     * @return string
     * @throws froq\file\FileSystemException
     */
    public static function makeFile(string $path, int $mode = File::MODE, bool $temp = false): string
    {
        return @filemake($path, $mode, $temp, check: false)
            ?: throw FileSystemException::error();
    }

    /**
     * Remove a directory.
     *
     * @param  string $path
     * @return bool
     * @throws froq\file\FileSystemException
     */
    public static function removeDirectory(string $path): bool
    {
        return @rmdir($path)
            ?: throw FileSystemException::error();
    }

    /**
     * Remove a file.
     *
     * @param  string $path
     * @return bool
     * @throws froq\file\FileSystemException
     */
    public static function removeFile(string $path): bool
    {
        return @rmfile($path)
            ?: throw FileSystemException::error();
    }

    /**
     * Read a file.
     *
     * @param  string   $file
     * @param  int      $offset
     * @param  int|null $length
     * @return string
     * @throws froq\file\FileSystemException
     */
    public static function readFile(string $file, int $offset = 0, int $length = null): string
    {
        return @file_read($file, $offset, $length)
            ?? throw FileSystemException::error();
    }

    /**
     * Write a file.
     *
     * @param  string $file
     * @param  string $data
     * @param  int    $flags
     * @param  bool   $append
     * @return int
     * @throws froq\file\FileSystemException
     */
    public static function writeFile(string $file, string $data, int $flags = 0, bool $append = false): int
    {
        return @file_write($file, $data, $flags, $append)
            ?? throw FileSystemException::error();
    }

    /**
     * Get splitted paths as a tree.
     *
     * Example: `getPathTree('/tmp/foo')` => `['/', '/tmp', '/tmp/foo']`.
     *
     * @param  string $path
     * @param  bool   $normalize
     * @param  bool   $convert
     * @return array<string|Path>
     */
    public static function getPathTree(string $path, bool $normalize = true, bool $convert = false): array
    {
        $paths = self::splitPaths($path, $normalize);

        // Search "/" and "~" (home) chars.
        $pfx = ($path !== DIRECTORY_SEPARATOR)
            && strpfx($path, [DIRECTORY_SEPARATOR, '~']);

        $ret = [];

        if ($paths) {
            foreach ($paths as $i => $path) {
                if ($i === 0) {
                    $ret[] = $path;
                } else {
                    $prev  = $ret[$i - 1]; // Append as parent.
                    $ret[] = join(DIRECTORY_SEPARATOR, [$prev, $path]);
                }
            }

            if ($pfx) {
                 if ($ret[0] === '') {
                    // Put "/" into first.
                    $ret[0] = DIRECTORY_SEPARATOR;
                } elseif ($ret[0] !== '' && $ret[0] !== '~') {
                    // Append "/" to all (if normalized).
                    $ret = map($ret, fn($p) => DIRECTORY_SEPARATOR . $p);
                }
            }

            // Convert all paths to Path instances.
            $convert && $ret = map($ret, fn($p) => new Path($p));
        }

        return $ret;
    }

    /**
     * Count paths returning size of parts, or return -1 if path is empty.
     *
     * Note: This method must be used `normalize: false` option for non-resolved results.
     * For example, countPaths('x') = count(cwd-path-parts) + 1, countPaths('x', false) = 1.
     *
     * @param  string $path
     * @param  bool   $normalize
     * @return int
     */
    public static function countPaths(string $path, bool $normalize = true): int
    {
        $paths = self::splitPaths($path, $normalize);

        return $paths ? count($paths) : -1; // Empty path.
    }

    /**
     * Split paths.
     *
     * @param  string $path
     * @param  bool   $normalize
     * @return array<string>
     */
    public static function splitPaths(string $path, bool $normalize = true): array
    {
        if ($path === '' || $path === DIRECTORY_SEPARATOR) {
            return !$path ? [] : ['']; // 2nd is root.
        }

        $path = $normalize ? self::normalizePath($path) : $path;

        // Cos of normalize.
        if ($path === null) {
            return [];
        }

        // Keep ticking root part.
        if (strpfx($path, DIRECTORY_SEPARATOR)) {
            $path = '@@@' . $path;
        }

        $paths = split(DIRECTORY_SEPARATOR, $path);

        // Restore root part.
        if ($paths && $paths[0] === '@@@') {
            $paths[0] = '';
        }

        return $paths;
    }

    /**
     * Join paths.
     *
     * @param  array<string> $paths
     * @param  bool          $normalize
     * @return string
     */
    public static function joinPaths(array $paths, bool $normalize = true): string
    {
        if ($paths === []) {
            return '';
        }

        $path = join(DIRECTORY_SEPARATOR, $paths);

        $path = $normalize ? self::normalizePath($path) : $path;

        // Cos of normalize.
        if ($path === null) {
            return '';
        }

        return $path;
    }

    /**
     * Resolve a path.
     *
     * @param  string $path
     * @return string|null
     */
    public static function resolvePath(string $path): string|null
    {
        return get_real_path($path, check: true, real: true);
    }

    /**
     * Normalize a path.
     *
     * @param  string $path
     * @return string|null
     */
    public static function normalizePath(string $path): string|null
    {
        return get_real_path($path, check: null, real: false);
    }
}
