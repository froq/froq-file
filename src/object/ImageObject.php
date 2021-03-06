<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file\object;

use froq\file\object\{AbstractObject, ObjectException, FileObject};
use froq\file\upload\ImageSource;
use froq\file\File;

/**
 * Image Object.
 *
 * Represents an image object entity which aims to work with image resources in OOP style.
 *
 * @package froq\file\object
 * @object  froq\file\object\ImageObject
 * @author  Kerem Güneş
 * @since   4.0, 5.0 Moved to object directory.
 */
class ImageObject extends AbstractObject
{
    /** @const string */
    public const MIME_JPEG = 'image/jpeg', MIME_PNG  = 'image/png',
                 MIME_GIF  = 'image/gif',  MIME_WEBP = 'image/webp';

    /** @var array */
    protected static array $mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /** @var array */
    protected static array $optionsDefault = [
        'jpegQuality'  => -1, 'webpQuality' => -1,
        'pngZipLevel'  => -1, 'pngFilters'  => -1,
        'transparency' => true, // For only webp's.
    ];

    /**
     * Get mimes.
     *
     * @return array
     */
    public final function getMimes(): array
    {
        return self::$mimes;
    }

    /**
     * Get a copy of image object as a new ImageObject.
     *
     * @return froq\file\ImageObject
     */
    public final function copy(): ImageObject
    {
        $this->resourceCheck();

        return new ImageObject($this->createResourceCopy(), $this->mime);
    }

    /**
     * Validate image resource.
     *
     * @return bool
     */
    public final function valid(): bool
    {
        try {
            $this->resourceCheck();
            return true;
        } catch (ObjectException) {
            return false;
        }
    }

    /**
     * Resize image.
     *
     * @param  int        $width
     * @param  int        $height
     * @param  array|null $options
     * @return self
     */
    public final function resize(int $width, int $height, array $options = null): self
    {
        $temp = ($resourceFile = $this->getResourceFile())
              ? FileObject::fromTempFile($resourceFile)
              : FileObject::fromTempResource()->setContents($this->getContents());

        $image = (new ImageSource)->prepare(
            ['type' => $this->mime, 'file' => $temp->path(), 'directory' => tmp()],
            ['clear' => false, 'clearSource' => false, 'useImagick' => true,
             'jpegQuality' => $options['jpegQuality'] ?? $this->options['jpegQuality'],
             'webpQuality' => $options['webpQuality'] ?? $this->options['webpQuality']]
        )->resize($width, $height, $options);

        unset($temp);

        $this->resource     = imagecreatefromstring($image->toString());
        $this->resourceFile = $image->save(); // As temp file.

        return $this;
    }

    /**
     * Crop image.
     *
     * @param  int        $width
     * @param  int|null   $height
     * @param  array|null $options
     * @return self
     */
    public final function crop(int $width, int $height = null, array $options = null): self
    {
        $temp = ($resourceFile = $this->getResourceFile())
              ? FileObject::fromTempFile($resourceFile)
              : FileObject::fromTempResource()->setContents($this->getContents());

        $image = (new ImageSource)->prepare(
            ['type' => $this->mime, 'file' => $temp->path(), 'directory' => tmp()],
            ['clear' => false, 'clearSource' => false, 'useImagick' => true,
             'jpegQuality' => $options['jpegQuality'] ?? $this->options['jpegQuality'],
             'webpQuality' => $options['webpQuality'] ?? $this->options['webpQuality']]
        )->crop($width, $height, $options);

        unset($temp);

        $this->resource     = imagecreatefromstring($image->toString());
        $this->resourceFile = $image->save(); // As temp file.

        return $this;
    }

    /**
     * Get image dimensions.
     *
     * @return array
     */
    public final function dimensions(): array
    {
        $this->resourceCheck();

        return [imagesx($this->resource), imagesy($this->resource)];
    }

    /**
     * Get image info, with EXIF data if available.
     *
     * @return array|null
     */
    public final function info(): array|null
    {
        if (($contents = $this->getContents())
            && ($info = getimagesizefromstring($contents))) {
            $info += [
                'width'      => $info[0], 'height' => $info[1],
                'type'       => $info[2], 'size'   => strlen($contents),
                'extension'  => image_type_to_extension($info[2]),
                'exif'       => null,
            ];

            // For only JPEG (and also PNG? https://stackoverflow.com/q/9542359/362780).
            if ($info['type'] == 2 && function_exists('exif_read_data')) {
                $fp = tmpfile();
                fwrite($fp, $contents) && $info['exif'] = exif_read_data($fp);
                fclose($fp);

                // I don't understand why all keys ain't uniform.
                $info['exif'] && $info['exif'] = array_map(
                    fn($v) => is_array($v) ? array_change_key_case($v) : $v,
                    array_change_key_case($info['exif'])
                );
            }

            return $info;
        }

        return null;
    }

