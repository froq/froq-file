<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file\mime;

use froq\file\mime\{MimeException, MimeTypes};
use froq\file\File;
use Error;

/**
 * Mime.
 *
 * Represents a static class entity which is able to get files' mime types and extensions.
 *
 * @package froq\file\mime
 * @object  froq\file\mime\Mime
 * @author  Kerem Güneş
 * @since   1.0, 4.0 Moved to mime directory.
 * @static
 */
final class Mime
{
    /**
     * Get file type.
     *
     * @param  string $file
     * @param  bool   $errorCheck
     * @return string|null
     * @throws froq\file\MimeException
     */
    public static function getType(string $file, bool $errorCheck = true): string|null
    {
        if ($errorCheck && File::errorCheck($file, $error)) {
            throw new MimeException($error->getMessage(), null, $error->getCode());
        }

        $type = null;

        try {
            // This function may be not available.
            $type = mime_content_type($file);
            if ($type === false) {
                throw new MimeException('@error');
            }
        } catch (Error) {
            try {
                // This function may be not available.
                $exec = exec('file -i '. escapeshellarg($file));
                if (preg_match('~: *([^/ ]+/[^; ]+)~', $exec, $match)) {
                    $type = $match[1];
                    if ($type == 'inode/directory') {
                        $type = 'directory';
                    }
                }
            } catch (Error) {}
        }

        // Try by extension.
        if ($type == null) {
            $extension = self::getExtension($file);
            if ($extension != null) {
                $type = self::getTypeByExtension($extension);
            }
        }

        return $type;
    }

    /**
     * Get file extension.
     *
     * @param  string $file
     * @return string|null
     * @since  3.0
     */
    public static function getExtension(string $file): string|null
    {
        return file_extension($file, false);
    }

    /**
     * Get a file type by extension.
     *
     * @param  string $extension
     * @return string|null
     */
    public static function getTypeByExtension(string $extension): string|null
    {
        $search = strtolower($extension);

        foreach (MimeTypes::all() as $type => $extensions) {
            if (in_array($search, $extensions, true)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Get a file extension by type.
     *
     * @param  string $type
     * @param  int    $i
     * @return string|null
     */
    public static function getExtensionByType(string $type, int $i = 0): string|null
    {
        $search = strtolower($type);

        foreach (MimeTypes::all() as $type => $extensions) {
            if ($search === $type) {
                return $extensions[$i] ?? null;
            }
        }

        return null;
    }
}
