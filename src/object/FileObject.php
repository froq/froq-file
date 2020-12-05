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
     * @return self
     */
    public function rewind(): self
    {
        $this->resourceCheck();

        rewind($this->resource);

        return $this;
    }

    /**
     * Empty file.
     *
     * @return self
     */
    public function empty(): self
    {
        $this->resourceCheck();

        rewind($this->resource) && ftruncate($this->resource, 0);

        return $this;
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
        return $this->getStat()['size'] ?? null;
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

        return is_int($ret = ftell($this->resource)) ? $ret : null;
    }

    /**
     * Get file stat.
     *
     * @return array|null
     */
    public function getStat(): array|null
    {
        $this->resourceCheck();

        return fstat($this->resource) ?: null;
    }

    /**
     * Get file metadata.
     *
     * @return array|null
     */
    public function getMetadata(): array|null
    {
        $this->resourceCheck();

        return fmeta($this->resource) ?: null;
    }

    /**
     * Get file info.
     *
     * @return array|null
     */
    public function getInfo(): array|null
    {
        $this->resourceCheck();

        $stat = fstat($this->resource) ?: null;
        $meta = fmeta($this->resource) ?: null;

        return ($stat && $meta) ? $stat + ['meta' => $meta] : null;
    }

    /**
     * Get file name.
     *
     * @return string|null
     */
    public function getName(): string|null
    {
        return $this->getPathInfo('dirname');
    }

    /**
     * Get file extension.
     *
     * @return string|null
     */
    public function getExtension(): string|null
    {
        return $this->getPathInfo('extension');
    }

    /**
     * Get file directory.
     *
     * @return string|null
     */
    public function getDirectory(): string|null
    {
        return $this->getPathInfo('dirname');
    }

    /**
     * Get file path.
     *
     * @return string|null
     */
    public function getPath(): string|null
    {
        return $this->getMetadata()['uri'] ?? null;
    }

    /**
     * Get file path info.
     *
     * @param  string|null $component
     * @return string|array|null
     */
    public function getPathInfo(string $component = null): string|array|null
    {
        $path = $this->getPath();

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

        // Without this, stats won't be resetted.
        rewind($this->resource);

        ftruncate($this->resource, 0);
        fwrite($this->resource, $contents);
        fseek($this->resource, 0);

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

        $mode = $options['mode'] ?? self::$optionsDefault['mode'];
        $resource = fopen($file, $mode);

        $resource || throw new ObjectException('Cannot create resource [error: %s]', '@error');

        return new static($resource, $mime ?? mime_content_type($file), $options);
    }

    /**
     * @inheritDoc froq\file\object\AbstractObject
     */
    public static function fromString(string $string, string $mime = null, array $options = null): static
    {
        $mode = $options['mode'] ?? self::$optionsDefault['mode'];
        $resource = fopen('php://temp', $mode);

        $resource || throw new ObjectException('Cannot create resource [error: %s]', '@error');

        fwrite($resource, $string) && fseek($resource, 0);

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
