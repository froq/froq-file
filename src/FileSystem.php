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
     * @param  string     $path
     * @param  array|null $options
     * @return froq\file\Directory
     * @throws froq\file\FileSystemException
     */
    public static function openDirectory(string $path, array $options = null): Directory
    {
        try {
            $ret = new Directory($path);
            return $ret->open();
        } catch (DirectoryException $e) {
            throw new FileSystemException($e);
        }
    }

    /**
     * Open a file.
     *
     * @param  string     $path
     * @param  array|null $options
     * @return froq\file\File
     * @throws froq\file\FileSystemException
     */
    public static function openFile(string $path, array $options = null): File
    {
        try {
            $ret = new File($path);
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
     * @return int
     * @throws froq\file\FileSystemException
     */
    public static function writeFile(string $file, string $data, int $flags = 0): int
    {
        return @file_write($file, $data, $flags)
            ?? throw FileSystemException::error();
    }

    /**
     * Append a file.
     *
     * @param  string $file
     * @param  string $data
     * @param  int    $flags
     * @return int
     * @throws froq\file\FileSystemException
     */
    public static function appendFile(string $file, string $data, int $flags = 0): int
    {
        return @file_write($file, $data, $flags |= FILE_APPEND)
            ?? throw FileSystemException::error();
    }

    /**
     * Split paths.
     *
     * @param  string $path
     * @return array
     */
    public static function splitPaths(string $path): array
    {
        return split(DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Join paths.
     *
     * @param  string ...$paths
     * @return string
     */
    public static function joinPaths(string ...$paths): string
    {
        return join($s = DIRECTORY_SEPARATOR, map($paths, fn($n) => trim($n, $s)));
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
