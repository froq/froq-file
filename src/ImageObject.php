<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file;

use froq\file\{AbstractObject, FileException, Util as FileUtil, FileObject};
use froq\file\upload\ImageUploader;
use froq\common\interfaces\Stringable;

/**
 * Image Object.
 *
 * Represents an image object entity which aims to work with image resources in OOP style.
 *
 * @package froq\file
 * @object  froq\file\ImageObject
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
final class ImageObject extends AbstractObject implements Stringable
{
    /** @const string */
    public const MIME_JPEG = 'image/jpeg', MIME_PNG  = 'image/png',
                 MIME_GIF  = 'image/gif', MIME_WEBP = 'image/webp';
                 // Far enough for now..
                 /* MIME_WBMP = 'image/vnd.wap.wbmp',
                 MIME_BMP  = 'image/bmp',
                 MIME_XBM  = 'image/xbm',
                 MIME_XPM  = 'image/x-xpixmap'; */

    /** @var array */
    private static array $mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp',
        /* 'image/vnd.wap.wbmp', 'image/bmp', 'image/xbm', 'image/x-xpixmap' */];

    /** @var array */
    private static array $optionsDefault = ['jpegQuality' => -1, 'webpQuality' => -1,
        'pngZipLevel' => -1, 'pngFilters' => -1];

    /**
     * Constructor.
     * @param  resource|null $resource
     * @param  string|null   $mime
     * @throws froq\file\FileException
     */
    public function __construct($resource = null, string $mime = null, array $options = null)
    {
        if ($mime && !in_array($mime, self::$mimes)) {
            throw new FileException("Invalid MIME '%s', valids are: %s", [$mime, join(', ', self::$mimes)]);
        }

        $this->setOptionsDefault($options, self::$optionsDefault);

        parent::__construct($resource, $mime);
    }

    /**
     * Get mimes.
     *
     * @return array
     */
    public function getMimes(): array
    {
        return self::$mimes;
    }

    /**
     * Get a copy of image object as a new `ImageObject`.
     *
     * @return froq\file\ImageObject
     */
    public function copy(): ImageObject
    {
        $this->resourceCheck();

        return new ImageObject($this->createResourceCopy(), $this->mime);
    }

    /**
     * Get image size.
     *
     * @return int|null
     */
    public function size(): int|null
    {
        return ($contents = $this->getContents()) ? strlen($contents) : null;
    }

    /**
     * Validate image resource.
     *
     * @return bool
     */
    public function valid(): bool
    {
        try {
            $this->resourceCheck();
            return true;
        } catch (FileException) {
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
    public function resize(int $width, int $height, array $options = null): self
    {
        $temp = is_resource($this->resourceFile)
            ? new FileObject($this->resourceFile)
            : (new FileObject)->setContents($this->getContents());

        $resource = (new ImageUploader(
            ['type' => $this->mime, 'file' => $temp->getPath(), 'directory' => '/tmp'],
            ['allowedTypes' => '*', 'allowedExtensions' => '*', 'clear' => false, 'clearSource' => false,
             'jpegQuality' => $this->options['jpegQuality'], 'webpQuality' => $this->options['webpQuality']]
        ))->resize($width, $height, $options)->getDestinationImage();

        $temp->free();

        $this->resource = $resource;

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
    public function crop(int $width, int $height = null, array $options = null): self
    {
        $temp = is_resource($this->resourceFile)
            ? new FileObject($this->resourceFile)
            : (new FileObject)->setContents($this->getContents());

        $resource = (new ImageUploader(
            ['type' => $this->mime, 'file' => $temp->getPath(), 'directory' => '/tmp'],
            ['allowedTypes' => '*', 'allowedExtensions' => '*', 'clear' => false, 'clearSource' => false,
             'jpegQuality' => $this->options['jpegQuality'], 'webpQuality' => $this->options['webpQuality']]
        ))->crop($width, $height, $options)->getDestinationImage();

        $temp->free();

        $this->resource = $resource;

        return $this;
    }

    /**
     * Get image dimensions.
     *
     * @return array
     */
    public function getDimensions(): array
    {
        $this->resourceCheck();

        return [imagesx($this->resource), imagesy($this->resource)];
    }

    /**
     * Get image info, with EXIF data if available.
     *
     * @return array|null
     */
    public function getInfo(): array|null
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
                $fp = fopen('php://temp/maxmemory:'. $info['size'], 'w+b');
                fwrite($fp, $contents);
                $info['exif'] = exif_read_data($fp);
                fclose($fp);

                // I don't understand why all keys ain't uniform.
                if ($info['exif']) {
                    $info['exif'] = array_map(
                        fn($v) => is_array($v) ? array_change_key_case($v) : $v,
                        array_change_key_case($info['exif'])
                    );
                }
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
    public function setContents(string $contents): self
    {
        $this->resourceCheck();

        $this->resource = null; // Void old.
        $this->resource = imagecreatefromstring($contents);

        return $this;
    }

    /**
     * Get image contents as binary format.
     *
     * @return string|null
     * @throws froq\file\FileException
     */
    public function getContents(): string|null
    {
        if (is_resource($this->resourceFile)) {
            return freadall($this->resourceFile);
        }

        $this->resourceCheck();

        if (!isset($this->mime)) {
            throw new FileException('No MIME given yet, try after calling setMime()');
        }
        if (!in_array($this->mime, self::$mimes)) {
            throw new FileException("Invalid MIME '%s', valids are: %s", [$this->mime, join(', ', self::$mimes)]);
        }

        ob_start();

        // Without copy (that resampled copy), PNG, GIF, WEBP will be losing transparency.
        $copy = null;

        match ($this->mime) {
            self::MIME_JPEG => imagejpeg($this->resource, null, $this->options['jpegQuality']),
            self::MIME_PNG  => imagepng($copy = $this->createResourceCopy(), null, $this->options['pngZipLevel'],
                    $this->options['pngFilters']),
            self::MIME_GIF  => imagegif($copy = $this->createResourceCopy()),
            self::MIME_WEBP => imagewebp($copy = $this->createResourceCopy(), null, $this->options['webpQuality']),
        };

        $copy && $this->removeResourceCopy($copy);

        return ob_get_clean() ?: null;
    }

    /**
     * Check whether image type is JPEG.
     *
     * @return bool
     */
    public function isJpeg(): bool
    {
        return ($this->mime == self::MIME_JPEG);
    }

    /**
     * Check whether image type is PNG.
     *
     * @return bool
     */
    public function isPng(): bool
    {
        return ($this->mime == self::MIME_PNG);
    }

    /**
     * Check whether image type is GIF.
     *
     * @return bool
     */
    public function isGif(): bool
    {
        return ($this->mime == self::MIME_GIF);
    }

    /**
     * Check whether image type is WEBP.
     *
     * @return bool
     */
    public function isWebp(): bool
    {
        return ($this->mime == self::MIME_WEBP);
    }

    /**
     * Get Base64 contents.
     *
     * @return string
     */
    public function toBase64(): string
    {
        return base64_encode($this->getContents());
    }

    /**
     * Get Base64 URL.
     *
     * @return string
     */
    public function toBase64Url(): string
    {
        return 'data:' . $this->getMime() . ';base64,' . $this->toBase64();
    }

    /**
     * @inheritDoc froq\file\AbstractObject
     */
    public static function fromFile(string $file, string $mime = null, array $options = null): static
    {
        if (FileUtil::errorCheck($file, $error)) {
            throw new FileException($error->getMessage(), null, $error->getCode());
        }

        return self::fromString(file_get_contents($file), mime_content_type($file), $options);
    }

    /**
     * @inheritDoc froq\file\AbstractObject
     */
    public static function fromString(string $string, string $mime = null, array $options = null): static
    {
        $resource = imagecreatefromstring($string);
        $resource || throw new FileException('Cannot create resource [error: %s]', '@error');

        $mime || $mime = (getimagesizefromstring($string)['mime'] ?? null);

        $image = new static($resource, $mime, $options);
        $image->resourceFile = tmpfile(); // Stored for speed up resize(), crop(), getContents().
        fwrite($image->resourceFile, $string);

        return $image;
    }

    /**
     * @inheritDoc froq\common\interfaces\Stringable
     */
    public function toString(): string
    {
        return $this->getContents();
    }
}
