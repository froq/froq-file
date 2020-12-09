<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file\object;

use froq\file\object\{AbstractObject, ObjectException};
use froq\file\File;

/**
 * File Object.
 *
 * Represents a file object entity which aims to work with file resources in OOP style.
 *
 * @package froq\file\object
 * @object  froq\file\object\FileObject
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0, 5.0 Moved to object directory.
 */
class FileObject extends AbstractObject
{
    /** @var array */
    protected static array $optionsDefault = ['mode' => 'r+b'];

    /**
     * Write some contents to file.
     *
     * @param  string $contents
     * @return int|null
     */
    public final function write(string $contents): int|null
    {
        $this->resourceCheck();

        $ret = fwrite($this->resource, $contents);

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Read some contents from file by length.
     *
     * @param  int $length
     * @return string|null
     */
    public final function read(int $length): string|null
    {
        $this->resourceCheck();

        $ret = fread($this->resource, $length);

        return ($ret !== false && $ret !== '') ? $ret : null;
    }

    /**
     * Read all contents from file.
     *
     * @param  int $from
     * @return string|null
     */
    public final function readAll(int $from = 0): string|null
    {
        $this->resourceCheck();

        $ret = freads($this->resource, $from);

        return ($ret !== null) ? $ret : null;
    }

    /**
     * Read a character from file.
     *
     * @return string|null
     */
    public final function readChar(): string|null
    {
        $this->resourceCheck();

        $ret = fgetc($this->resource);

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Read a line from file.
     *
     * @return string|null
     */
    public final function readLine(int $length = 1024): string|null
    {
        $this->resourceCheck();

        $ret = fgets($this->resource, $length);

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Rewind file.
     *
     * @return bool
     */
    public final function rewind(): bool
    {
        $this->resourceCheck();

        return frewind($this->resource);
    }

    /**
     * Empty file.
     *
     * @return bool
     */
    public final function empty(): bool
    {
        $this->resourceCheck();

        return freset($this->resource, '');
    }

    /**
     * Get a copy of file object as a new FileObject.
     *
     * @return froq\file\object\FileObject
     */
    public final function copy(): FileObject
    {
        $this->resourceCheck();

        return new FileObject($this->createResourceCopy(), $this->mime);
    }

    /**
     * Lock file.
     *
     * @param  bool $block
     * @return bool
     */
    public final function lock(bool $block = true): bool
    {
        $this->resourceCheck();

        return flock($this->resource, ($block ? LOCK_EX : LOCK_EX | LOCK_NB));
    }

    /**
     * Unlock file.
     *
     * @return bool
     */
    public final function unlock(): bool
    {
        $this->resourceCheck();

        return flock($this->resource, LOCK_UN);
    }

    /**
     * Get/set file offset.
     *
     * @param  int|null $where
     * @param  int|null $whence
     * @return int|bool|null
     */
    public final function offset(int $where = null, int $whence = null): int|bool|null
    {
        return ($where === null) ? $this->getPosition()
            : $this->setPosition($where, ($whence ?? SEEK_SET));
    }

    /**
     * Validator.
     *
     * @return bool
     */
    public final function valid(): bool
    {
        return !$this->isEnded();
    }

    /**
     * Get file stat.
     *
     * @return array|null
     */
    public final function stat(): array|null
    {
        $this->resourceCheck();

        return fstat($this->resource) ?: null;
    }

    /**
     * Get file meta data.
     *
     * @return array|null
     */
    public final function meta(): array|null
    {
        $this->resourceCheck();

        return fmeta($this->resource) ?: null;
    }

    /**
     * Get file info.
     *
     * @return array|null
     */
    public final function info(): array|null
    {
        $this->resourceCheck();

        return finfo($this->resource) ?: null;
    }

    /**
     * Get file name.
     *
     * @return string|null
     */
    public final function name(): string|null
    {
        return $this->pathInfo('filename');
    }

    /**
     * Get file extension.
     *
     * @return string|null
     */
    public final function extension(): string|null
    {
        return $this->pathInfo('extension');
    }

    /**
     * Get file directory.
     *
     * @return string|null
     */
    public final function directory(): string|null
    {
        return $this->pathInfo('dirname');
    }

    /**
     * Get file path.
     *
     * @return string|null
     */
    public final function path(): string|null
    {
        return $this->meta()['uri'] ?? null;
    }

    /**
     * Get file path info.
     *
     * @param  string|null $component
     * @return string|array|null
     */
    public final function pathInfo(string $component = null): string|array|null
    {
        $path = $this->path();

        if ($path && !strpfx($path, 'php://temp')) {
            return get_path_info($path, $component);
        }

        return null;
    }

    /**
     * Set file contents.
     *
     * @param  string    $contents
     * @param  int|null &$ret
     * @return self
     */
    public final function setContents(string $contents, int &$ret = null): self
    {
        $this->resourceCheck();

        $ret = fwrites($this->resource, $contents);

        return $this;
    }

    /**
     * Get file contents.
     *
     * @return string|null
     */
    public final function getContents(): string|null
    {
        $this->resourceCheck();

        $ret = freads($this->resource);

        return ($ret !== null) ? $ret : null;
    }

    /**
     * Set file pointer position.
     *
     * @param  int $where
     * @param  int $whence
     * @return bool
     */
    public final function setPosition(int $where, int $whence = SEEK_SET): bool
    {
        $this->resourceCheck();

        return !fseek($this->resource, $where, $whence);
    }

    /**
     * Get file pointer position.
     *
     * @return int|null
     */
    public final function getPosition(): int|null
    {
        $this->resourceCheck();

        $ret = ftell($this->resource);

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Check end-of-file state.
     *
     * @return bool
     */
    public final function isEnded(): bool
    {
        return ($this->resource && feof($this->resource));
    }

    /**
     * Check empty state.
     *
     * @return bool
     */
    public final function isEmpty(): bool
    {
        return ($this->freed || !$this->resource || !fsize($this->resource));
    }

    /**
     * @inheritDoc froq\common\interfaces\Sizable
     */
    public final function size(): int
    {
        $this->resourceCheck();

        $ret = fsize($this->resource);

        return ($ret !== null) ? $ret : -1;
    }

    /**
     * @inheritDoc froq\common\interfaces\Stringable
     */
    public final function toString(): string
    {
        return (string) $this->getContents();
    }

    /**
     * @inheritDoc froq\file\object\AbstractObject
     */
    public static final function fromFile(string $file, string $mime = null, array $options = null): static
    {
        if (File::errorCheck($file, $error)) {
            throw new ObjectException($error->getMessage(), null, $error->getCode());
        }

        $resource = fopen($file, ($options['mode'] ?? self::$optionsDefault['mode']));
        $resource || throw new ObjectException('Cannot create resource [error: %s]', '@error');

        return new static($resource, $mime ?? mime_content_type($file), $options);
    }

    /**
     * @inheritDoc froq\file\object\AbstractObject
     */
    public static final function fromString(string $string, string $mime = null, array $options = null): static
    {
        $resource = fopen('php://temp', ($options['mode'] ?? self::$optionsDefault['mode']));
        $resource || throw new ObjectException('Cannot create resource [error: %s]', '@error');

        fwrite($resource, $string);

        return new static($resource, $mime, $options);
    }
}
