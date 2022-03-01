<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file\object;

use froq\file\File;
use froq\common\trait\{ApplyTrait, OptionTrait};
use froq\common\interface\{Sizable, Stringable};

/**
 * Abstract Object.
 *
 * Represents an abstract class for working with file/image resources in OOP style.
 *
 * @package froq\file\object
 * @object  froq\file\object\AbstractObject
 * @author  Kerem Güneş
 * @since   4.0, 5.0
 */
abstract class AbstractObject implements Sizable, Stringable
{
    /**
     * @see froq\common\trait\ApplyTrait
     * @see froq\common\trait\OptionTrait
     */
    use ApplyTrait, OptionTrait;

    /** @var mixed<resource|GdImage>|null */
    protected mixed $resource = null;

    /** @var ?string */
    protected ?string $resourceFile = null;

    /** @var ?string */
    protected ?string $mime = null;

    /** @var ?bool */
    protected ?bool $freed = null;

    /**
     * Constructor.
     *
     * @param  mixed<resource|GdImage>|null $resource
     * @param  string|null                  $mime
     * @param  array|null                   $options
     * @param  string|null                  $resourceFile @internal
     * @causes froq\file\{TempFileObjectException|FileObjectException|ImageObjectException}
     */
    public function __construct(mixed $resource = null, string $mime = null, array $options = null, string $resourceFile = null)
    {
        if ($resource !== null) {
            if ($this instanceof FileObject) {
                if (!is_stream($resource)) {
                    self::throw('Resource type must be stream, %t given', $resource);
                }

                // For clean ups (mostly for temps).
                $resourceFile ??= fmeta($resource)['uri'];
            } elseif ($this instanceof ImageObject) {
                if ($mime && !in_array($mime, static::MIMES)) {
                    self::throw('Invalid MIME `%s` [valids: %a]', [$mime, static::MIMES]);
                }

                // When a resource given, eg: fopen('path/to/file.jpg', 'rb').
                if (is_stream($resource)) {
                    $temp = ImageObject::fromString(freadall($resource));
                    [$resource, $mime] = [$temp->getResource(), $temp->getMime(), $temp->free()];
                }

                if (!is_image($resource)) {
                    self::throw('Resource type must be stream|GdImage, %t given', $resource);
                }
            }
        }

        $this->resource     = $resource;
        $this->resourceFile = $resourceFile;
        $this->mime         = $mime;

        $this->setOptions($options, static::$optionsDefault);
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->free();
    }

    /**
     * Get resource.
     *
     * @return mixed<resource|GdImage>|null
     */
    public final function getResource(): mixed
    {
        return $this->resource;
    }

