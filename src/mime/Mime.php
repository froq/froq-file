<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file\mime;

use froq\file\mime\{MimeException, MimeTypes};
use froq\file\Util as FileUtil;
use Error;

/**
 * Mime.
 *
 * @package froq\file\mime
 * @object  froq\file\mime\Mime
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   1.0, 4.0 Moved to mime directory.
 * @static
 */
final class Mime
{
    /**
     * Get type.
     * @param  string $file
     * @param  bool   $errorCheck
     * @return ?string
     * @throws froq\file\MimeException
     */
    public static function getType(string $file, bool $errorCheck = true): ?string
    {
        if ($errorCheck) {
            FileUtil::errorCheck($file, $error);
            if ($error != null) {
                throw new MimeException($error->getMessage(), null, $error->getCode());
            }
        }

        $type = null;

        try {
            // This function may be not available.
            $type = mime_content_type($file);
            if ($type === false) {
                throw new MimeException('@error');
            }
        } catch (Error $e) {
            try {
                // This function may be not available.
                $exec = exec('file -i '. escapeshellarg($file));
                if (preg_match('~: *([^/ ]+/[^; ]+)~', $exec, $match)) {
                    $type = $match[1];
                    if ($type == 'inode/directory') {
                        $type = 'directory';
                    }
                }
            } catch (Error $e) {}
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
     * Get extension.
     * @param  string $file
     * @return ?string
     * @since  3.0
     */
    public static function getExtension(string $file): ?string
    {
        if (ctype_print($file)) { // Safe.
            return pathinfo($file, PATHINFO_EXTENSION) ?: null;
        }
        return null;
    }

    /**
     * Get type by extension.
     * @param  string $extension
     * @return ?string
     */
    public static function getTypeByExtension(string $extension): ?string
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
     * Get extension by type.
     * @param  string $type
     * @param  int    $i
     * @return ?string
     */
    public static function getExtensionByType(string $type, int $i = 0): ?string
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
