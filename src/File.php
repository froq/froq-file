<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file;

use froq\file\mime\{Mime, MimeException};
use froq\file\FileException;

/**
 * File.
 *
 * @package froq\file
 * @object  froq\file\File
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0, 4.0 Made static, added getType(),getExtension(),read(),write(),mode(), moved all other stuff
 *          into AbstractUploader.
 * @static
 */
final class File
{
    /**
     * Get file type.
     *
     * @param  string $file
     * @return string|null
     */
    public static function getType(string $file): string|null
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
            catch (Error) { return null; }
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
            catch (Error) { return null; }
    }

    /**
     * Check whether a file is existing.
     *
     * @param  string $file
     * @return bool
     */
    public static function isExists(string $file): bool
    {
        return self::isFile($file);
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
     * Read entire contents from a file/stream.
     *
     * @param  string|resource $file
     * @return string
     * @throws froq\file\FileException
     * @since  4.0
     */
    public static function read($file): string
    {
        if (is_string($file)) {
            $ret = file_get_contents($file);
        } elseif (is_stream($file)) {
            $ret = stream_get_contents($file, -1, 0);
        } else {
            throw new FileException('Invalid file type %s, valids are: string, stream', $type);
        }

        if ($ret === false) {
            throw new FileException('Cannot read file [error: %s, file: %s]', ['@error', $file]);
        }

        return $ret;
    }

    /**
     * Write given contents entirely into a file/stream.
     *
     * @param  string|resource $file
     * @param  string          $contents
     * @param  int             $flags
     * @return bool
     * @throws froq\file\FileException
     * @since  4.0
     */
    public static function write($file, string $contents, int $flags = 0): bool
    {
        if (is_string($file)) {
            $ret = file_set_contents($file, $contents, $flags) !== null;
        } elseif (is_stream($file)) {
            $ret = stream_set_contents($file, $contents);
        } else {
            throw new FileException('Invalid file type %s, valids are: string, stream', $type);
        }

        if ($ret === false) {
            throw new FileException('Cannot write file [error: %s, file: %s]', ['@error', $file]);
        }

        return true;
    }

    /**
     * Set/get file mode.
     *
     * @param  string   $file
     * @param  int|null $mode
     * @return string
     * @throws froq\file\FileException
     * @since  4.0
     */
    public static function mode(string $file, int $mode = null): string
    {
        if ($mode !== null) {
            if ($mode > -1) { // Set mode.
                $ret = chmod($file, $mode);
                if ($ret === false) {
                    throw new FileException("Cannot set file mode [error: %s, file: %s]",
                        ['@error', $file]);
                }
                $ret = $mode;
            } else { // Get mode.
                $ret = fileperms($file);
                if ($ret === false) {
                    throw new FileException("Cannot get file stat for '%s'", $file);
                }
            }

            // Comparing.
            // $mode = File::mode($file, -1)
            // $mode === '644' or octdec($mode) === 0644
            return $ret ? decoct($ret & 0777) : null;
        }

        // Get full permissions.
        $perms = fileperms($file);
        if ($perms === false) {
            throw new FileException("Cannot get file stat for '%s'", $file);
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
        // Sadly is_file(), is_readable(), stat() even SplFileInfo is not giving a proper error when
        // a 'permission' / 'not exists' / 'null byte (\0)' error occurs, or path is a directory. :/
        // Also seems not documented on php.net but when $filename contains null byte (\0) then a
        // TypeError will be thrown with message such: TypeError: fopen() expects parameter 1 to be
        // a valid path, string given in..
        $fp = null;
        try {
            $fp = fopen($file, 'r');
        } catch (Error $e) {
            $error = $e->getMessage();
        }

        if ($fp) {
            fclose($fp);

            if (is_dir($file)) {
                $error = new FileError(
                    "Given path '%s' is a directory",
                    get_real_path($file), FileError::DIRECTORY_GIVEN
                );
            } // else ok.
        } else {
            $error = $error ?? error_message() ?? 'Unknown error';

            if (stripos($error, 'valid path')) {
                $error = new FileError(
                    "No valid path '%s' given",
                    strtr($file, ["\0" => "\\0"]), FileError::INVALID_PATH
                );
            } elseif (stripos($error, 'no such file')) {
                $error = new FileError(
                    "No file exists such '%s'",
                    get_real_path($file), FileError::NO_FILE
                );
            } elseif (stripos($error, 'permission denied')) {
                $error = new FileError(
                    "No permission for accessing to '%s' file",
                    get_real_path($file), FileError::NO_PERMISSION
                );
            } else {
                $error = new FileError($error);
            }
        }

        return ($error != null);
    }
}
