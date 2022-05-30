<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file;

use froq\file\mime\{Mime, MimeException};
use froq\file\object\{FileObject, FileObjectException};

/**
 * A static file utility class.
 *
 * @package froq\file
 * @object  froq\file\File
 * @author  Kerem Güneş
 * @since   3.0, 4.0
 * @static
 */
final class File extends \StaticClass
{
    /**
     * Get file mime.
     *
     * @param  string $file
     * @return string|null
     */
    public static function getMime(string $file): string|null
    {
        try { return Mime::getType($file); }
            catch (MimeException) { return null; }
    }

    /**
     * Get file extension.
     *
     * @param  string $file
     * @return string|null
     */
    public static function getExtension(string $file): string|null
    {
        return Mime::getExtension($file);
    }

    /**
     * Check whether given path is a file.
     *
     * @param  string $path
     * @return bool|null
     */
    public static function isFile(string $path): bool|null
    {
        // Errors happen in strict mode, else warning only.
        try { return is_file($path); }
            catch (\Error) { return null; }
    }

    /**
     * Check whether given path is a directory.
     *
     * @param  string $path
     * @return bool|null
     */
    public static function isDirectory(string $path): bool|null
    {
        // Errors happen in strict mode, else warning only.
        try { return is_dir($path); }
            catch (\Error) { return null; }
    }

    /**
     * Check whether a file is readable.
     *
     * @param  string $file
     * @return bool
     */
    public static function isReadable(string $file): bool
    {
        return self::isFile($file) && is_readable($file);
    }

    /**
     * Check whether a file is writable.
     *
     * @param  string $file
     * @return bool
     */
    public static function isWritable(string $file): bool
    {
        return self::isFile($file) && is_writable($file);
    }

    /**
     * Check whether a file is available to read/write.
     *
     * @param  string $file
     * @return bool
     * @since  6.0
     */
    public static function isAvailable(string $file): bool
    {
        return self::isReadable($file) && self::isWritable($file);
    }

    /**
     * Make a file.
     *
     * @param  string $file
     * @param  int    $mode
     * @param  bool   $temp
     * @return bool
     * @throws froq\file\FileException
     * @since  6.0
     */
    public static function make(string $file, int $mode = 0644, bool $temp = false): bool
    {
        return @file_create($file, $mode, $temp) ?: throw new FileException('@error');
    }

    /**
     * Remove a file.
     *
     * @param  string $file
     * @return bool
     * @throws froq\file\FileException
     * @since  6.0
     */
    public static function remove(string $file): bool
    {
        return @file_remove($file) ?: throw new FileException('@error');
    }

    /**
     * Open a file as FileObject.
     *
     * @param  string      $file
     * @param  string      $mode
     * @param  string|null $mime
     * @param  array|null  $options
     * @return froq\file\FileObject
     * @throws froq\file\FileException
     */
    public static function open(string $file, string $mode = 'r+b', string $mime = null, array $options = null): FileObject
    {
        $options['mode'] = $mode;

        try {
            return FileObject::fromFile($file, $mime, $options);
        } catch (FileObjectException $e) {
            throw new FileException($e->message, code: $e->code, cause: $e->cause ?? $e);
        }
    }

    /**
     * Open a temp file as FileObject.
     *
     * @param  string      $file
     * @param  string      $mode
     * @param  string|null $mime
     * @param  array|null  $options
     * @return froq\file\FileObject
     * @throws froq\file\FileException
     */
    public static function openTemp(string $mode = 'w+b', string $mime = null, array $options = null): FileObject
    {
        $options['mode'] = $mode;

        try {
            return FileObject::fromTempFile($mime, $options);
        } catch (FileObjectException $e) {
            throw new FileException($e->message, code: $e->code, cause: $e->cause ?? $e);
        }
    }

    /**
     * Read entire contents from a file/stream.
     *
     * @param  mixed<string|resource> $file
     * @return string
     * @throws froq\file\FileException
     * @since  4.0
     */
    public static function getContents(mixed $file): string
    {
        if (is_string($file)) {
            if (is_dir($file)) {
                throw new FileException(
                    'Cannot write file, it\'s a directory [file: %s]', $file
                );
            }
            if (!file_exists($file)) {
                throw new FileException(
                    'Cannot read file, it\'s not existing [file: %s]', $file
                );
            }
            if (!is_readable($file)) {
                throw new FileException(
                    'Cannot read file, it\'s not readable [file: %s]', $file
                );
            }

            $ret =@ file_get_contents($file);
        } elseif (is_stream($file)) {
            $ret =@ stream_get_contents($file, -1, 0);
        } else {
            throw new FileException(
                'Invalid file type `%s` [valids: string,stream]', $type
            );
        }

        if ($ret === false) {
            throw new FileException(
                'Cannot read file [file: %s, error: %s]', [$file, '@error']
            );
        }

        return $ret;
    }

