<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file;

use froq\file\mime\{Mime, MimeException};
use froq\file\{FileError, Util as FileUtil};

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
     * Check whether a file is existing.
     *
     * @param  string $file
     * @return bool
     */
    public static function isExists(string $file): bool
    {
        return FileUtil::isFile($file);
    }

    /**
     * Check whether a file is readable.
     *
     * @param  string $file
     * @return bool
     */
    public static function isReadable(string $file): bool
    {
        return FileUtil::isFile($file) && is_readable($file);
    }

    /**
     * Check whether a file is writable.
     *
     * @param  string $file
     * @return bool
     */
    public static function isWritable(string $file): bool
    {
        return FileUtil::isFile($file) && is_writable($file);
    }

    /**
     * Read entire contents from a file/stream.
     *
     * @param  string|resource $file
     * @return string
     * @throws froq\file\FileError
     * @since  4.0
     */
    public static function read($file): string
    {
        $ret = match ($type = get_type($file)) {
            'string' => file_get_contents($file),
            'resource (stream)' => stream_get_contents($file, -1, 0),
            default => throw new FileError('Invalid file type %s, valids are: string|resource', $type)
        };

        if ($ret === false) {
            throw new FileError('Cannot read file [error: %s, file: %s]', ['@error', $file]);
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
     * @throws froq\file\FileError
     * @since  4.0
     */
    public static function write($file, string $contents, int $flags = 0): bool
    {
        $ret = match ($type = get_type($file)) {
            'string' => file_set_contents($file, $contents, $flags),
            'resource (stream)' => stream_set_contents($file, $contents),
            default => throw new FileError('Invalid file type %s, valids are: string|resource', $type)
        };

        if ($ret === false) {
            throw new FileError('Cannot write file [error: %s, file: %s]', ['@error', $file]);
        }

        return true;
    }

    /**
     * Set/get file mode.
     *
     * @param  string   $file
     * @param  int|null $mode
     * @return string
     * @throws froq\file\FileError
     * @since  4.0
     */
    public static function mode(string $file, int $mode = null): string
    {
        if ($mode !== null) {
            if ($mode > -1) { // Set mode.
                $ret = chmod($file, $mode);
                if ($ret === false) {
                    throw new FileError("Cannot set file mode [error: %s, file: %s]", ['@error', $file]);
                }
                $ret = $mode;
            } else { // Get mode.
                $ret = fileperms($file);
                if ($ret === false) {
                    throw new FileError("Cannot get file stat for '%s'", $file);
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
            throw new FileError("Cannot get file stat for '%s'", $file);
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
}
