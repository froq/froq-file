<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file;

use froq\file\{FileException, FileObject, ImageObject};
use froq\common\traits\{OptionTrait, ApplyTrait};

/**
 * Abstract File Object.
 *
 * @package froq\file
 * @object  froq\file\AbstractFileObject
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
abstract class AbstractFileObject
{
    /**
     * Option & Apply traits.
     * @see froq\common\traits\OptionTrait
     * @see froq\common\traits\ApplyTrait
     */
    use OptionTrait, ApplyTrait;

    /**
     * Resource.
     * @var ?resource
     */
    protected $resource;

    /**
     * Resource file.
     * @var ?resource|?string
     */
    protected $resourceFile;

    /**
     * Mime type.
     * @var ?string
     */
    protected ?string $mimeType;

    /**
     * Freed.
     * @var ?bool
     */
    protected ?bool $freed = null;

    /**
     * Constructor.
     * @param  resource|null $resource
     * @param  string|null   $mimeType
     * @throws froq\file\FileException
     */
    public function __construct($resource = null, string $mimeType = null)
    {
        $resource = $resource ?? tmpfile();

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

    /**
     * Get resource.
     * @return ?resource
     */
    public final function getResource()
    {
        return $this->resource;
    }

    /**
     * Get resource file.
     * @return ?resource
     */
    public final function getResourceFile()
    {
        return $this->resourceFile;
    }

    /**
     * Get resource type.
     * @return ?string
     */
    public final function getResourceType(): ?string
    {
        return is_resource($this->resource) ? get_resource_type($this->resource) : null;
    }

    /**
     * Create resource copy.
     * @return resource|null
     */
    public final function &createResourceCopy()
    {
        if ($this->resource && is_resource($this->resource)) {
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
                        $copy, 255, 255, 255, 127 // Apply transparency.
                    ));
                }

                imagecopyresampled($copy, $this->resource, 0, 0, 0, 0, $width, $height, $width, $height);
            }

            return $copy;
        }

        return null;
    }

    /**
     * Remove resource copy.
     * @param  resource &$copy
     * @return ?bool
     */
    public final function removeResourceCopy(&$copy): ?bool
    {
        if ($copy && is_resource($copy)) {
            if ($this instanceof FileObject) {
                return fclose($copy);
            } elseif ($this instanceof ImageObject) {
                return imagedestroy($copy);
            }
        }

        return null;
    }

    /**
     * Set mime type.
     * @param  string $mimeType
     * @return self
     */
    public final function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * Get mime type..
     * @return ?string
     */
    public final function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    /**
     * Free.
     * @return void
     */
    public final function free(): void
    {
        if ($this->resource && is_resource($this->resource)) {
            if ($this instanceof FileObject) {
                $this->freed = fclose($this->resource);
            } elseif ($this instanceof ImageObject) {
                $this->freed = imagedestroy($this->resource);
            }
        }

        if ($this->resourceFile && is_resource($this->resourceFile)) {
            fclose($this->resourceFile);
        }

        $this->resource = null;
        $this->resourceFile = null;
    }

    /**
     * Is freed.
     * @return bool
     */
    public final function isFreed(): bool
    {
        return ($this->freed === true);
    }

    /**
     * From resource.
     * @param  resource    $resource
     * @param  string|null $mimeType
     * @param  array|null  $options
     * @return self (static)
     * @throws froq\file\FileException
     */
    public static final function fromResource($resource, string $mimeType = null, array $options = null): self
    {
        if (is_null($resource)) {
            throw new FileException('Null resource given');
        }

        return new static($resource, $mimeType, $options);
    }

    /**
     * From temporary resource.
     * @param  resource    $resource
     * @param  string|null $mimeType
     * @param  array|null  $options
     * @return self (static)
     */
    public static final function fromTemporaryResource(string $mimeType = null, array $options = null): self
    {
        return new static(self::createTemporaryResource($options), $mimeType, $options);
    }

    /**
     * From temporary file resource.
     * @param  resource    $resource
     * @param  string|null $mimeType
     * @param  array|null  $options
     * @return self (static)
     */
    public static final function fromTemporaryFileResource(string $mimeType = null, array $options = null): self
    {
        return new static(tmpfile(), $mimeType, $options);
    }

    /**
     * Create temporary resource.
     * @param  array|null $options
     * @return resource
     * @throws froq\file\FileException
     */
    public static final function createTemporaryResource(array $options = null)
    {
        $mode = $options['mode'] ?? 'w+b';
        $maxmem = $options['maxmem'] ?? null;

        $resource =@ !$maxmem ? fopen('php://temp', $mode)
                              : fopen('php://temp/maxmemory:'. $maxmem, $mode);

        if (!$resource) {
            throw new FileException('Cannot create temporary resource [error: %s]', ['@error']);
        }

        return $resource;
    }

    /**
     * Resource check.
     * @return void
     * @throws froq\file\FileException
     */
    protected final function resourceCheck(): void
    {
        if ($this->freed) {
            throw new FileException('No resource to process with, it is freed');
        }
        if (!$this->resource || !is_resource($this->resource)) {
            throw new FileException('No resource to process with, it is not valid');
        }
    }

    /**
     * From file.
     * @param  string      $file
     * @param  string|null $mimeType
     * @param  array|null  $options
     * @return self (static)
     * @throws froq\file\FileException
     */
    abstract public static function fromFile(string $file, string $mimeType = null, array $options = null): self;

    /**
     * From string.
     * @param  string      $string
     * @param  string|null $mimeType
     * @param  array|null  $options
     * @return self (static)
     * @throws froq\file\FileException
     */
    abstract public static function fromString(string $string, string $mimeType = null, array $options = null): self;
}
