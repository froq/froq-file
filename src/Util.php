<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file;

use froq\file\FileError;
use Error;

/**
 * Util.
 *
 * @package froq\file
 * @object  froq\file\Util
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 * @static
 */
final class Util
{
    /**
     * Is file.
     * @param  string $path
     * @return ?bool
     */
    public static function isFile(string $path): ?bool
    {
        // Errors happen in strict mode, else warning only.
        try { return is_file($path); } catch (Error $e) {
            return null; // Error.
        }
    }

    /**
     * Is directory.
     * @param  string $path
     * @return ?bool
     */
    public static function isDirectory(string $path): ?bool
    {
        // Errors happen in strict mode, else warning only.
        try { return is_dir($path); } catch (Error $e) {
            return null; // Error.
        }
    }

    /**
     * Format bytes.
     * @param  int $bytes
     * @return string
     */
    public static function formatBytes(int $bytes): string
    {
        static $base = 1024, $units = ['B', 'KB', 'MB', 'GB'];

        $i = 0;
        while ($bytes > $base) {
            $i++; $bytes /= $base;
        }

        return round($bytes, 2) . $units[$i];
    }


    /**
     * Convert bytes.
     * @param  string $bytes
     * @return int
     */
    public static function convertBytes(string $bytes): int
    {
        static $base = 1024, $units = ['', 'K', 'M', 'G'];

        // Eg: 6.4M or 6.4MB => 6.4MB, 64M or 64MB => 64MB.
        if (sscanf($bytes, '%f%c', $byte, $unit) == 2) {
            return (int) ($byte * pow($base, array_search(strtoupper($unit), $units)));
        }

        return (int) $bytes;
    }

    /**
     * Error check.
     * @param  string $file
     * @param  froq\file\FileError|null &$error
     * @return bool
     */
    public static function errorCheck(string $file, FileError &$error = null): bool
    {
        // Sadly is_file(), is_readable(), stat() even SplFileInfo is not giving a proper error
        // when a 'permission' / 'not exists' / 'null byte (\0)' error occurs, or path is a
        // directory.. :/
        // Also seems not documented on php.net but when $filename contains null byte (\0) then a
        // TypeError will be thrown with message such: TypeError: fopen() expects parameter 1 to be
        // a valid path, string given in..
        $fp = false;
        try {
            $fp = fopen($file, 'r');
        } catch (Error $e) {
            $error = $e->getMessage();
        }

        if ($fp) {
            fclose($fp);

            if (is_dir($file)) {
                $error = new FileError("Given path '%s' is a directory",
                    $file, FileError::DIRECTORY_GIVEN);
            }
        } else {
            $error = $error ?? error_message() ?? 'Unknown error';
            if (stripos($error, 'valid path')) {
                $error = new FileError("No valid path '%s' given",
                    strtr($file, ["\0" => "\\0"]), FileError::NO_VALID_PATH);
            } elseif (stripos($error, 'no such file')) {
                $error = new FileError("No file such '%s' is exists",
                    $file, FileError::NO_SUCH_FILE);
            } elseif (stripos($error, 'permission denied')) {
                $error = new FileError("No permission for accessing to '%s' file",
                    $file, FileError::NO_PERMISSION);
            } else {
                $error = new FileError($error);
            }
        }

        return ($error != null);
    }
}
