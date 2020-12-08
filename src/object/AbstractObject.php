<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file\object;

use froq\file\object\{ObjectException, FileObject, ImageObject};
use froq\common\traits\{OptionTrait, ApplyTrait};
use froq\common\interfaces\{Sizable, Stringable};

/**
 * Abstract Object.
 *
 * Represents an abstract object entity which aims to work with file/image resources in OOP style.
 *
 * @package froq\file\object
 * @object  froq\file\object\AbstractObject
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0, 5.0 Moved to object directory.
 */
abstract class AbstractObject implements Sizable, Stringable
{
    /**
     * Option & Apply traits.
     * @see froq\common\traits\OptionTrait
     * @see froq\common\traits\ApplyTrait
     */
    use OptionTrait, ApplyTrait;

    /** @var resource|GdImage|null */
    protected $resource;

    /** @var resource|null */
    protected $resourceFile;

    /** @var ?string */
    protected ?string $mime;

    /** @var ?bool */
    protected ?bool $freed = null;

    /**
     * Constructor.
     *
     * @param  resource|GdImage|null $resource
     * @param  string|null           $mime
     * @throws froq\file\ObjectException
     */
    public function __construct($resource = null, string $mime = null, array $options = null)
    {
        if ($resource != null) {
            if ($this instanceof FileObject) {
                if (!is_type_of($resource, 'stream')) {
                    throw new ObjectException("Resource type must be stream, '%s' given",
                        get_type($resource));
                }
            } elseif ($this instanceof ImageObject) {
                if ($mime && !in_array($mime, static::$mimes)) {
                    throw new ObjectException("Invalid MIME '%s', valids are: %s",
                        [$mime, join(', ', static::$mimes)]);
                }

                // When a resource given, eg: fopen('path/to/file.jpg', 'rb').
                if (is_stream($resource)) {
                    $temp = ImageObject::fromString(freadall($resource));
                    [$resource, $mime] = [$temp->getResource(), $temp->getMime(), $temp->free()];
                }

                if (!is_type_of($resource, 'GdImage')) {
                    throw new ObjectException("Resource type must be GdImage, '%s' given",
                        get_type($resource));
                }
            }

            $this->setResource($resource);
        }

        $this->mime = $mime;

        $this->setOptions($options, static::$optionsDefault);
    }

    /**
     * Set resource.
     *
     * @return resource|GdImage|null
     */
    public final function setResource($resource): void
    {
        $this->free();
        $this->freed = null;
        $this->resource = $resource;
    }

    /**
     * Get resource.
     *
     * @return resource|GdImage|null
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
     * Create a resource copy.
     *
     * @return &resource|&GdImage|null
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

            if (in_array($this->mime, ['image/png', 'image/gif', 'image/webp'])) {
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
     * @param  resource|GdImage &$copy
     * @return bool|null
     */
    public final function removeResourceCopy(&$copy): bool|null
    {
        if (empty($copy)) {
            return null;
        }

        if ($this instanceof FileObject && is_stream($copy)) {
            return fclose($copy);
        } elseif ($this instanceof ImageObject && is_image($copy)) {
            unset($copy);
            return true;
        }

        throw new ObjectException('Invalid resource copy, valids are: stream, GdImage');
    }

    /**
     * Save file/image object contents to an absolute file.
     *
     * @param  string      $directory
     * @param  string|null $name
     * @param  int|null    $mode
     * @return string
     * @throws froq\file\ObjectException
     */
    public final function save(string $directory, string $name = null, int $mode = null): string
    {
        $directory = trim($directory);
        if ($directory == '') {
            throw new ObjectException('Empty directory given');
        }

        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new ObjectException('Cannot make directory [error: %s, directory: %s]',
                ['@error', $directory]);
        } elseif (!is_writable($directory)) {
            throw new ObjectException('Cannot into write %s directory, it is not writable',
                $directory);
        }

        // Make a random UUID name if no name given.
        $file = chop($directory, '/') .'/'. ($name ?: uuid());

        // Default is 0644.
        $mode && touch($file) && chmod($file, $mode);

        if (file_write($file, $this->toString()) === null) {
            throw new ObjectException('Cannot write file [error: %s]', '@error');
        }

        return $file;
    }

    /**
     * Free resources.
     *
     * @return void
     */
    public final function free(): void
    {
        if ($this->resource && is_stream($this->resource)) {
            fclose($this->resource);
        }
        if ($this->resourceFile && is_stream($this->resourceFile)) {
            fclose($this->resourceFile);
        }

        $this->freed = true;

        // Void all.
        $this->resource = $this->resourceFile = null;
    }

    /**
     * Check freed state.
     *
     * @return bool
     */
    public final function isFreed(): bool
    {
        return !!$this->freed;
    }

    /**
     * Create a file/image object from resource.
     *
     * @param  resource    $resource
     * @param  string|null $mime
     * @param  array|null  $options
     * @return static
     * @throws froq\file\object\ObjectException
     */
    public static final function fromResource($resource, string $mime = null, array $options = null): static
    {
        $resource || throw new ObjectException('Empty resource given');

        return new static($resource, $mime, $options);
    }

    /**
     * Create a file object from temporary resource.
     *
     * @param  string|null $mime
     * @param  array|null  $options
     * @return static
     */
    public static final function fromTempResource(string $mime = null, array $options = null): static
    {
        if (static::class != FileObject::class) {
            throw new ObjectException('Method available for only %s object', FileObject::class);
        }

        return new static(tmpfile(), $mime, $options);
    }

    /**
     * Check resource validity.
     *
     * @return void
     * @throws froq\file\object\ObjectException
     */
    protected final function resourceCheck(): void
    {
        if ($this->freed) {
            throw new ObjectException('No resource to process with, it is freed');
        }
        if (!is_stream($this->resource) && !is_image($this->resource)) {
            throw new ObjectException('No resource to process with, it is not valid');
        }
    }

    /**
     * Create a file/image object from file.
     *
     * @param  string      $file
     * @param  string|null $mime
     * @param  array|null  $options
     * @return static
     * @throws froq\file\object\ObjectException
     */
    abstract public static function fromFile(string $file, string $mime = null, array $options = null): static;

    /**
     * Create a file/image object from string.
     *
     * @param  string      $string
     * @param  string|null $mime
     * @param  array|null  $options
     * @return static
     * @throws froq\file\object\ObjectException
     */
    abstract public static function fromString(string $string, string $mime = null, array $options = null): static;
}
