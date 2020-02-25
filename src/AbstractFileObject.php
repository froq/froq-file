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

// use froq\file\FileException;
use froq\file\{FileException, Util as FileUtil};

/**
 * Abstract File Object.
 * @package froq\file
 * @object  froq\file\AbstractFileObject
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
abstract class AbstractFileObject
{
    /**
     * Resource.
     * @var resource
     */
    protected $resource;

    protected ?string $mimeType;

    protected ?bool $freed = null;

    public function __construct($resource = null, string $mimeType = null)
    {
        $resource = $resource ?? self::createTemporaryResource();

        if (!is_resource($resource)) {
            throw new FileException('Resource must be a "gd" or "stream" resource, "%s" given',
                [gettype($resource)]);
        }

        $resourceType = get_resource_type($resource);
        if ($this instanceof FileObject && $resourceType != 'stream') {
            throw new FileException('Resource type must be "stream", "%s" given',
                [$resourceType]);
        } elseif ($this instanceof ImageObject && $resourceType != 'gd') {
            throw new FileException('Resource type must be "gd", "%s" given',
                [$resourceType]);
        }

        $this->resource = $resource;
        $this->mimeType = $mimeType;
    }

    public function __destruct()
    {
        $this->free();
    }

    public final function getResource()
    {
        return $this->resource;
    }
    public final function getResourceType(): ?string
    {
        return is_resource($this->resource) ? get_resource_type($this->resource) : null;
    }
    public final function getResourceCopy()
    {
        if (is_resource($this->resource)) {
            if ($this instanceof FileObject) {
                $pos = ftell($this->resource);
                rewind($this->resource);

                $copy = self::createTemporaryResource();
                stream_copy_to_stream($this->resource, $copy, -1, 0);
                rewind($copy);

                fseek($this->resource, $pos);
            } elseif ($this instanceof ImageObject) {
                [$width, $height] = [imagesx($this->resource), imagesy($this->resource)];
                $copy = imagecreatetruecolor($width, $height);
                // imagecopy($copy, $this->resource, 0, 0, 0, 0, $width, $height);
                // imagecopymerge($copy, $this->resource, 0, 0, 0, 0, $width, $height, 100);
                // imagecopyresampled($copy, $this->resource, 0, 0, 0, 0, $width, $height, $width, $height);

                // $transparent = imagecolorallocatealpha($copy, 0, 0, 0, 127);
                // imagefill($copy, 0, 0, $transparent);
                // imagesavealpha($copy, true);
                // imagealphablending($copy, false);
                // imageantialias($copy, true);

                // imagecolortransparent($copy, imagecolorallocatealpha($copy, 0, 0, 0, 127));
                // imagealphablending($copy, false);
                // imagesavealpha($copy, true);

                // imagecolortransparent($copy, imagecolorallocate($copy, 0, 0, 0));
                // imagealphablending($copy, false);
                // imagesavealpha($copy, true);
                // imagecopyresampled($copy, $this->resource, 0, 0, 0, 0, $width, $height, $width, $height);

                // imagealphablending($copy, false);
                // imagesavealpha($copy, true);
                // $color = imagecolortransparent($copy, imagecolorallocatealpha($copy, 0, 0, 0, 127));
                // imagefill($copy, 0, 0, $color);
                // imagecopyresampled($copy, $this->resource, 0, 0, 0, 0, $width, $height, $width, $height);

                // imagecopy($copy, $this->resource, 0, 0, 0, 0, $width, $height);
                // imagecopymerge($copy, $this->resource, 0, 0, 0, 0, $width, $height, 100);
                // imagecopyresampled($copy, $this->resource, 0, 0, 0, 0, $width, $height, $width, $height);
                // $this->imagecopyresampledSMOOTH($copy, $this->resource, 0, 0, 0, 0, $width, $height, $width, $height);

                $this->setTransparency($copy, $this->resource);
                // $this->imagetograyscale($copy);
                imagecopyresampled($copy, $this->resource, 0, 0, 0, 0, $width, $height, $width, $height);
                // imagefilter($copy, IMG_FILTER_EMBOSS);
                // imagesavealpha($copy, true);
                // $c = imagecolorat($copy, 0, 0);
                // $cc = imagecolorsforindex($copy, $c);
                // imagecolorset($copy, $c, $cc['red'], $cc['green'], $cc['blue']);
                // for ($x = 0; $x < $width; $x++) {
                //     for ($y = 0; $y < $height; $y++) {
                //         $c = imagecolorat($copy, $x, $y);
                //         $cc = imagecolorsforindex($copy, $c);
                //         imagecolorset($copy, $c, $cc['red'], $cc['green'], $cc['blue']);
                //     }
                // }
            }
            return $copy;
        }
        return null;
    }

