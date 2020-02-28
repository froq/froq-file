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

use froq\file\{AbstractFileObject, FileException, Util as FileUtil};

/**
 * Image Object.
 * @package froq\file
 * @object  froq\file\ImageObject
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
final class ImageObject extends AbstractFileObject
{
    public const MIME_TYPE_JPEG = 'image/jpeg',
                 MIME_TYPE_PNG  = 'image/png',
                 MIME_TYPE_GIF  = 'image/gif',
                 MIME_TYPE_WEBP = 'image/webp';
                 // Far enough for now..
                 /* MIME_TYPE_WBMP = 'image/vnd.wap.wbmp',
                 MIME_TYPE_BMP  = 'image/bmp',
                 MIME_TYPE_XBM  = 'image/xbm',
                 MIME_TYPE_XPM  = 'image/x-xpixmap'; */

    private static array $mimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp',
        /* 'image/vnd.wap.wbmp', 'image/bmp', 'image/xbm', 'image/x-xpixmap' */];

    private static array $optionsDefault = ['jpegQuality' => -1, 'webpQuality' => -1,
        'pngZipLevel' => -1, 'pngFilters' => -1];

    public function __construct($resource = null, string $mimeType = null, array $options = null)
    {
        if ($mimeType && !in_array($mimeType, self::$mimeTypes)) {
            throw new FileException('Invalid MIME type "%s" given, valids are: %s',
                [$mimeType, join(', ', self::$mimeTypes)]);
        }

        $this->setOptionsDefault($options, self::$optionsDefault);

        parent::__construct($resource, $mimeType);
    }

    public function getMimeTypes(): array
    {
        return self::$mimeTypes;
    }

    public function copy(): ImageObject
    {
        $this->resourceCheck();

        return new ImageObject($this->createResourceCopy(), $this->mimeType);
    }

    public function size(): ?int
    {
        $contents = $this->getContents();
        return ($contents !== null) ? strlen($contents) : null;
    }
    public function valid(): bool
    {
        try {
            $this->resourceCheck();
            return true;
        } catch (FileException $e) {
            return false;
        }
    }

    public function resample(): self
    {
        $this->resourceCheck();

        [$oldImg, $width, $height] = [$this->resource,
            imagesx($this->resource), imagesy($this->resource)];

        $newImg = imagecreatetruecolor($width, $height);
        imagealphablending($newImg, false);
        imagesavealpha($newImg, true);
        imageantialias($newImg, true);
        imagefill($newImg, 0, 0, imagecolorallocatealpha(
            $newImg, 0, 0, 0, 127 // Apply transparency.
        ));
        imagecopyresampled($newImg, $oldImg, 0, 0, 0, 0, $width, $height, $width, $height);
        imagedestroy($oldImg); // Free old one.

        $this->resource = $newImg;

        return $this;
    }

    public function getDimensions(): array
    {
        $this->resourceCheck();

        return [imagesx($this->resource), imagesy($this->resource)];
    }

    public function getInfo(): ?array
    {
        $contents = $this->getContents();
        if ($contents !== null) {
            $info = getimagesizefromstring($contents) ?: null;
            if ($info) {
                $info += [
                    'width'      => $info[0], 'height' => $info[1],
                    'type'       => $info[2], 'size'   => strlen($contents),
                    'dimensions' => [$info[0], $info[1]],
                    'attributes' => explode(' ', $info[3]),
                    'extension'  => trim(image_type_to_extension($info[2]), '.'),
                    'exif'       => null,
                ];

                // For only JPEG (and also PNG? https://stackoverflow.com/q/9542359/362780).
                if ($info['type'] == 2 && function_exists('exif_read_data')) {
                    $fp = fopen('php://temp', 'w+b');
                    fwrite($fp, $contents);
                    $info['exif'] = exif_read_data($fp);
                    fclose($fp);
                    if ($info['exif']) {
                        // I don't understand why all keys aren't uniform.
                        $info['exif'] = array_map(
                            fn($v) => is_array($v) ? array_change_key_case($v) : $v,
                            array_change_key_case($info['exif'])
                        );
                    }
                }
            }
            return $info;
        }
        return null;
    }

    public function getContents(): ?string
    {
        $this->resourceCheck();

        $mimeType = $this->getMimeType();
        if ($mimeType == null) {
            throw new FileException('No MIME type given yet');
        }

        ob_start();

        $copy = $this->createResourceCopy();
        switch ($mimeType) {
            case self::MIME_TYPE_JPEG:
                imagejpeg($copy, null, $this->option('jpegQuality'));
                break;
            case self::MIME_TYPE_PNG:
                imagepng($copy, null, $this->option('pngZipLevel'), $this->option('pngFilters'));
                break;
            case self::MIME_TYPE_GIF:
                imagegif($copy);
                break;
            case self::MIME_TYPE_WEBP:
                imagewebp($copy, null, $this->option('webpQuality'));
                break;
        }
        $this->removeResourceCopy($copy);

        return ob_get_length() !== false ? ob_get_clean() : null;
    }

    // @implement
    public static function fromFile(string $file, string $mimeType = null, array $options = null): ImageObject
    {
        FileUtil::errorCheck($file, $error);
        if ($error != null) {
            throw new FileException($error->getMessage(), null, $error->getCode());
        }

        $mimeType =@ $mimeType ?? mime_content_type($file);
        $resource =@ imagecreatefromstring(file_get_contents($file));
        if (!$resource) {
            throw new FileException('Cannot create resource [error: %s]', ['@error']);
        }
        return new ImageObject($resource, $mimeType, $options);
    }

    // @implement
    public static function fromString(string $string, string $mimeType = null, array $options = null): ImageObject
    {
        $mimeType =@ $mimeType ?? getimagesizefromstring($string)['mime'];
        $resource =@ imagecreatefromstring($string);
        if (!$resource) {
            throw new FileException('Cannot create resource [error: %s]', ['@error']);
        }
        return new ImageObject($resource, $mimeType, $options);
    }

    // @implement
    public function free(): void
    {
        if (is_resource($this->resource)) {
            $this->freed = imagedestroy($this->resource);
            $this->resource = null;
        }
    }
}
