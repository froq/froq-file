<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\mime;

/**
 * A static class, for getting file MIME types and extensions.
 *
 * @package froq\file\mime
 * @class   froq\file\mime\Mime
 * @author  Kerem Güneş
 * @since   1.0, 4.0
 * @static
 */
class Mime
{
    /**
     * Get type.
     *
     * @param  string $file
     * @return string|null
     * @throws froq\file\MimeException
     */
    public static function getType(string $file): string|null
    {
        if (is_dir($file)) {
            return 'directory';
        }

        $type = null;

        try {
            // This function may not be available.
            $type = @mime_content_type($file);
            if ($type === false) {
                $type = null;
            }
        } catch (\Throwable) {}

        // Try by extension.
        if ($type === null && ($extension = file_extension($file))) {
            $type = self::getTypeByExtension($extension);
        }

        return $type;
    }

    /**
     * Get type by given extension.
     *
     * @param  string $extension
     * @return string|null
     */
    public static function getTypeByExtension(string $extension): string|null
    {
        $search = strtolower(ltrim($extension, '.'));

        foreach (Mimes::all() as $type => $extensions) {
            if (equals($search, ...$extensions)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Get file extension by given type.
     *
     * @param  string $type
     * @param  int    $index
     * @return string|null
     */
    public static function getExtensionByType(string $type, int $index = 0): string|null
    {
        $search = strtolower($type);

        foreach (Mimes::all() as $type => $extensions) {
            if (equals($search, $type)) {
                return @$extensions[$index];
            }
        }

        return null;
    }
}
