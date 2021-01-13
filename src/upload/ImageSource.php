<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file\upload;

use froq\file\upload\{AbstractSource, UploadException};
use GdImage, Imagick, ImagickException;

/**
 * Image Source.
 *
 * Represents an uploaded image entity which aims to work images in OOP style with a few safety options.
 *
 * @package froq\file\upload
 * @object  froq\file\upload\ImageSource
 * @author  Kerem Güneş
 * @since   3.0, 5.0 Moved to upload directory, derived from ImageUploader.
 */
class ImageSource extends AbstractSource
{
    /** @const int */
    public const QUALITY = -1;

    /** @const array */
    public const SUPPORTED_TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];

    /** @var GdImage|Imagick|null */
    protected GdImage|Imagick|null $sourceImage;

    /** @var GdImage|Imagick|null */
    protected GdImage|Imagick|null $targetImage;

    /** @var array */
    protected array $info;

    /** @var array */
    protected array $newDimensions;

    /** @var array */
    protected static array $optionsDefault = [
        'jpegQuality' => -1,    'webpQuality' => -1,
        'useImagick'  => false, 'stripImage'  => false,
    ];

    /** @var bool */
    protected bool $resized = false;

    /** @var bool */
    protected bool $useImagick = false;

    /**
     * Constructor.
     *
     * @param array|null $options
     */
    public function __construct(array $options = null)
    {
        $this->setOptions($options, self::$optionsDefault);

        $this->useImagick = (bool) $this->options['useImagick'];

        parent::__construct($this->options);
    }

    /**
     * Resample an image.
     *
     * @return self
     */
    public final function resample(): self
    {
        return $this->resize(0, 0, ['resample' => true, 'adjust' => false, 'proportion' => false]);
    }

    /**
     * Resize an image.
     *
     * @param  int        $width
     * @param  int        $height
     * @param  array|null $options
     * @return self
     * @throws froq\file\upload\UploadException
     */
    public final function resize(int $width, int $height, array $options = null): self
    {
        if ($width < 0 || $height < 0) {
            throw new UploadException('Both with and height must be greater than -1');
        } elseif ($width == 0 && $height == 0 && empty($options['resample'])) {
            throw new UploadException('Either with or height must be greater than 0');
        }

        $this->fillInfo();

        // @defaults=false,true
        $adjust     = $options['adjust']     ?? false;
        $proportion = $options['proportion'] ?? true;

        [$origWidth, $origHeight, $type] = $this->getInfo();

        // Use original width/height if given ones excessive.
        if ($adjust !== false) {
            if ($width > $origWidth) $width = $origWidth;
            if ($height > $origHeight) $height = $origHeight;
        }

        $newWidth = $newHeight = 0;
        if ($proportion !== false) {
            $factor    = (
                $width == 0 ? $height / $origHeight : (
                    $height == 0 ? $width / $origWidth : (
                        min($width / $origWidth, $height / $origHeight)
                    )
                )
            );
            $newWidth  = (int) ($origWidth * $factor);
            $newHeight = (int) ($origHeight * $factor);
        } else {
            $newWidth  = (int) ($width > 0 ? $width : $origWidth);
            $newHeight = (int) ($height > 0 ? $height : $origHeight);
        }

        // Fix 1px gap. @cancel: not using "bestfit" option below.
        // if ($newWidth < $newHeight) $newWidth += 1;
        // if ($newHeight < $newWidth) $newHeight += 1;

        $this->sourceImage = $this->createSourceImage();
        $this->targetImage = $this->createTargetImage([$newWidth, $newHeight],
            $this->usingImagick() ? $this->sourceImage : null
        );

        if ($this->usingImagick()) {
            $size = filesize($this->getSource());

            try {
                $imagick = $this->targetImage;
                ($size >= 1_000_000 || $size <= 750_000) // Choose faster method (1mb|750kb).
                    ? $imagick->thumbnailImage($newWidth, $newHeight)
                    : $imagick->resizeImage($newWidth, $newHeight, Imagick::FILTER_BOX, 1.0);
            } catch (ImagickException $e) {
                throw new UploadException($e);
            }
        } else {
            // Handle transparency.
            if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
                imagealphablending($this->targetImage, false);
                imagesavealpha($this->targetImage, true);
                imageantialias($this->targetImage, true);
                imagefill($this->targetImage, 0, 0, imagecolorallocatealpha(
                    $this->targetImage, 255, 255, 255, 127 // Transparent.
                ));
            }

            imagecopyresampled(
                $this->targetImage, $this->sourceImage,
                0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight
            ) || throw new UploadException('Failed resampling target image [error: %s]', '@error');

        }

        // Store new dimensions.
        $this->newDimensions = [$newWidth, $newHeight];

        // Tick for using resized image & chaining (eg: $up->resize(100, 150)->crop(75, 75)).
        $this->resized = true;

        return $this;
    }

    /**
     * Resize an image as thumbnail.
     *
     * @param  int      $width
     * @param  int|null $height
     * @return self
     */
    public final function resizeThumbnail(int $width, int $height = null): self
    {
        return $this->resize($width, $height ?? 0);
    }

    /**
     * Crop an image.
     *
     * @param  int        $width
     * @param  int|null   $height
     * @param  array|null $options
     * @return self
     * @throws froq\file\upload\UploadException
     */
    public final function crop(int $width, int $height = null, array $options = null): self
    {
        if ($width < 0 || $height < 0) {
            throw new UploadException('Both with and height must be greater than -1');
        } elseif ($width == 0 && $height == 0) {
            throw new UploadException('Either with or height must be greater than 0');
        }

        $this->fillInfo();

        // @default=false
        $proportion = $options['proportion'] ?? false;

        [$origWidth, $origHeight, $type] = $this->getInfo();

        // Squares.
        $height = $height ?: $width;

        if ($proportion !== false) {
            $div        = 4;
            $factor     = max($width, $height);
            $cropWidth  = (int) (0.5 * $factor);
            $cropHeight = (int) (0.5 * $factor);
        } else {
            $div        = 2;
            $cropWidth  = $width;
            $cropHeight = $height;
        }

        $x = (int) (($options['x'] ?? null) ?? ($origWidth - $cropWidth) / $div);
        $y = (int) (($options['y'] ?? null) ?? ($origHeight - $cropHeight) / $div);

        $this->sourceImage = $this->createSourceImage();
        $this->targetImage = $this->createTargetImage([$width, $height],
            $this->usingImagick() ? $this->sourceImage : null
        );

        if ($this->usingImagick()) {
            try {
                $imagick = $this->targetImage;
                $imagick->cropImage($width, $height, $x, $y);
                $imagick->setImagePage(0, 0, 0, 0);
            } catch (ImagickException $e) {
                throw new UploadException($e);
            }
        } else {
            // Handle transparency.
            if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
                imagealphablending($this->targetImage, false);
                imagesavealpha($this->targetImage, true);
                imageantialias($this->targetImage, true);
                imagefill($this->targetImage, 0, 0, imagecolorallocatealpha(
                    $this->targetImage, 255, 255, 255, 127 // Transparent.
                ));
            }

            imagecopyresampled(
                $this->targetImage, $this->sourceImage,
                0, 0, $x, $y, $width, $height, $width, $height
            ) || throw new UploadException('Failed cropping target image [error: %s]', '@error');
        }

        // Store new dimensions.
        $this->newDimensions = [$width, $height];

        return $this;
    }

    /**
     * Crop an image as thumbnail.
     *
     * @param  int      $width
     * @param  int|null $height
     * @return self
     * @since  5.0
     */
    public final function cropThumbnail(int $width, int $height = null): self
    {
        if ($width < 0 || $height < 0) {
            throw new UploadException('Both with and height must be greater than -1');
        } elseif ($width == 0 && $height == 0) {
            throw new UploadException('Either with or height must be greater than 0');
        }

        $this->fillInfo();

        [$origWidth, $origHeight] = $this->getInfo();

        // Squares.
        $height = $height ?: $width;

        if (($origWidth / $width) < ($origHeight / $height)) {
            $this->crop(
                $origWidth, (int) floor($height * $origWidth / $width),
                ['x' => 0, 'y' => (($origHeight - ($height * $origWidth / $width)) / 2)]
            );
        } else {
            $this->crop(
                (int) ceil($width * $origHeight / $height), $origHeight,
                ['x' => (($origWidth - ($width * $origHeight / $height)) / 2), 'y' => 0]
            );
        }

        // Tick for using resized image.
        $this->resized = true;

        return $this->resize($width, $height);
    }

    /**
     * Chop an image.
     *
     * @param  int $width
     * @param  int $height
     * @param  int $x
     * @param  int $y
     * @return self
     * @since  5.0
     */
    public final function chop(int $width, int $height, int $x, int $y): self
    {
        return $this->crop($width, $height, ['x' => $x, 'y' => $y, 'proportion' => false]);
    }

    /**
     * Rotate an image.
     *
     * @param  int|float       $degree
     * @param  int|string|null $background
     * @return self
     * @since  5.0
     */
    public final function rotate(int|float $degree, int|string $background = null): self
    {
        $this->fillInfo();

        if ($this->usingImagick()) {
            $this->sourceImage = $this->createSourceImage();
            $this->targetImage = $this->createTargetImage([], $this->sourceImage);

            $this->targetImage->rotateImage($background ?? '', $degree);
        } else {
            $this->resample();

            // Fix GD "counter clockwise" stuff.
            $degree = -$degree;

            $this->targetImage = imagerotate($this->targetImage, $degree, $background ?? 0) ?: null;
            $this->targetImage || throw new UploadException('Failed rotating target image [error: %s]', '@error');
        }

        // Tick for using resized image.
        $this->resized = true;

        return $this;
    }

    /**
     * @inheritDoc froq\file\upload\AbstractSource
     */
    public final function save(string $name = null, string $appendix = null, bool $appendNewDimensions = false): string
    {
        if ($appendNewDimensions) {
            $appendix = ($appendix == null)
                ? vsprintf('%dx%d', $this->getNewDimensions())
                : vsprintf('%dx%d-%s', array_merge($this->getNewDimensions(), [$appendix]));
        }

        $target = $this->prepareTarget($name, $appendix);

        $this->overwriteCheck($target);

        if ($this->outputTo($target)) {
            return $target;
        }

        throw new UploadException('Failed saving image [error: %s]', '@error');
    }

    /**
     * @inheritDoc froq\file\upload\AbstractSource
     */
    public final function move(string $name = null, string $appendix = null): string
    {
        $source = $this->getSource();
        $target = $this->prepareTarget($name, $appendix);

        $this->overwriteCheck($target);

        if (rename($source, $target)) {
            return $target;
        }

        throw new UploadException('Failed moving image [error: %s]', '@error');
    }

    /**
     * @inheritDoc froq\file\upload\AbstractSource
     */
    public final function clear(bool $force = false): void
    {
        if ($force || $this->options['clearSource']) {
            is_file($file = $this->getSource()) && unlink($file);
        }

        // Free sources.
        if ($this->options['clear']) {
            if ($this->useImagick) {
                $this->sourceImage && $this->sourceImage->clear();
                $this->targetImage && $this->targetImage->clear();
            }

            $this->sourceImage = $this->targetImage = null;
        }
    }

    /**
     * @inheritDoc froq\common\interface\Stringable
     */
    public final function toString(): string
    {
        return $this->output();
    }

    /**
     * Display processed image as binary string.
     *
     * @return void
     */
    public final function display(): void
    {
        echo $this->toString();
    }

    /**
     * Get image info.
     *
     * @return array
     * @throws froq\file\upload\UploadException
     */
    public final function getInfo(): array
    {
        if (!empty($this->info)) {
            return $this->info;
        }

        throw new UploadException('No info filled yet, try after calling fillInfo()');
    }

    /**
     * Fill image info.
     *
     * @return void
     * @throws froq\file\upload\UploadException
     * @internal
     */
    public final function fillInfo(): void
    {
        if ($this->resized) { // Use resized image as source.
            $info = getimagesizefromstring($this->toString());
        } elseif (empty($this->info)) {
            $info = getimagesize($this->getSource());
        }

        $info = $info ?? ($this->info ?? null);

        if (empty($info)) {
            throw new UploadException('Failed getting source info [error: %s]', '@error');
        }

        if (!in_array($info[2], self::SUPPORTED_TYPES)) {
            throw new UploadException('Invalid image type, valids are: JPEG, PNG, GIF, WEBP');
        }

        // Add suggestive names.
        $info += ['type' => $info[2], 'width' => $info[0], 'height' => $info[1]];

        $this->info = $info;
    }

    /**
     * Get source image.
     *
     * @return GdImage|Imagick|null
     */
    public final function getSourceImage(): GdImage|Imagick|null
    {
        return $this->sourceImage ?? null;
    }

    /**
     * Get target image.
     *
     * @return GdImage|Imagick|null
     */
    public final function getTargetImage(): GdImage|Imagick|null
    {
        return $this->targetImage ?? null;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public final function getType(): int
    {
        return $this->getInfo()['type'];
    }

    /**
     * Get dimensions.
     *
     * @return array
     */
    public final function getDimensions(): array
    {
        $info = $this->getInfo();

        return [$info['width'], $info['height']];
    }

    /**
     * Get new dimensions.
     *
     * @return array|null
     */
    public final function getNewDimensions(): array|null
    {
        return $this->newDimensions ?? null;
    }

    /**
     * Check resized state.
     *
     * @return bool
     * @since  5.0
     */
    public final function resized(): bool
    {
        return $this->resized;
    }

    /**
     * Check GD state.
     *
     * @return bool
     * @since  5.0
     */
    public final function usingGd(): bool
    {
        return !$this->useImagick;
    }

    /**
     * Check Imagick state.
     *
     * @return bool
     * @since  5.0
     */
    public final function usingImagick(): bool
    {
        return $this->useImagick;
    }

    /**
     * Get Base64 contents.
     *
     * @return string
     * @since  4.0
     */
    public final function toBase64(): string
    {
        return base64_encode($this->toString());
    }

    /**
     * Get Base64 URL.
     *
     * @return string
     * @since  4.0
     */
    public final function toBase64Url(): string
    {
        return 'data:' . $this->getMime() . ';base64,' . $this->toBase64();
    }

    /**
     * Create source image.
     *
     * @return GdImage|Imagick
     * @throws froq\file\upload\UploadException
     */
    protected final function createSourceImage(): GdImage|Imagick
    {
        if ($this->usingImagick()) {
            if ($this->resized) {
                return $this->sourceImage;
            }

            return new Imagick($this->getSource());
        }

        if ($this->resized) {
            // Use resized image as source.
            $image = imagecreatefromstring($this->toString());

            // Clear old stuff.
            $this->sourceImage = $this->targetImage = null;
        } else {
            $image = match ($this->getType()) {
                IMAGETYPE_JPEG => imagecreatefromjpeg($this->getSource()),
                IMAGETYPE_PNG  => imagecreatefrompng($this->getSource()),
                IMAGETYPE_GIF  => imagecreatefromgif($this->getSource()),
                IMAGETYPE_WEBP => imagecreatefromwebp($this->getSource()),
            };
        }

        return $image ?: throw new UploadException('Failed creating source image [error: %s]', '@error');
    }

    /**
     * Create target image.
     *
     * @param  array        $dimensions
     * @param  Imagick|null $sourceImage
     * @return GdImage|Imagick
     * @throws froq\file\upload\UploadException
     */
    protected final function createTargetImage(array $dimensions, Imagick $sourceImage = null): GdImage|Imagick
    {
        if ($this->usingImagick()) {
            if (isset($this->targetImage)) {
                return $this->targetImage;
            } elseif (isset($this->sourceImage)) {
                return $this->sourceImage->getImage();
            } elseif ($sourceImage != null) {
                return $sourceImage->getImage();
            }

            throw new UploadException('Cannot create target image, no source image exists');
        }

        // Discard source image.
        $image = imagecreatetruecolor(...$dimensions);

        return $image ?: throw new UploadException('Failed creating target image [error: %s]', '@error');
    }

    /**
     * Output processed image as binary string.
     *
     * @return string
     * @throws froq\file\upload\UploadException
     */
    protected final function output(): string
    {
        $image = $this->getTargetImage();
        $image || throw new UploadException('No target image created yet, call one of these methods first: '
            . 'resample(), resize(), resizeThumbnail(), crop(), cropThumbnail() or rotate()');

        $type = $this->getType();

        if ($image instanceof Imagick) {
            if ($this->options['jpegQuality'] && $type == IMAGETYPE_JPEG) {
                $image->setImageCompression(Imagick::COMPRESSION_JPEG);
                $image->setImageCompressionQuality( // Wants uint as default, interesting..
                    $this->options['jpegQuality'] > -1 ? $this->options['jpegQuality'] : 0
                );
            }

            // Strip preserving ICC profile.
            if ($this->options['stripImage']) {
                $profiles = $image->getImageProfiles('icc', true);
                $image->stripImage();
                if (!empty($profiles['icc'])) {
                    $image->profileImage('icc', $profiles['icc']);
                }
            }

            try {
                return $image->getImageBlob();
            } catch (ImagickException $e) {
                throw new UploadException($e);
            }
        }

        ob_start();

        if (match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, null, $this->options['jpegQuality']),
            IMAGETYPE_PNG  => imagepng($image),
            IMAGETYPE_GIF  => imagegif($image),
            IMAGETYPE_WEBP => imagewebp($image, null, $this->options['webpQuality']),
        }) {
            return ob_get_clean();
        }

        throw new UploadException('Failed processing image [error: %s]', '@error');
    }

    /**
     * Output processed image as an absolute file.
     *
     * @param  string $to
     * @return string
     * @throws froq\file\upload\UploadException
     */
    protected final function outputTo(string $to): string
    {
        $to = trim($to);
        $to || throw new UploadException('Empty target file path given');

        $image = $this->getTargetImage();
        $image || throw new UploadException('No target image created yet, call one of these methods first: '
            . 'resample(), resize(), resizeThumbnail(), crop(), cropThumbnail() or rotate()');

        $type = $this->getType();

        if ($image instanceof Imagick) {
            if ($this->options['jpegQuality'] && $type == IMAGETYPE_JPEG) {
                $image->setImageCompression(Imagick::COMPRESSION_JPEG);
                $image->setImageCompressionQuality( // Wants uint as default, interesting..
                    $this->options['jpegQuality'] > -1 ? $this->options['jpegQuality'] : 0
                );
            }

            // Strip preserving ICC profile.
            if ($this->options['stripImage']) {
                $profiles = $image->getImageProfiles('icc', true);
                $image->stripImage();
                if (!empty($profiles['icc'])) {
                    $image->profileImage('icc', $profiles['icc']);
                }
            }

            try {
                $image->writeImage($to);
                return $to;
            } catch (ImagickException $e) {
                throw new UploadException($e);
            }
        }

        if (match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $to, $this->options['jpegQuality']),
            IMAGETYPE_PNG  => imagepng($image),
            IMAGETYPE_GIF  => imagegif($image),
            IMAGETYPE_WEBP => imagewebp($image, $to, $this->options['webpQuality']),
        }) {
            return $to;
        }

        throw new UploadException('Failed processing image [error: %s]', '@error');
    }
}
