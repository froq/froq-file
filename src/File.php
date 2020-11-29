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
 * @since   3.0, 4.0 Made static, added getType(),getExtension(),read(),write(),mode(), moved all
 *          other stuff into AbstractUploader.
 * @static
 */
final class File
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
     * Is exists.
     * @param  string $file
     * @return bool
     */
    public static function isExists(string $file): bool
    {
        return FileUtil::isFile($file);
    }

    /**
     * Is readable.
     * @param  string $file
     * @return bool
     */
    public static function isReadable(string $file): bool
    {
        return FileUtil::isFile($file) && is_readable($file);
    }

    /**
     * Is writable.
     * @param  string $file
     * @return bool
     */
    public static function isWritable(string $file): bool
    {
        return FileUtil::isFile($file) && is_writable($file);
    }

    /**
     * Read.
     * @param  string $file
     * @return string
     * @throws froq\file\FileError
     * @since  4.0
     */
    public static function read(string $file): string
    {
        $ret =@ file_get_contents($file);
        if ($ret !== false) {
            return $ret;
        }

        throw new FileError('Cannot read file [error: %s, file: %s]', ['@error', $file]);
    }

    /**
     * Write.
     * @param  string $file
     * @param  string $contents
     * @param  int    $flags
     * @return bool
     * @throws froq\file\FileError
     * @since  4.0
     */
    public static function write(string $file, string $contents, int $flags = 0): bool
    {
        $ret =@ file_put_contents($file, $contents, $flags);
        if ($ret !== false) {
            return true;
        }

        throw new FileError('Cannot write file [error: %s, file: %s]', ['@error', $file]);
    }

    /**
     * Mode.
     * @param  string   $file
     * @param  int|null $mode
     * @return string
     * @throws froq\file\FileError
     * @since  4.0
     */
    public static function mode(string $file, int $mode = null): string
    {
        if ($mode !== null) {
            // Get mode.
            if ($mode === -1) {
                $ret =@ fileperms($file);
                if ($ret === false) {
                    throw new FileError('Cannot get file stat for "%s"', [$file]);
                }
            }
            // Set mode.
            else {
                $ret =@ chmod($file, $mode);
                if ($ret === false) {
                    throw new FileError('Cannot set file mode [error: %s, file: %s]', ['@error', $file]);
                }
                $ret = $mode;
            }

            // Compare.
            // $mode = file_mode($file, -1)
            // $mode === '644' or octdec($mode) === 0644
            return $ret ?  decoct($ret & 0777) : null;
        }

        // Get full permissions.
        $perms =@ fileperms($file);
        if ($perms === false) {
            throw new FileError('Cannot get file stat for "%s"', [$file]);
        }

        // Source http://php.net/fileperms.
        switch ($perms & 0xf000) {
            case 0xc000: $ret = 's'; break; // Socket.
            case 0xa000: $ret = 'l'; break; // Symbolic link.
            case 0x8000: $ret = 'r'; break; // Regular.
            case 0x6000: $ret = 'b'; break; // Block special.
            case 0x4000: $ret = 'd'; break; // Directory.
            case 0x2000: $ret = 'c'; break; // Character special.
            case 0x1000: $ret = 'p'; break; // FIFO pipe.
                default: $ret = 'u';        // Unknown.
        }

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