    /**
     * Get resource file.
     *
     * @return string|null
     */
    public final function getResourceFile(): string|null
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
     * @return &mixed<resource|GdImage>|null
     */
    public final function &createResourceCopy(): mixed
    {
        if (!$this->resource) {
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
     * @param  mixed<resource|GdImage> &$copy
     * @return bool|null
     */
    public final function removeResourceCopy(mixed &$copy): bool|null
    {
        if (!$copy) {
            return null;
        }

        if ($this instanceof FileObject && is_stream($copy)) {
            return fclose($copy);
        } elseif ($this instanceof ImageObject && is_image($copy)) {
            unset($copy);
            return true;
        }

        self::throw('Invalid resource copy [valids: stream,GdImage]');
    }

    /**
     * Open a file as resource.
     *
     * @param  string|null $file
     * @param  string|null $mime
     * @param  array|null  $options
     * @return self
     * @since  5.0
     */
    public final function open(string $file = null, string $mime = null, array $options = null): self
    {
        self::ban(__method__, 1);

        $file ??= $this->resourceFile;
        if ($file === null || trim($file) === '') {
            self::throw('No file given & no resource file to process');
        }

        // Free.
        $this->free();
        $this->freed = null;

        $resource = null;
        try {
            if ($this instanceof FileObject) {
                $resource = fopen($file, ($options['mode'] ?? static::$optionsDefault['mode']));
            } elseif ($this instanceof ImageObject) {
                if (File::errorCheck($file, $error)) {
                    throw $error;
                }

                $resource = imagecreatefromstring(file_get_contents($file));
            }
        } catch (\Throwable $e) {
            self::throw($e->getMessage(), code: $e->getCode(), cause: $e);
        }

        $this->resource     = $resource ?: self::throw('Cannot create resource [error: @error]');
        $this->resourceFile = $file;    // To clean ups & speed ups resize(), crop(), getContents() etc.
        $this->mime         = $mime     ?? mime_content_type($file);
        $this->options      = $options  ?? $this->options;

        return $this;
    }

    /**
     * Clean up resource & resource file.
     *
     * @return bool
     * @since  5.0
     */
    public final function close(): bool
    {
        self::ban(__method__, 1);

        return $this->free();
    }

    /**
     * Save file/image object contents to an absolute file.
     *
     * @param  string      $directory
     * @param  string|null $name
     * @param  int|null    $mode
     * @return string
     * @causes froq\file\{TempFileObjectException|FileObjectException|ImageObjectException}
     */
    public final function save(string $directory, string $name = null, int $mode = null): string
    {
        if (trim($directory) === '') {
            self::throw('Empty directory given');
        }

        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            self::throw(
                'Cannot make directory [directory: %s, error: %s]',
                [$directory, '@error']
            );
        } elseif (!is_writable($directory)) {
            self::throw(
                'Cannot write directory, not writable [directory: %s]',
                $directory
            );
        }

        // Make a random UUID name if no name given.
        $file = chop($directory, '/') .'/'. ($name ?: uuid(timed: true));

        // Default is 0644.
        $mode && touch($file) && chmod($file, $mode);

        if (file_put_contents($file, $this->toString()) === false) {
            self::throw('Cannot write file [error: @error]');
        }

        return $file;
    }

    /**
     * Unsave file/image a saved absolute file.
     *
     * @param  string $file
     * @return bool
     */
    public final function unsave(string $file): bool
    {
        return is_file($file) && unlink($file);
    }

    /**
     * Free resources.
     *
     * @return bool
     */
    public final function free(): bool
    {
        if (!$this->freed) {
            $this->freed = true;

            if ($this->resource && is_stream($this->resource)) {
                $this->freed = fclose($this->resource);
            }
            if ($this->resourceFile && is_tmpnam($this->resourceFile)) {
                $this->freed = unlink($this->resourceFile);
            }

            // Clean up.
            $this->resource     = null;
            $this->resourceFile = null;

            return $this->freed;
        }

        // Closed before.
        return false;
    }

    /**
     * Check freed state.
     *
     * @return bool
     */
    public final function isFreed(): bool
    {
        return (bool) $this->freed;
    }

    /**
     * Check resource state.
     *
     * @return bool
     */
    public final function isValid(): bool
    {
        return $this->resource && (is_stream($this->resource) || is_image($this->resource));
    }

    /**
     * Create a file/image object from resource.
     *
     * @param  resource    $resource
     * @param  string|null $mime
     * @param  array|null  $options
     * @param  string|null $file @internal
     * @return static
     * @causes froq\file\{TempFileObjectException|FileObjectException|ImageObjectException}
     */
    public static final function fromResource($resource, string $mime = null, array $options = null, string $file = null): static
    {
        self::ban(__method__);

        $resource ?: self::throw('Empty resource given');

        return new static($resource, $mime, $options, resourceFile: $file);
    }

    /**
     * Create a file object from temporary resource.
     *
     * @param  string|null $mime
     * @param  array|null  $options
     * @return static
     * @causes froq\file\{TempFileObjectException|FileObjectException|ImageObjectException}
     */
    public static final function fromTempResource(string $mime = null, array $options = null): static
    {
        self::ban(__method__);

        // Not using 'php://temp', cus used for file_get_contents() stuff.
        $resource = tmpfile() ?: self::throw('Empty resource returned [error: @error]');

        return new static($resource, $mime, $options, resourceFile: null);
    }

    /**
     * Create a file object from a temporary file.
     *
     * @param  string|null $file
     * @param  string|null $mime
     * @param  array|null  $options
     * @return static
     * @causes froq\file\{TempFileObjectException|FileObjectException|ImageObjectException}
     */
    public static final function fromTempFile(string $mime = null, array $options = null): static
    {
        self::ban(__method__);

        $file     = tmpnam();
        $resource = fopen($file, 'w+b') ?: self::throw('Empty resource returned [error: @error]');

        return new static($resource, $mime, $options, resourceFile: $file);
    }

    /**
     * Check resource validity.
     *
     * @return void
     * @causes froq\file\{TempFileObjectException|FileObjectException|ImageObjectException}
     */
    protected final function resourceCheck(): void
    {
        if ($this->isFreed()) {
            self::throw('No resource to process with, freed');
        }
        if (!$this->isValid()) {
            self::throw('No resource to process with, not valid [resource: %s]',
                get_type($this->resource));
        }
    }

    /**
     * Ban given method for unrelated objects.
     */
    private static function ban(string $method, int $type = 0): void
    {
        if ($type == 0 && !is_class_of(static::class, FileObject::class)) {
            self::throw('Method %s() is not available for non %s object(s)', [$method, FileObject::class]);
        } elseif ($type == 1 && is_class_of(static::class, TempFileObject::class)) {
            self::throw('Method %s() is not available for %s object(s)', [$method, TempFileObject::class]);
        }
    }

    /**
     * Throw a related exception.
     */
    private static function throw(...$args): void
    {
        $exception = match (true) {
            is_class_of(static::class, TempFileObject::class) => TempFileObjectException::class,
            is_class_of(static::class, FileObject::class)     => FileObjectException::class,
            is_class_of(static::class, ImageObject::class)    => ImageObjectException::class,
        };

        throw new $exception(...$args);
    }

    /**
     * Create a file/image object from file.
     *
     * @param  string      $file
     * @param  string|null $mime
     * @param  array|null  $options
     * @return static
     * @throws froq\file\object\{FileObjectException|ImageObjectException}
     */
    public abstract static function fromFile(string $file, string $mime = null, array $options = null): static;

    /**
     * Create a file/image object from string.
     *
     * @param  string      $string
     * @param  string|null $mime
     * @param  array|null  $options
     * @return static
     * @throws froq\file\object\{FileObjectException|ImageObjectException}
     */
    public abstract static function fromString(string $string, string $mime = null, array $options = null): static;
}
