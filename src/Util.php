<?php
/**
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Copyright (c) 2015 Kerem Güneş
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace froq\file;

use froq\file\{Mime, MimeException, FileError};
use Error;

/**
 * Util.
 * @package froq\file
 * @object  froq\file\Util
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 * @static
 */
final class Util
{
    /**
     * Get type.
     * @param  string $file
     * @return ?string
     */
    public static function getType(string $file): ?string
    {
        try { return Mime::getType($file); } catch (MimeException $e) {
            return null; // Error.
        }
    }

    /**
     * Get extension.
     * @param  string $file
     * @return ?string
     */
    public static function getExtension(string $file): ?string
    {
        return Mime::getExtension($file);
    }

    /**
     * Is file.
     * @param  string $path
     * @return ?bool
     */
    public static function isFile(string $path): ?bool
    {
        // Errors happen in strict mode, else warning only.
        try { return @is_file($path); } catch (Error $e) {
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
        try { return @is_dir($path); } catch (Error $e) {
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

        if (preg_match('~([\d\.]+)(\w)~', $bytes, $match)) {
            [, $bytes, $unit] = $match;
            return (int) ($bytes * pow($base, array_search(strtoupper($unit), $units)));
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
        // Sadly is_file(),is_readable(),stat() even SplFileInfo etc. not giving a proper error
        // when a 'permission' / 'not exists' / 'null byte (\0)' error occurs, or path is a
        // directory.. :/
        // Also seems not documented on php.net but when $filename contains null byte (\0) then a
        // TypeError will be thrown with message such: TypeError: fopen() expects parameter 1 to be
        // a valid path, string given in..
        $fp = false;
        try {
            $fp =@ fopen($file, 'r');
        } catch (Error $e) {
            $error = $e->getMessage();
        }

        if ($fp) {
            fclose($fp);

            if (is_dir($file)) {
                $error = new FileError('Given path "%s" is a directory', [$file],
                    FileError::DIRECTORY_GIVEN);
            }
        } else {
            $error = $error ?? error_get_last()['message'] ?? 'Unknown error';
            if (stripos($error, 'no such file')) {
                $error = new FileError('File "%s" is not exists', [$file],
                    FileError::NO_SUCH_FILE);
            } elseif (stripos($error, 'permission denied')) {
                $error = new FileError('Permission denied for file "%s"', [$file],
                    FileError::PERMISSION_DENIED);
            } elseif (stripos($error, 'valid path')) {
                $error = new FileError('Invalid path "%s" given', [str_replace("\0", "\\0", $file)],
                    FileError::INVALID_PATH);
            } else {
                $error = new FileError($error);
            }
        }

        return ($error != null);
    }
}
