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

use froq\common\interfaces\Stringable;
use froq\file\{AbstractFileObject, FileException, Util as FileUtil};

/**
 * Image Object.
 * @package froq\file
 * @object  froq\file\ImageObject
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
final class ImageObject extends AbstractFileObject implements Stringable
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

    public function resize(int $width, int $height): self
    {
        $tmp = null;
        if (is_string($this->resourceFile)) {
            $file = $this->resourceFile;
        } elseif (is_resource($this->resourceFile)) {
            $file = stream_get_meta_data($this->resourceFile)['uri'];
        } else {
            $tmp  = (new FileObject())->setContents($this->getContents());
            $file = $tmp->getPath();
        }

        $up = (
            new ImageUploader(
                ['type' => $this->mimeType, 'file' => $file, 'directory' => '/tmp'],
                ['allowedTypes' => '*', 'allowedExtensions' => '*', 'clear' => false, 'clearSource' => false,
                 'jpegQuality' => $this->options['jpegQuality'], 'webpQuality' => $this->options['webpQuality']]
            )
        )->resize($width, $height);

        $tmp && $tmp->free();

        $this->resource = $up->getDestinationImage();

        return $this;
    }

    public function crop(int $width, int $height = null): self {}

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
                    'extension'  => image_type_to_extension($info[2]),
                    'exif'       => null,
                ];

                // For only JPEG (and also PNG? https://stackoverflow.com/q/9542359/362780).
                if ($info['type'] == 2 && function_exists('exif_read_data')) {
                    $fp = fopen('php://temp/maxmemory:'. $info['size'], 'w+b');
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

    public function setContents(string $contents): self
    {
        $this->resourceCheck();

        imagedestroy($this->resource);
        $this->resource = imagecreatefromstring($contents);

        return $this;
    }

    public function getContents(): ?string
    {
        if ($this->resourceFile) {
            return file_get_contents($this->resourceFile);
        }

        $this->resourceCheck();

        if (empty($this->mimeType)) {
            throw new FileException('No MIME type given yet, try after calling setMimeType()');
        }

        ob_start();

        switch ($this->mimeType) {
            case self::MIME_TYPE_JPEG:
                imagejpeg($this->resource, null, $this->options['jpegQuality']);
                break;
            case self::MIME_TYPE_PNG:
                imagepng($this->resource, null, $this->options['pngZipLevel'], $this->options['pngFilters']);
                break;
            case self::MIME_TYPE_GIF:
                imagegif($this->resource);
                break;
            case self::MIME_TYPE_WEBP:
                imagewebp($this->resource, null, $this->options['webpQuality']);
                break;
        }

        return ob_get_length() !== false ? ob_get_clean() : null;
    }

    public function isJpeg(): bool
    {
        return ($this->mimeType == self::MIME_TYPE_JPEG);
    }
    public function isPng(): bool
    {
        return ($this->mimeType == self::MIME_TYPE_PNG);
    }
    public function isGif(): bool
    {
        return ($this->mimeType == self::MIME_TYPE_GIF);
    }
    public function isWebp(): bool
    {
        return ($this->mimeType == self::MIME_TYPE_WEBP);
    }

    /**
     * To base 64.
     * @return string
     */
    public function toBase64(): string
    {
        return base64_encode($this->toString());
    }

    /**
     * To base 64 url.
     * @return string
     */
    public function toBase64Url(): string
    {
        $base64 = base64_encode($this->toString());

        return 'data:'. $this->mimeType .';base64,'. $base64;
    }

    /**
     * @inheritDoc froq\common\interfaces\Stringable
     */
    public function toString(): string
    {
        return $this->getContents();
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

        $image = new ImageObject($resource, $mimeType, $options);
        $image->resourceFile = $file; // Stored for speed up resize(), crop(), getContents().

        return $image;
    }

    // @implement
    public static function fromString(string $string, string $mimeType = null, array $options = null): ImageObject
    {
        $mimeType =@ $mimeType ?? getimagesizefromstring($string)['mime'];
        $resource =@ imagecreatefromstring($string);
        if (!$resource) {
            throw new FileException('Cannot create resource [error: %s]', ['@error']);
        }

        $image = new ImageObject($resource, $mimeType, $options);
        $image->resourceFile = tmpfile(); // Stored for speed up resize(), crop(), getContents().
        fwrite($image->resourceFile, $string);

        return $image;
    }
}
