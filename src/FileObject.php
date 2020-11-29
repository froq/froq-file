<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file;

use froq\file\{AbstractObject, FileException, Util as FileUtil};
use froq\common\interfaces\Stringable;

/**
 * File Object.
 *
 * @package froq\file
 * @object  froq\file\FileObject
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
final class FileObject extends AbstractObject implements Stringable
{
    /**
     * Options default.
     * @var array
     */
    private static array $optionsDefault = ['mode' => 'r+b'];

    /**
     * Constructor.
     * @param resource|null $resource
     * @param string|null   $mimeType
     * @param array|null    $options
     */
    public function __construct($resource = null, string $mimeType = null, array $options = null)
    {
        $this->setOptionsDefault($options, self::$optionsDefault);

        parent::__construct($resource, $mimeType);
    }

    /**
     * Write.
     * @param  string $content
     * @return ?int
     */
    public function write(string $content): ?int
    {
        $this->resourceCheck();

        $ret = fwrite($this->resource, $content);

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Read..
     * @param  int $length
     * @return ?string
     */
    public function read(int $length): ?string
    {
        $this->resourceCheck();

        $ret = fread($this->resource, $length);

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Read char.
     * @return ?string
     */
    public function readChar(): ?string
    {
        $this->resourceCheck();

        $ret = fgetc($this->resource);

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Read line.
     * @return ?string
     */
    public function readLine(int $length = 1024): ?string
    {
        $this->resourceCheck();

        $ret = fgets($this->resource, $length);

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Rewind.
     * @return self
     */
    public function rewind(): self
    {
        $this->resourceCheck();

        rewind($this->resource);

        return $this;
    }

    /**
     * Empty.
     * @return self
     */
    public function empty(): self
    {
        $this->resourceCheck();

        rewind($this->resource) && ftruncate($this->resource, 0);

        return $this;
    }

    /**
     * Copy.
     * @return froq\file\FileObject
     */
    public function copy(): FileObject
    {
        $this->resourceCheck();

        return new FileObject($this->createResourceCopy(), $this->mimeType);
    }

    /**
     * Lock.
     * @param  bool $block
     * @return bool
     */
    public function lock(bool $block = true): bool
    {
        $this->resourceCheck();

        return flock($this->resource, $block ? LOCK_EX : LOCK_EX | LOCK_NB);
    }

    /**
     * Unlock.
     * @return bool
     */
    public function unlock(): bool
    {
        $this->resourceCheck();

        return flock($this->resource, LOCK_UN);
    }

    /**
     * Size.
     * @return ?int
     */
    public function size(): ?int
    {
        return $this->getStat()['size'] ?? null;
    }

    /**
     * Offset.
     * @param  int|null $where
     * @param  int|null $whence
     * @return ?int|?bool
     */
    public function offset(int $where = null, int $whence = null)
    {
        return ($where === null) ? $this->getPosition()
             : $this->setPosition($where, $whence ?? SEEK_SET);
    }

    /**
     * Valid.
     * @return bool
     */
    public function valid(): bool
    {
        return !$this->isEnded();
    }

    /**
     * Set position.
     * @param  int $where
     * @param  int $whence
     * @return ?bool
     */
    public function setPosition(int $where, int $whence = SEEK_SET): ?bool
    {
        $this->resourceCheck();

        $ret = fseek($this->resource, $where, $whence);

        return ($ret === 0) ? true : null;
    }

    /**
     * Get position.
     * @return ?int
     */
    public function getPosition(): ?int
    {
        $this->resourceCheck();

        $ret = ftell($this->resource);

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Get stat..
     * @return ?array
     */
    public function getStat(): ?array
    {
        $this->resourceCheck();

        return fstat($this->resource) ?: null;
    }

    /**
     * Get metadata.
     * @return ?array
     */
    public function getMetadata(): ?array
    {
        $this->resourceCheck();

        return stream_get_meta_data($this->resource) ?: null;
    }

    /**
     * Get info..
     * @return ?array
     */
    public function getInfo(): ?array
    {
        $this->resourceCheck();

        $stat = fstat($this->resource) ?: null;
        $meta = stream_get_meta_data($this->resource) ?: null;

        return ($stat && $meta) ? $stat + ['meta' => $meta] : null;
    }

    /**
     * Get name.
     * @return ?string
     */
    public function getName(): ?string
    {
        return basename($this->getMetadata()['uri'] ?? '') ?: null;
    }

    /**
     * Get directory.
     * @return ?string
     */
    public function getDirectory(): ?string
    {
        return dirname($this->getMetadata()['uri'] ?? '') ?: null;
    }

    /**
     * Get path.
     * @return ?string
     */
    public function getPath(): ?string
    {
        return $this->getMetadata()['uri'] ?? null;
    }

    /**
     * Get path info.
     * @return ?array
     */
    public function getPathInfo(): ?array
    {
        $uri = $this->getMetadata()['uri'] ?? null;

        return $uri ? [$uri, dirname($uri), basename($uri)] : null;
    }

    /**
     * Set contents.
     * @param  string $contents
     * @return self
     */
    public function setContents(string $contents): self
    {
        $this->resourceCheck();

        // Without this, stats won't be resetted.
        rewind($this->resource);

        ftruncate($this->resource, 0)
            && fwrite($this->resource, $contents)
                && fseek($this->resource, 0);

        return $this;
    }

    /**
     * Get contents.
     * @return ?string
     */
    public function getContents(): ?string
    {
        $this->resourceCheck();

        $pos = ftell($this->resource);
        $ret = stream_get_contents($this->resource, -1, 0);
        fseek($this->resource, $pos);

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Is ended..
     * @return bool
     */
    public function isEnded(): bool
    {
        return ($this->resource && feof($this->resource));
    }

    /**
     * Is empty.
     * @return bool
     */
    public function isEmpty(): bool
    {
        return ($this->freed || !$this->resource || !fstat($this->resource)['size']);
    }

    /**
     * @inheritDoc froq\file\AbstractObject
     * @implement
     */
    public static function fromFile(string $file, string $mimeType = null, array $options = null): FileObject
    {
        FileUtil::errorCheck($file, $error);
        if ($error != null) {
            throw new FileException($error->getMessage(), null, $error->getCode());
        }

        $mode = $options['mode'] ?? self::$optionsDefault['mode'];
        $resource =@ fopen($file, $mode);
        if (!$resource) {
            throw new FileException('Cannot create resource [error: %s]', ['@error']);
        }

        return new FileObject($resource, $mimeType, $options);
    }

    /**
     * @inheritDoc froq\file\AbstractObject
     * @implement
     */
    public static function fromString(string $string, string $mimeType = null, array $options = null): FileObject
    {
        $mode = $options['mode'] ?? self::$optionsDefault['mode'];
        $resource =@ fopen('php://temp', $mode);
        if (!$resource) {
            throw new FileException('Cannot create resource [error: %s]', ['@error']);
        }

        fwrite($resource, $string) && fseek($resource, 0);

        return new FileObject($resource, $mimeType, $options);
    }

    /**
     * @inheritDoc froq\common\interfaces\Stringable
     */
    public function toString(): string
    {
        return $this->getContents();
    }
}
