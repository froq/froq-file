<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file;

use froq\file\upload\{ImageSource, ImageSourceException};
use froq\common\trait\OptionTrait;

/**
 * Proxy class for manipulating images.
 *
 * @package froq\file
 * @object  froq\file\Image
 * @author  Kerem Güneş
 * @since   6.0
 */
class Image
{
    use OptionTrait;

    /** Source file. */
    private string $file;

    /** Source file info. */
    private array $fileInfo = [];

    /** Source object. */
    public readonly ImageSource $source;

    /**
     * Constructor.
     *
     * @param string|null $file
     * @param array|null  $options
     */
    public function __construct(string $file = null,  array $options = null)
    {
        $file && $this->file = $file;

        $this->setOptions($options);
    }

    /**
     * Set file.
     *
     * @param  string $file
     * @return self
     */
    public function setFile(string $file): self
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file.
     *
     * @return string|null
     */
    public function getFile(): string|null
    {
        return $this->file ?? null;
    }

    /**
     * Set file info.
     *
     * @param  array $fileInfo
     * @return self
     */
    public function setFileInfo(array $fileInfo): self
    {
        $this->fileInfo = $fileInfo;

        return $this;
    }

    /**
     * Get file info.
     *
     * @return array
     */
    public function getFileInfo(): array
    {
        return $this->fileInfo;
    }

    /**
     * Set name.
     *
     * @param  string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->fileInfo['name'] = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string|null
     */
    public function getName(): string|null
    {
        return $this->fileInfo['name'] ?? null;
    }

    /**
     * Set extension.
     *
     * @param  string $extension
     * @return self
     */
    public function setExtension(string $extension): self
    {
        $this->fileInfo['extension'] = $extension;

        return $this;
    }

    /**
     * Get extension.
     *
     * @return string|null
     */
    public function getExtension(): string|null
    {
        return $this->fileInfo['extension'] ?? null;
    }

    /**
     * Set directory.
     *
     * @param  string $directory
     * @return self
     */
    public function setDirectory(string $directory): self
    {
        $this->fileInfo['directory'] = $directory;

        return $this;
    }

    /**
     * Get directory.
     *
     * @return string|null
     */
    public function getDirectory(): string|null
    {
        return $this->fileInfo['directory'] ?? null;
    }

    /**
     * Get or create source object.
     *
     * @return froq\file\upload\ImageSource
     * @throws froq\file\ImageException
     */
    public function source(): ImageSource
    {
        if (empty($this->file)) {
            throw new ImageException('No source file given yet');
        }

        if (empty($this->source)) {
            $this->source = new ImageSource();
            $this->source->prepare(['file' => $this->file] + $this->fileInfo, $this->options);
        }

        return $this->source;
    }

    /**
     * Proxy method to source's save().
     *
     * @see froq\file\upload\ImageSource::save()
     */
    public function save(...$args): string
    {
        try {
            return $this->source()->save(...$args);
        } catch (ImageSourceException $e) {
            throw new ImageException($e);
        }
    }

    /**
     * Proxy method to source's move().
     *
     * @see froq\file\upload\ImageSource::move()
     */
    public function move(...$args): string
    {
        try {
            return $this->source()->move(...$args);
        } catch (ImageSourceException $e) {
            throw new ImageException($e);
        }
    }

    /**
     * Proxy method to source's clear().
     *
     * @see froq\file\upload\ImageSource::clear()
     */
    public function clear(...$args): void
    {
        $this->source()->clear(...$args);
    }

    /**
     * Proxy method to source's resample().
     *
     * @see froq\file\upload\ImageSource::resample()
     */
    public function resample(): self
    {
        try {
            $this->source()->resample();
            return $this;
        } catch (ImageSourceException $e) {
            throw new ImageException($e);
        }
    }

    /**
     * Proxy method to source's resize().
     *
     * @see froq\file\upload\ImageSource::resize()
     */
    public function resize(...$args): self
    {
        try {
            $this->source()->resize(...$args);
            return $this;
        } catch (ImageSourceException $e) {
            throw new ImageException($e);
        }
    }

    /**
     * Proxy method to source's resizeThumbnail().
     *
     * @see froq\file\upload\ImageSource::resizeThumbnail()
     */
    public function resizeThumbnail(...$args): self
    {
        try {
            $this->source()->resizeThumbnail(...$args);
            return $this;
        } catch (ImageSourceException $e) {
            throw new ImageException($e);
        }
    }

    /**
     * Proxy method to source's crop().
     *
     * @see froq\file\upload\ImageSource::crop()
     */
    public function crop(...$args): self
    {
        try {
            $this->source()->crop(...$args);
            return $this;
        } catch (ImageSourceException $e) {
            throw new ImageException($e);
        }
    }

    /**
     * Proxy method to source's cropThumbnail().
     *
     * @see froq\file\upload\ImageSource::cropThumbnail()
     */
    public function cropThumbnail(...$args): self
    {
        try {
            $this->source()->cropThumbnail(...$args);
            return $this;
        } catch (ImageSourceException $e) {
            throw new ImageException($e);
        }
    }

    /**
     * Proxy method to source's chop().
     *
     * @see froq\file\upload\ImageSource::chop()
     */
    public function chop(...$args): self
    {
        try {
            $this->source()->chop(...$args);
            return $this;
        } catch (ImageSourceException $e) {
            throw new ImageException($e);
        }
    }

    /**
     * Proxy method to source's rotate().
     *
     * @see froq\file\upload\ImageSource::rotate()
     */
    public function rotate(...$args): self
    {
        try {
            $this->source()->rotate(...$args);
            return $this;
        } catch (ImageSourceException $e) {
            throw new ImageException($e);
        }
    }

    /**
     * Get source file size.
     *
     * @return int
     */
    public function size(): int
    {
        return $this->source()->getSize();
    }

    /**
     * Get source file mime.
     *
     * @return string
     */
    public function mime(): string
    {
        return $this->source()->getMime();
    }

    /**
     * Get source file info.
     *
     * @return array
     */
    public function info(): array
    {
        return $this->source()->getInfo();
    }

    /**
     * Proxy method to source's toString().
     *
     * @see froq\file\upload\ImageSource::toString()
     */
    public function toString(): string
    {
        try {
            return $this->source()->toString();
        } catch (ImageSourceException $e) {
            throw new ImageException($e);
        }
    }

    /**
     * Proxy method to source's toBase64().
     *
     * @see froq\file\upload\ImageSource::toBase64()
     */
    public function toBase64(): string
    {
        try {
            return $this->source()->toBase64();
        } catch (ImageSourceException $e) {
            throw new ImageException($e);
        }
    }

    /**
     * Proxy method to source's toDataUrl().
     *
     * @see froq\file\upload\ImageSource::toDataUrl()
     */
    public function toDataUrl(): string
    {
        try {
            return $this->source()->toDataUrl();
        } catch (ImageSourceException $e) {
            throw new ImageException($e);
        }
    }
}
