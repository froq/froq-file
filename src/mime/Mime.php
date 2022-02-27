<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file\mime;

use froq\file\mime\{Mimes, MimeException};
use froq\file\{File, FileError};

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
final class Mime extends \StaticClass
{
    /**
     * Get a file type.
     *
     * @param  string $file
     * @param  bool   $errorCheck
     * @return string|null
     * @throws froq\file\MimeException
     */
    public static function getType(string $file, bool $errorCheck = true): string|null
    {
        if ($errorCheck && File::errorCheck($file, $error)) {
            if ($error->code != FileError::DIRECTORY) {
                throw new MimeException($error->message, code: $error->code, cause: $error);
            }

            // Return "directory" as type if error is directory error.
            return 'directory';
        }

        $type = null;

        try {
            // This function may be not available.
            $type = mime_content_type($file);
            if ($type === false) {
                throw new MimeException('@error');
            }
        } catch (\Error) {
            try {
                // This function may be not available.
                $exec = exec('file -i '. escapeshellarg($file));
                if (preg_match('~: *([^/ ]+/[^; ]+)~', $exec, $match)) {
                    $type = strtolower($match[1]);
                    if ($type == 'inode/directory') {
                        $type = 'directory';
                    } elseif ($type == 'inode/x-empty') {
                        $type = 'application/x-empty';
                    }
                }
            } catch (\Error) {}
        }

        // Try by extension.
        if (!$type && ($extension = self::getExtension($file))) {
            $type = self::getTypeByExtension($extension);
        }

        return $type;
    }

    /**
     * Get a file extension.
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
     * Get a file type by given extension.
     *
     * @param  string $extension
     * @return string|null
     */
    public static function getTypeByExtension(string $extension): string|null
    {
        $search = strtolower($extension);

        if (str_starts_with($search, '.')) {
            $search = ltrim($search, '.');
        }

        foreach (Mimes::all() as $type => $extensions) {
            if (equal($search, ...$extensions)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Get a file extension by given type.
     *
     * @param  string $type
     * @param  int    $index
     * @return string|null
     */
    public static function getExtensionByType(string $type, int $index = 0): string|null
    {
        $search = strtolower($type);

        foreach (Mimes::all() as $type => $extensions) {
            if (equal($search, $type)) {
                return $extensions[$index] ?? null;
            }
        }

        return null;
    }
}
