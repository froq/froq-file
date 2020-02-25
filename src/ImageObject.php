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
use Error;

/**
 * Image Object.
 * @package froq\file
 * @object  froq\file\ImageObject
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
final class ImageObject extends AbstractFileObject
{
    public const DEFAULT_QUALITY = -1;

    protected int $quality;

    public function __construct($resource = null, string $mimeType = null, int $quality = null)
    {
        $this->quality = $quality ?: self::DEFAULT_QUALITY;

        parent::__construct($resource, $mimeType);
    }

    public function copy(): ImageObject
    {
        $this->resourceCheck();

        return new ImageObject($this->getResourceCopy(), $this->getMimeType());
    }

    public function size(): ?int
    {
        // imagescale()
        // https://stackoverflow.com/a/24669362/362780
        ob_start();              // start the buffer
        imagejpeg($img);         // output image to buffer
        $size = ob_get_length(); // get size of buffer (in bytes)
        ob_end_clean();          // trash the buffer
    }

    public function getContents(int $quality = null)
    {
        $mimeType = $this->getMimeType();
        if ($mimeType == null) {
            throw new FileException('No MIME type given yet');
        }

        $quality = $quality ?? $this->quality;

        ob_start();
        switch ($mimeType) {
            case 'image/jpeg':
                $copy = $this->getResourceCopy();
                imagejpeg($copy, null, $quality) && imagedestroy($copy);
                break;
            case 'image/png':
                $copy = $this->getResourceCopy();
                // $copy = $this->getResource();

                // $color = imagecolorallocatealpha($copy, 0, 0, 0, 127); //fill transparent back
                // imagefill($copy, 0, 0, $color);
                // imagesavealpha($copy, true);

                // imagealphablending($copy, false);
                // imagesavealpha($copy, true);
                // imageantialias($copy, true);

                // $transparent = imagecolorallocatealpha($copy, 255, 255, 255, 127);
                // $transparent = imagecolorallocatealpha($copy, 0, 0, 0, 127);
                // $transparent = imagecolorallocate($copy, 0, 0, 0);
                // imagefill($copy, 0, 0, $transparent);
                // imagecolortransparent($copy, $transparent);

                // $black = imagecolorallocate($copy, 0, 0, 0);
                // imagecolortransparent($copy, $black); // Make the background transparent

                imagepng($copy) && imagedestroy($copy);
                // imagecolordeallocate($copy, $transparent);
                break;
            case 'image/gif':
                $copy = $this->getResourceCopy();
                imagegif($copy) && imagedestroy($copy);
                break;
            case 'image/webp':
                $copy = $this->getResourceCopy();
                imagewebp($copy, null, $quality) && imagedestroy($copy);
                break;
            default:
                throw new FileException('No MIME type supported such "%s"', $mimeType);
        }
        $ret = ob_get_clean();

        return $ret;
    }

    // webp
    // public function getCompressedContents()
    // {}

    // @implement
    public static function fromFile(string $file, int $quality = null): ImageObject
    {
        FileUtil::errorCheck($file, $error);
        if ($error != null) {
            throw new FileException($error->getMessage(), null, $error->getCode());
        }

        $resource =@ imagecreatefromstring(file_get_contents($file));
        if (!$resource) {
            throw new FileException('Cannot create resource [error: %s]', ['@error']);
        }
        return new ImageObject($resource, mime_content_type($file), $quality);
    }

    // @implement
    public static function fromString(string $string, int $quality = null): ImageObject
    {
        $resource =@ imagecreatefromstring($string);
        if (!$resource) {
            throw new FileException('Cannot create resource [error: %s]', ['@error']);
        }
        return new ImageObject($resource, getimagesizefromstring($string)['mime'], $quality);
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
