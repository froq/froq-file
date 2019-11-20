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

use froq\file\{MimeException, MimeTypes, Util as FileUtil};
use Error;

/**
 * Mime.
 * @package froq\file
 * @object  froq\file\Mime
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   1.0
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
                throw new MimeException($error->getMessage(), $error->getCode());
            }
        }

        $type = null;

        try {
            // This function could be not exists.
            $type =@ mime_content_type($file);
            if ($type === false) {
                throw new MimeException(error());
            }
        } catch (Error $e) {
            try {
                // This function could be not exists.
                $exec = exec('file -i '. escapeshellarg($file));
                if (preg_match('~: ([^/ ]+/[^; ]+)~', $exec, $match)) {
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
        if (ctype_print($file)) { // safe
            return pathinfo($file, PATHINFO_EXTENSION);
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
