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

// use froq\file\FileException;
use froq\file\{FileException, Util as FileUtil};

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
     * Resource.
     * @var resource
     */
    protected $resource;

    protected ?bool $freed = null;

    public function __construct($resource = null)
    {
        $resource = $resource ?? self::createTemporaryResource();

        if (!$resource || !is_resource($resource)) {
            throw new FileException('Invalid resource "%s" given, must be gd or stream resource',
                [gettype($resource)]);
        }

        $resourceType = get_resource_type($resource);
        if ($resourceType != 'gd' && $resourceType != 'stream') {
            throw new FileException('Invalid resource type "%s" given, valids are: gd, stream',
                [$resourceType]);
        }

        $this->resource = $resource;
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

    public final function isFreed(): bool
    {
        return ($this->freed === true);
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

    public static final function fromResource($resource): self
    {
        if (is_null($resource)) {
            throw new FileException('Null resource given');
        }
        return new static($resource);
    }
    public static final function fromMemoryResource(string $mode = null): self
    {
        return new static(self::createMemoryResource($mode));
    }
    public static final function fromTemporaryResource(string $mode = null, int $maxmem = null): self
    {
        return new static(self::createTemporaryResource($mode, $maxmem));
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

    abstract public static function fromFile(string $file): self;
    abstract public static function fromString(string $string): self;

    abstract public function free(): void;
}