// http://php.net/imagecolorset#70156
static function imagetograyscale($im)
{
    if (imageistruecolor($im)) {
        imagetruecolortopalette($im, false, 256);
    }

    for ($c = 0; $c < imagecolorstotal($im); $c++) {
        $col = imagecolorsforindex($im, $c);
        $gray = (int) round(0.299 * $col['red'] + 0.587 * $col['green'] + 0.114 * $col['blue']);
        imagecolorset($im, $c, $gray, $gray, $gray);
    }
}
// http://php.net/imagecolortransparent#89927
static function setTransparency($new_img, $src_img)
{
    $transparencyIndex = imagecolortransparent($src_img);
    $transparencyColor = array('red' => 255, 'green' => 255, 'blue' => 255);

    if ($transparencyIndex >= 0) {
        $transparencyColor    = imagecolorsforindex($src_img, $transparencyIndex);
    }

    $transparencyIndex    = imagecolorallocate($new_img, $transparencyColor['red'], $transparencyColor['green'], $transparencyColor['blue']);
    imagefill($new_img, 0, 0, $transparencyIndex);
    imagecolortransparent($new_img, $transparencyIndex);

    imageantialias($new_img, true);
    if (imageistruecolor($src_img)) {
        imagetruecolortopalette($new_img, false, 256);
    }
}

// http://php.net/imagecopyresampled#81898
function imagecopyresampledSMOOTH(&$dst_img, $src_img, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h, $mult=1.25){
    // don't use a $mult that's too close to an int or this function won't make much of a difference

    $tgt_w = round($src_w * $mult);
    $tgt_h = round($src_h * $mult);

    // using $mult <= 1 will make the current step w/h smaller (or the same), don't allow this, always resize by at least 1 pixel larger
    if($tgt_w <= $src_w){ $tgt_w += 1; }
    if($tgt_h <= $src_h){ $tgt_h += 1; }

    // if the current step w/h is larger than the final height, adjust it back to the final size
    // this check also makes it so that if we are doing a resize to smaller image, it happens in one step (since that's already smooth)
    if($tgt_w > $dst_w){ $tgt_w = $dst_w; }
    if($tgt_h > $dst_h){ $tgt_h = $dst_h; }

    $tmpImg = imagecreatetruecolor($tgt_w, $tgt_h);
    $this->setTransparency($tmpImg, $this->resource);
    imagecopyresampled($tmpImg, $src_img, 0, 0, $src_x, $src_y, $tgt_w, $tgt_h, $src_w, $src_h);
    $this->setTransparency($dst_img, $this->resource);
    // imagecopy($dst_img, $tmpImg, $dst_x, $dst_y, 0, 0, $tgt_w, $tgt_h);
    imagecopyresampled($dst_img, $tmpImg, $dst_x, $dst_y, 0, 0, $tgt_w, $tgt_h, $src_w, $src_h);
    imagedestroy($tmpImg);

    // as long as the final w/h has not been reached, reep on resizing
    if($tgt_w < $dst_w OR $tgt_h < $dst_h){
        $this->imagecopyresampledSMOOTH($dst_img, $dst_img, $dst_x, $dst_y, $dst_x, $dst_y, $dst_w, $dst_h, $tgt_w, $tgt_h, $mult);
    }
}

    // http://php.net/imagecolortransparent#89927
    static function copyTransparency($sourceImage, &$destinationImage)
    {
        static $transparencyColorDefault = ['red' => 255, 'green' => 255, 'blue' => 255];

        $transparencyIndex = imagecolortransparent($sourceImage);
        $transparencyColor = $transparencyColorDefault;
        if ($transparencyIndex >= 0) {
            $transparencyColor = imagecolorsforindex($sourceImage, $transparencyIndex);
        }

        $transparencyIndex = imagecolorallocate($destinationImage,
            $transparencyColor['red'], $transparencyColor['green'], $transparencyColor['blue']
        );
        imagefill($destinationImage, 0, 0, $transparencyIndex);
        imagecolortransparent($destinationImage, $transparencyIndex);
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }
    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public final function isFreed(): bool
    {
        return ($this->freed === true);
    }

    public static final function createMemoryResource(string $mode = null)
    {
        $resource =@ fopen('php://memory', $mode ?? 'w+b');
        if (!$resource) {
            throw new FileException('Cannot create memory resource [error: %s]', ['@error']);
        }
        return $resource;
    }

    public static final function createTemporaryResource(string $mode = null, int $maxmem = null)
    {
        $resource =@ ($maxmem === null) ? fopen('php://temp', $mode ?? 'w+b')
                                        : fopen('php://temp/maxmemory:'. $maxmem, $mode ?? 'w+b');
        if (!$resource) {
            throw new FileException('Cannot create temporary resource [error: %s]', ['@error']);
        }
        return $resource;
    }

    public static final function fromResource($resource, string $mimeType = null): self
    {
        if (is_null($resource)) {
            throw new FileException('Null resource given');
        }
        return new static($resource, $mimeType);
    }
    public static final function fromMemoryResource(string $mode = null): self
    {
        return new static(self::createMemoryResource($mode));
    }
    public static final function fromTemporaryResource(string $mode = null, int $maxmem = null): self
    {
        return new static(self::createTemporaryResource($mode, $maxmem));
    }

    protected final function resourceCheck(): void
    {
        if ($this->freed) {
            throw new FileException('No resource to process with, it is freed');
        }
        if (!$this->resource || !is_resource($this->resource)) {
            throw new FileException('No resource to process with, it is not valid');
        }
    }

    abstract public static function fromFile(string $file): self;
    abstract public static function fromString(string $string): self;

    abstract public function free(): void;
}