    /**
     * Write given contents entirely into a file/stream.
     *
     * @param  mixed<string|resource> $file
     * @param  string                 $contents
     * @param  int                    $flags
     * @return bool
     * @throws froq\file\FileException
     * @since  4.0
     */
    public static function setContents(mixed $file, string $contents, int $flags = 0): bool
    {
        if (is_string($file)) {
            if (is_dir($file)) {
                throw new FileException(
                    'Cannot write file, it\'s a directory [file: %s]', $file
                );
            }
            if (file_exists($file) && !is_writable($file)) {
                throw new FileException(
                    'Cannot write file, it\'s not writable [file: %s]', $file
                );
            }

            $ret =@ file_set_contents($file, $contents, $flags);
        } elseif (is_stream($file)) {
            $ret =@ stream_set_contents($file, $contents);
        } else {
            throw new FileException(
                'Invalid file type `%s` [valids: string,stream]', $type
            );
        }

        if ($ret === null) {
            throw new FileException(
                'Cannot write file [file: %s, error: %s]', [$file, '@error']
            );
        }

        return true;
    }

    /**
     * Set/get file mode.
     *
     * @param  string   $file
     * @param  int|null $mode
     * @return string|null
     * @throws froq\file\FileException
     * @since  4.0
     */
    public static function mode(string $file, int|bool $mode = null): string|null
    {
        if ($mode !== null) {
            // Set mode.
            if (is_int($mode)) {
                $ret =@ chmod($file, $mode);
                if ($ret === false) {
                    throw new FileException(
                        'Cannot set file mode [file: %s, error: %s]',
                        [$file, '@error']
                    );
                }
                $ret = $mode;
            }
            // Get mode.
            else {
                $ret =@ fileperms($file);
                if ($ret === false) {
                    throw new FileException(
                        'Cannot get file stat [file: %s, error: %s]',
                        [$file, '@error']
                    );
                }
            }

            // Comparing.
            // $mode = mode($file, true)
            // $mode === '0644' or octdec($mode) === 0644
            return $ret ? ('0' . decoct($ret & 0777)) : null;
        }

        // Get full permissions.
        $perms =@ fileperms($file);
        if ($perms === false) {
            throw new FileException(
                'Cannot get file stat [file: %s, error: %s]',
                [$file, '@error']
            );
        }

        // Source http://php.net/fileperms.
        $ret = match ($perms & 0xf000) {
             0xc000 => 's', // Socket.
             0xa000 => 'l', // Symbolic link.
             0x8000 => 'r', // Regular.
             0x6000 => 'b', // Block special.
             0x4000 => 'd', // Directory.
             0x2000 => 'c', // Character special.
             0x1000 => 'p', // FIFO pipe.
            default => 'u', // Unknown.
        };

        // Owner.
        $ret .= (($perms & 0x0100) ? 'r' : '-');
        $ret .= (($perms & 0x0080) ? 'w' : '-');
        $ret .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));

        // Group.
        $ret .= (($perms & 0x0020) ? 'r' : '-');
        $ret .= (($perms & 0x0010) ? 'w' : '-');
        $ret .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));

        // World.
        $ret .= (($perms & 0x0004) ? 'r' : '-');
        $ret .= (($perms & 0x0002) ? 'w' : '-');
        $ret .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));

        return $ret;
    }

    /**
     * Check error state.
     *
     * @param  string                    $file
     * @param  froq\file\FileError|null &$error
     * @return bool
     */
    public static function errorCheck(string $file, FileError &$error = null): bool
    {
        $error = null;

        if (str_contains($file, "\0")) {
            $error = new FileError(
                'No valid path, path contains NULL-bytes',
                code: FileError::NO_VALID_PATH
            );
            return true;
        } elseif (trim($file) === '') {
            $error = new FileError(
                'No valid path, path is empty',
                code: FileError::NO_VALID_PATH
            );
            return true;
        }

        // Sadly is_file(), is_readable(), stat() even SplFileInfo is not giving a proper error when
        // a 'permission' / 'not exists' / 'null byte (\0)' error occurs, or path is a directory. :/
        // Also seems not documented on php.net but when $filename contains null byte (\0) then a
        // TypeError will be thrown with message such: TypeError: fopen() expects parameter 1 to be
        // a valid path, string given in..
        $fp = null;
        try {
            $fp =@ fopen($file, 'r');
        } catch (\Error $e) {
            $error = $e->getMessage();
        }

        if ($fp) {
            fclose($fp);

            if (is_dir($file)) {
                $error = new FileError(
                    'Given path is a directory [path: %s]',
                    $file, FileError::DIRECTORY
                );
            } // else ok.
        } else {
            $error ??= error_message() ?? 'Unknown error';

            if (stripos($error, 'no such file')) {
                $error = new FileError(
                    'No file exists [file: %s]',
                    $file, FileError::NO_FILE_EXISTS
                );
            } elseif (stripos($error, 'permission denied')) {
                $error = new FileError(
                    'No access permission [file: %s]',
                    $file, FileError::NO_ACCESS_PERMISSION
                );
            } elseif (stripos($error, 'valid path') || stripos($error, 'null bytes')) {
                $path  = strtr(substr($file, 0, 255), ["\0" => "\\0"]) . '...';
                $error = new FileError(
                    'No valid path [path: %s]',
                    $path, FileError::NO_VALID_PATH
                );
            } else {
                $error = new FileError($error);
            }
        }

        return ($error != null);
    }
}
