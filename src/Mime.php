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

namespace Froq\File;

/**
 * @package    Froq
 * @subpackage Froq\File
 * @object     Froq\File\Mime
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final /* static */ class Mime
{
    /**
     * Get type.
     * @param  string $file
     * @return string
     * @throws Froq\File\MimeException
     */
    public static function getType(string $file): string
    {
        if (!extension_loaded('fileinfo')) {
            throw new MimeException('fileinfo module not found');
        }

        @ $return = mime_content_type($file);
        if ($return === false) {
            throw new MimeException(error_get_last()['message'] ?? 'Unknown');
        }

        return $return;
    }

    /**
     * Get extension.
     * @param  string $fileName
     * @return ?string
     */
    public static function getExtension(string $fileName): ?string
    {
        return strchr($fileName, '.') ? pathinfo($fileName, PATHINFO_EXTENSION) : null;
    }

    /**
     * Get type by extension.
     * @param  string $fileName
     * @return ?string
     */
    public static function getTypeByExtension(string $fileName): ?string
    {
        $extension = self::getExtension($fileName);
        foreach (MimeTypes::all() as $type => $extensions) {
            if (in_array($extension, $extensions)) {
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
        $type = strtolower($type);
        $types = MimeTypes::all();

        return isset($types[$type]) ? $types[$type][$i] ?? $types[$type][0] : null;
    }
}