    /**
     * Set image contents as binary format.
     *
     * @param  string $contents
     * @return self
     */
    public final function setContents(string $contents): self
    {
        $this->resourceCheck();

        $this->resource = null; // Void.
        $this->resource = imagecreatefromstring($contents);

        return $this;
    }

    /**
     * Get image contents as binary format.
     *
     * @return string|null
     * @throws froq\file\object\ObjectException
     */
    public final function getContents(): string|null
    {
        if ($resourceFile = $this->getResourceFile()) {
            return file_get_contents($resourceFile);
        }

        $this->resourceCheck();

        if (!isset($this->mime)) {
            throw new ObjectException('No MIME given yet, try after calling setMime()');
        }
        if (!in_array($this->mime, self::$mimes)) {
            throw new ObjectException('Invalid MIME `%s`, valids are: %s', [$this->mime, join(', ', self::$mimes)]);
        }

        ob_start();

        // Without copy (that resampled copy), PNG, GIF, WEBP will be losing transparency.
        $copy = null;

        match ($this->mime) {
            self::MIME_JPEG => imagejpeg($this->resource, null, $this->options['jpegQuality']),
            self::MIME_WEBP => $this->options['transparency'] // For some speed (@default=true).
                ? imagewebp($copy = $this->createResourceCopy(), null, $this->options['webpQuality'])
                : imagewebp($this->resource, null, $this->options['webpQuality']),
            self::MIME_PNG  => imagepng($copy = $this->createResourceCopy(), null, $this->options['pngZipLevel'],
                $this->options['pngFilters']),
            self::MIME_GIF  => imagegif($copy = $this->createResourceCopy()),
        };

        $copy && $this->removeResourceCopy($copy);

        return ob_get_clean() ?: null;
    }

    /**
     * Check whether image type is JPEG.
     *
     * @return bool
     */
    public final function isJpeg(): bool
    {
        return ($this->mime == self::MIME_JPEG);
    }

    /**
     * Check whether image type is PNG.
     *
     * @return bool
     */
    public final function isPng(): bool
    {
        return ($this->mime == self::MIME_PNG);
    }

    /**
     * Check whether image type is GIF.
     *
     * @return bool
     */
    public final function isGif(): bool
    {
        return ($this->mime == self::MIME_GIF);
    }

    /**
     * Check whether image type is WEBP.
     *
     * @return bool
     */
    public final function isWebp(): bool
    {
        return ($this->mime == self::MIME_WEBP);
    }

    /**
     * Get Base64 contents.
     *
     * @return string
     */
    public final function toBase64(): string
    {
        return base64_encode($this->getContents());
    }

    /**
     * Get Base64 URL.
     *
     * @return string
     */
    public final function toBase64Url(): string
    {
        return 'data:' . $this->getMime() . ';base64,' . $this->toBase64();
    }

    /**
     * @inheritDoc froq\common\interface\Sizable
     */
    public final function size(): int
    {
        $ret = ($resourceFile = $this->getResourceFile()) ? filesize($resourceFile)
             : (($contents = $this->getContents()) ? strlen($contents) : false);

        return ($ret !== false) ? $ret : -1;
    }

    /**
     * @inheritDoc froq\common\interface\Stringable
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

        $resource = imagecreatefromstring(file_get_contents($file));
        $resource || throw new ObjectException('Cannot create resource [error: %s]', '@error');

        $mime ??= mime_content_type($file);

        $that = new static($resource, $mime, $options);

        // To speed up resize(), crop(), getContents() etc.
        $that->resourceFile = $file;

        return $that;
    }

    /**
     * @inheritDoc froq\file\object\AbstractObject
     */
    public static final function fromString(string $string, string $mime = null, array $options = null): static
    {
        $resource = imagecreatefromstring($string);
        $resource || throw new ObjectException('Cannot create resource [error: %s]', '@error');

        $mime ??= getimagesizefromstring($string)['mime'];

        $that = new static($resource, $mime, $options);

        // To speed up resize(), crop(), getContents() etc.
        $that->resourceFile = file_create_temp('froq/image');
        $that->resourceFile || throw new ObjectException('Cannot create resource file [error: %s]', '@error');

        file_set_contents($that->resourceFile, $string);

        return $that;
    }
}
