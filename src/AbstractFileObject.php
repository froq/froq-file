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

use froq\common\traits\OptionTrait;
use froq\file\{FileException, FileObject, ImageObject};

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
     * Option trait.
     * @see froq\common\traits\OptionTrait
     */
    use OptionTrait;

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

    public final function &createResourceCopy()
    {
        if (is_resource($this->resource)) {
            if ($this instanceof FileObject) {
                $pos = ftell($this->resource);
                rewind($this->resource);

                $copy = fopen('php://temp', 'w+b');
                stream_copy_to_stream($this->resource, $copy, -1, 0);
                rewind($copy);

                fseek($this->resource, $pos);
            } elseif ($this instanceof ImageObject) {
                $copy = imagecreatetruecolor(
                    $width  = imagesx($this->resource),
                    $height = imagesy($this->resource)
                );

                if (in_array($this->mimeType, [
                    ImageObject::MIME_TYPE_PNG,
                    ImageObject::MIME_TYPE_GIF,
                    ImageObject::MIME_TYPE_WEBP
                ])) {
                    imagealphablending($copy, false);
                    imagesavealpha($copy, true);
                    imageantialias($copy, true);
                    imagefill($copy, 0, 0, imagecolorallocatealpha(
                        $copy, 0, 0, 0, 127 // Apply transparency.
                    ));
                }

                imagecopyresampled($copy, $this->resource, 0, 0, 0, 0, $width, $height, $width, $height);
            }
            return $copy;
        }
        return null;
    }
    public final function removeResourceCopy(&$copy): ?bool
    {
        if (is_resource($copy)) {
            $ok = null;
            if ($this instanceof FileObject) {
                $ok = fclose($copy);
            } elseif ($this instanceof ImageObject) {
                $ok = imagedestroy($copy);
            }
            $copy = null;
            return $ok;
        }
        return null;
    }

    public final function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }
    public final function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public final function isFreed(): bool
    {
        return ($this->freed === true);
    }

    public static final function fromResource($resource, string $mimeType = null, array $options = null): self
    {
        if (is_null($resource)) {
            throw new FileException('Null resource given');
        }
        return new static($resource, $mimeType, $options);
    }
    public static final function fromMemoryResource(string $mode = null): self
    {
        return new static(self::createMemoryResource($mode));
    }
    public static final function fromTemporaryResource(string $mode = null, int $maxmem = null): self
    {
        return new static(self::createTemporaryResource($mode, $maxmem));
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

    protected final function resourceCheck(): void
    {
        if ($this->freed) {
            throw new FileException('No resource to process with, it is freed');
        }
        if (!$this->resource || !is_resource($this->resource)) {
            throw new FileException('No resource to process with, it is not valid');
        }
    }

    abstract public static function fromFile(string $file, string $mimeType = null, array $options = null): self;
    abstract public static function fromString(string $string, string $mimeType = null, array $options = null): self;
    abstract public function free(): void;
}
