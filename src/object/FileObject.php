<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file\object;

use froq\file\Util as FileUtil;
use froq\file\object\{AbstractObject, ObjectException};
use froq\common\interfaces\Stringable;

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
final class FileObject extends AbstractObject implements Stringable
{
    /** @var array */
    protected static array $optionsDefault = ['mode' => 'r+b'];

    /**
     * Write some content of file.
     *
     * @param  string $content
     * @return int|null
     */
    public function write(string $content): int|null
    {
        $this->resourceCheck();

        $ret = fwrite($this->resource, $content);

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Read some content of file by length.
     *
     * @param  int $length
     * @return string|null
     */
    public function read(int $length): string|null
    {
        $this->resourceCheck();

        $ret = fread($this->resource, $length);

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Read all content of file.
     *
     * @param  int $from
     * @return string|null
     */
    public function readAll(int $from = 0): string|null
    {
        $this->resourceCheck();

        $ret = freadall($this->resource, $from);

        return ($ret !== null) ? $ret : null;
    }

    /**
     * Read a character from file.
     *
     * @return string|null
     */
    public function readChar(): string|null
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
    public function readLine(int $length = 1024): string|null
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
    public function rewind(): bool
    {
        $this->resourceCheck();

        return frewind($this->resource);
    }

    /**
     * Empty file.
     *
     * @return bool
     */
    public function empty(): bool
    {
        $this->resourceCheck();

        return freset($this->resource, '');
    }

    /**
     * Get a copy of file object as a new `FileObject`.
     *
     * @return froq\file\object\FileObject
     */
    public function copy(): FileObject
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
    public function lock(bool $block = true): bool
    {
        $this->resourceCheck();

        return flock($this->resource, $block ? LOCK_EX : LOCK_EX | LOCK_NB);
    }

    /**
     * Unlock file.
     *
     * @return bool
     */
    public function unlock(): bool
    {
        $this->resourceCheck();

        return flock($this->resource, LOCK_UN);
    }

    /**
     * Get file size.
     *
     * @return int|null
     */
    public function size(): int|null
    {
        return $this->stat()['size'];
    }

    /**
     * Get/set file offset.
     *
     * @param  int|null $where
     * @param  int|null $whence
     * @return int|bool|null
     */
    public function offset(int $where = null, int $whence = null): int|bool|null
    {
        return ($where === null) ? $this->getPosition()
            : $this->setPosition($where, $whence ?? SEEK_SET);
    }

    /**
     * Validator.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return !$this->isEnded();
    }

    /**
     * Get file stat.
     *
     * @return array|null
     */
    public function stat(): array|null
    {
        $this->resourceCheck();

        return fstat($this->resource) ?: null;
    }

    /**
     * Get file meta data.
     *
     * @return array|null
     */
    public function meta(): array|null
    {
        $this->resourceCheck();

        return fmeta($this->resource) ?: null;
    }

    /**
     * Get file info.
     *
     * @return array|null
     */
    public function info(): array|null
    {
        $this->resourceCheck();

        return finfo($this->resource) ?: null;
    }

    /**
     * Get file name.
     *
     * @return string|null
     */
    public function name(): string|null
    {
        return $this->pathInfo('filename');
    }

    /**
     * Get file extension.
     *
     * @return string|null
     */
    public function extension(): string|null
    {
        return $this->pathInfo('extension');
    }

    /**
     * Get file directory.
     *
     * @return string|null
     */
    public function directory(): string|null
    {
        return $this->pathInfo('dirname');
    }

    /**
     * Get file path.
     *
     * @return string|null
     */
    public function path(): string|null
    {
        return $this->meta()['uri'] ?? null;
    }

    /**
     * Get file path info.
     *
     * @param  string|null $component
     * @return string|array|null
     */
    public function pathInfo(string $component = null): string|array|null
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
     * @param  string $contents
     * @return self
     */
    public function setContents(string $contents): self
    {
        $this->resourceCheck();

        freset($this->resource, $contents);

        return $this;
    }

    /**
     * Get file contents.
     *
     * @return string|null
     */
    public function getContents(): string|null
    {
        $this->resourceCheck();

        $pos = ftell($this->resource);
        $ret = freadall($this->resource);
        fseek($this->resource, $pos);

        return ($ret !== null) ? $ret : null;
    }

    /**
     * Set file pointer position.
     *
     * @param  int $where
     * @param  int $whence
     * @return bool
     */
    public function setPosition(int $where, int $whence = SEEK_SET): bool
    {
        $this->resourceCheck();

        return !fseek($this->resource, $where, $whence);
    }

    /**
     * Get file pointer position.
     *
     * @return int|null
     */
    public function getPosition(): int|null
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
    public function isEnded(): bool
    {
        return ($this->resource && feof($this->resource));
    }

    /**
     * Check empty state.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return ($this->freed || !$this->resource || !fstat($this->resource)['size']);
    }

    /**
     * @inheritDoc froq\file\object\AbstractObject
     */
    public static function fromFile(string $file, string $mime = null, array $options = null): static
    {
        if (FileUtil::errorCheck($file, $error)) {
            throw new ObjectException($error->getMessage(), null, $error->getCode());
        }

        $resource = fopen($file, ($options['mode'] ?? self::$optionsDefault['mode']));
        $resource || throw new ObjectException('Cannot create resource [error: %s]', '@error');

        return new static($resource, $mime ?? mime_content_type($file), $options);
    }

    /**
     * @inheritDoc froq\file\object\AbstractObject
     */
    public static function fromString(string $string, string $mime = null, array $options = null): static
    {
        $resource = fopen('php://temp', ($options['mode'] ?? self::$optionsDefault['mode']));
        $resource || throw new ObjectException('Cannot create resource [error: %s]', '@error');

        fwrite($resource, $string);

        return new static($resource, $mime, $options);
    }

    /**
     * @inheritDoc froq\common\interfaces\Stringable
     */
    public function toString(): string
    {
        return $this->getContents();
    }
}
