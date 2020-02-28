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

use froq\file\{AbstractFileObject, FileException, Util as FileUtil};

/**
 * File Object.
 * @package froq\file
 * @object  froq\file\FileObject
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
final class FileObject extends AbstractFileObject
{

    private static array $optionsDefault = ['openMode' => 'w+b'];

    public function __construct($resource = null, string $mimeType = null, array $options = null)
    {
        $this->setOptionsDefault($options, self::$optionsDefault);

        parent::__construct($resource, $mimeType);
    }

    public function write(string $content): ?int
    {
        $this->resourceCheck();

        $ret = fwrite($this->resource, $content);
        return ($ret !== false) ? $ret : null;
    }

    public function read(int $length): ?string
    {
        $this->resourceCheck();

        $ret = fread($this->resource, $length);
        return ($ret !== false) ? $ret : null;
    }
    public function readChar(): ?string
    {
        $this->resourceCheck();

        $ret = fgetc($this->resource);
        return ($ret !== false) ? $ret : null;
    }
    public function readLine(int $length = 1024): ?string
    {
        $this->resourceCheck();

        $ret = fgets($this->resource, $length);
        return ($ret !== false) ? $ret : null;
    }

    public function rewind(): self
    {
        $this->resourceCheck();

        rewind($this->resource);

        return $this;
    }

    public function empty(): self
    {
        $this->resourceCheck();

        ftruncate($this->resource, 0);

        return $this;
    }

    public function copy(): FileObject
    {
        $this->resourceCheck();

        return new FileObject($this->createResourceCopy(), $this->mimeType);
    }

    public function lock(bool $block = true): bool
    {
        $this->resourceCheck();

        return flock($this->resource, $block ? LOCK_EX : LOCK_EX | LOCK_NB);
    }
    public function unlock(): bool
    {
        $this->resourceCheck();

        return flock($this->resource, LOCK_UN);
    }

    public function size(): ?int
    {
        return $this->getStat()['size'] ?? null;
    }
    public function offset(int $where = null, int $whence = SEEK_SET)#: int|bool
    {
        return ($where === null) ? $this->getPosition()
                                 : $this->setPosition($where, $whence);
    }
    public function valid(): bool
    {
        return !$this->isEnded();
    }

    public function setPosition(int $where, int $whence = SEEK_SET): ?bool
    {
        $this->resourceCheck();

        $ret = fseek($this->resource, $where, $whence);
        return ($ret === 0) ? true : null;
    }
    public function getPosition(): ?int
    {
        $this->resourceCheck();

        $ret = ftell($this->resource);
        return ($ret !== false) ? $ret : null;
    }

    public function getStat(): ?array
    {
        $this->resourceCheck();

        return fstat($this->resource) ?: null;
    }
    public function getMetadata(): ?array
    {
        $this->resourceCheck();

        return stream_get_meta_data($this->resource) ?: null;
    }

    public function getContents(): ?string
    {
        $this->resourceCheck();

        $pos = ftell($this->resource);
        $ret = stream_get_contents($this->resource, -1, 0);
        fseek($this->resource, $pos);

        return ($ret !== false) ? $ret : null;
    }

    public function isEnded(): bool
    {
        return ($this->resource && feof($this->resource));
    }
    public function isEmpty(): bool
    {
        return ($this->freed || !$this->resource || !fstat($this->resource)['size']);
    }

    // @implement
    public static function fromFile(string $file, string $mimeType = null, array $options = null): FileObject
    {
        FileUtil::errorCheck($file, $error);
        if ($error != null) {
            throw new FileException($error->getMessage(), null, $error->getCode());
        }

        $mode     = $options['mode'] ?? self::$optionsDefault['openMode'];
        $resource =@ fopen($file, $mode);
        if (!$resource) {
            throw new FileException('Cannot create resource [error: %s]', ['@error']);
        }

        return new FileObject($resource, $mimeType, $mode);
    }

    // @implement
    public static function fromString(string $string, string $mimeType = null, array $options = null): FileObject
    {
        $mode     = $options['mode'] ?? self::$optionsDefault['openMode'];
        $resource =@ fopen('php://temp', $mode);
        if (!$resource) {
            throw new FileException('Cannot create resource [error: %s]', ['@error']);
        }

        fwrite($resource, $string);
        rewind($resource);

        return new FileObject($resource, null, $mode);
    }

    // @implement
    public function free(): void
    {
        if (is_resource($this->resource)) {
            $this->freed = fclose($this->resource);
            $this->resource = null;
        }
    }
}
