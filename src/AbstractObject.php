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
 * Abstract Object.
 *
 * Represents an abstract object entity which aims to work with file/image resources in OOP style.
 *
 * @package froq\file
 * @object  froq\file\AbstractObject
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
abstract class AbstractObject
{
    /**
     * Option & Apply traits.
     * @see froq\common\traits\OptionTrait
     * @see froq\common\traits\ApplyTrait
     */
    use OptionTrait, ApplyTrait;

    /** @var resource|GDImage|null */
    protected $resource;

    /** @var resource|null */
    protected $resourceFile;

    /** @var ?string */
    protected ?string $resourceType;

    /** @var ?string */
    protected ?string $mime;

    /** @var ?bool */
    protected ?bool $freed = null;

    /**
     * Constructor.
     *
     * @param  resource|GDImage|null $resource
     * @param  string|null           $mime
     * @throws froq\file\FileException
     */
    public function __construct($resource = null, string $mime = null)
    {
        if ($this instanceof FileObject) {
            // Open a temporary file when none given.
            $resource ??= tmpfile();

            if (!is_type_of($resource, 'stream')) {
                throw new FileException("Resource type must be stream, '%s' given", get_type($resource));
            }

            $this->resourceType = 'file';
        } elseif ($this instanceof ImageObject) {
            // When a resource given, eg: fopen('path/to/file.jpg', 'rb').
            if (is_stream($resource)) {
                $temp = ImageObject::fromString(freadall($resource));
                [$resource, $mime] = [$temp->getResource(), $temp->getMime()];
            }

            if (!is_type_of($resource, 'GDImage')) {
                throw new FileException("Resource type must be GDImage, '%s' given", get_type($resource));
            }

            $this->resourceType = 'image';
        }

        $this->resource = $resource;
        $this->mime = $mime;
    }

    /**
     * Get resource.
     *
     * @return resource|GDImage|null
     */
    public final function getResource()
    {
        return $this->resource;
    }

    /**
     * Get resource file.
     *
     * @return resource|null
     */
    public final function getResourceFile()
    {
        return $this->resourceFile;
    }

    /**
     * Get resource type.
     *
     * @return string|null
     */
    public final function getResourceType(): string|null
    {
        return $this->resourceType;
    }

    /**
     * Create a resource copy.
     *
     * @return resource|GDImage|null
     */
    public final function &createResourceCopy()
    {
        if (empty($this->resource)) {
            return null;
        }

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

            if (in_array($this->mime, [ImageObject::MIME_PNG, ImageObject::MIME_GIF, ImageObject::MIME_WEBP])) {
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

    /**
     * Remove a resource copy.
     *
     * @param  resource|GDImage &$copy
     * @return bool|null
     */
    public final function removeResourceCopy(&$copy): bool|null
    {
        if (empty($copy)) {
            return null;
        }

        if ($this instanceof FileObject && is_type_of($copy, 'stream')) {
            return fclose($copy);
        } elseif ($this instanceof ImageObject && is_type_of($copy, 'GDImage')) {
            $copy = null;
            return true;
        }

        throw new FileException('Invalid resource copy, valids are: stream, GDImage');
    }

    /**
     * Set mime type.
     *
     * @param  string $mime
     * @return void
     */
    public final function setMime(string $mime): void
    {
        $this->mime = $mime;
    }

    /**
     * Get mime type.
     *
     * @return string|null
     */
    public final function getMime(): string|null
    {
        return $this->mime;
    }

    /**
     * Free resources.
     *
     * @return void
     */
    public final function free(): void
    {
        if ($this instanceof FileObject && is_resource($this->resource)) {
            $this->freed = fclose($this->resource);
        } elseif ($this instanceof ImageObject) {
            $this->freed = true;
        }

        if (is_resource($this->resourceFile)) {
            fclose($this->resourceFile);
        }

        // Void all.
        $this->resource = $this->resourceFile = $this->resourceType = null;
    }

    /**
     * Check freed state.
     *
     * @return bool
     */
    public final function isFreed(): bool
    {
        return ($this->freed === true);
    }

    /**
     * Create a file/image object from resource.
     *
     * @param  resource    $resource
     * @param  string|null $mime
     * @param  array|null  $options
     * @return static
     * @throws froq\file\FileException
     */
    public static final function fromResource($resource, string $mime = null, array $options = null): static
    {
        $resource || throw new FileException('Empty resource given');

        return new static($resource, $mime, $options);
    }

    /**
     * Create a file object from temporary resource.
     *
     * @param  resource    $resource
     * @param  string|null $mime
     * @param  array|null  $options
     * @return static
     */
    public static final function fromTemporaryResource(string $mime = null, array $options = null): static
    {
        (static::class == FileObject::class)
            || throw new FileException('Method %s() available for only %s', [__function__, FileObject::class]);

        return new static(tmpfile(), $mime, $options);
    }

    /**
     * Check resource validity.
     *
     * @return void
     * @throws froq\file\FileException
     */
    protected final function resourceCheck(): void
    {
        if ($this->freed) {
            throw new FileException('No resource to process with, it is freed');
        }

        if (empty($this->resource) || (
               !is_type_of($this->resource, 'stream')
            && !is_type_of($this->resource, 'GDImage')
        )) {
            throw new FileException('No resource to process with, it is not valid');
        }
    }

    /**
     * Create a file/image object from file.
     *
     * @param  string      $file
     * @param  string|null $mime
     * @param  array|null  $options
     * @return static
     * @throws froq\file\FileException
     */
    abstract public static function fromFile(string $file, string $mime = null, array $options = null): static;

    /**
     * Create a file/image object from string.
     *
     * @param  string      $string
     * @param  string|null $mime
     * @param  array|null  $options
     * @return static
     * @throws froq\file\FileException
     */
    abstract public static function fromString(string $string, string $mime = null, array $options = null): static;
}
