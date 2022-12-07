<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\upload;

use GdImage, Imagick, ImagickException;

/**
 * An image class for working/manipulating images.
 *
 * @package froq\file\upload
 * @class   froq\file\upload\ImageSource
 * @author  Kerem Güneş
 * @since   3.0, 5.0
 */
class ImageSource extends AbstractSource
{
    /** Default quality. */
    public final const QUALITY = -1;

    /** Supported image types. */
    public final const SUPPORTED_TYPES = [
        IMAGETYPE_JPEG, IMAGETYPE_WEBP,
        IMAGETYPE_PNG,  IMAGETYPE_GIF
    ];

    /** Source instance. */
    protected GdImage|Imagick|null $sourceImage = null;

    /** Target instance. */
    protected GdImage|Imagick|null $targetImage = null;

    /** Image info. */
    protected array $info;

    /** New dimensions. */
    protected array $newDimensions;

    /** Default options. */
    protected static array $optionsDefault = [
        'jpegQuality'   => -1, 'webpQuality' => -1, // All default.
        'pngQuality'    => -1, 'pngFilters'  => -1, // All default.
        'tryImagick'    => false,  // Try using Imagick if exists.
        'useImagick'    => false,  // Direct command to use Imagick (causes error if not exists).
        'stripImage'    => false,  // Valid for only Imagick.
        'stripImageIcc' => false,  // Valid for only Imagick.
        'background'    => 'none', // Availables: 'none' for transparency, 'black', 'white'.
    ];

    /** Resized state. */
    protected bool $resized = false;

    /**
     * @override
     */
    public function __construct(array $options = null)
    {
        parent::__construct(array_options($options, self::$optionsDefault));
    }

    /**
     * Resample.
     *
     * @return self
     */
    public final function resample(): self
    {
        return $this->resize(0, 0, ['resample' => true, 'adjust' => false, 'proportion' => false]);
    }

    /**
     * Resize.
     *
     * @param  int        $width
     * @param  int        $height
     * @param  array|null $options
     * @return self
     * @throws froq\file\upload\ImageSourceException
     */
    public final function resize(int $width, int $height, array $options = null): self
    {
        if ($width < 0 || $height < 0) {
            throw new ImageSourceException('Both width and height must be greater than -1');
        } elseif ($width === 0 && $height === 0 && empty($options['resample'])) {
            throw new ImageSourceException('Either width or height must be greater than 0');
        }

        // @defaults=false,true
        $adjust     = (bool) ($options['adjust'] ?? false);
        $proportion = (bool) ($options['proportion'] ?? true);

        [$origWidth, $origHeight, $type] = $this->getInfo();

        // Use original width/height if given ones excessive.
        if ($adjust !== false) {
            if ($width > $origWidth) $width = $origWidth;
            if ($height > $origHeight) $height = $origHeight;
        }

        $newWidth = $newHeight = 0;
        if ($proportion !== false) {
            $factor    = (
                $width === 0 ? $height / $origHeight : (
                    $height === 0 ? $width / $origWidth : (
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

        // @cancel: Not using "bestfit" option below.
        // Fix 1px gap.
        // if ($newWidth < $newHeight) $newWidth += 1;
        // if ($newHeight < $newWidth) $newHeight += 1;

        $this->sourceImage = $this->createSourceImage();
        $this->targetImage = $this->createTargetImage([$newWidth, $newHeight]);

        if ($this->targetImage instanceof Imagick) {
            try {
                $this->targetImage->scaleImage($newWidth, $newHeight);
            } catch (ImagickException $e) {
                throw new ImageSourceException($e);
            }
        } else {
            $background = null;

            if ($this->options['background'] === 'none'
                && in_array($type, [IMAGETYPE_WEBP, IMAGETYPE_PNG, IMAGETYPE_GIF], true)) {
                // Transparent.
                $background = imagecolorallocatealpha($this->targetImage, 255, 255, 255, 127);
                imagealphablending($this->targetImage, false);
                imagesavealpha($this->targetImage, true);
                imageantialias($this->targetImage, true);
            } else {
                // Black & white.
                $background = match ($this->options['background']) {
                    'black' => imagecolorallocate($this->targetImage, 0, 0, 0),
                    'white' => imagecolorallocate($this->targetImage, 255, 255, 255),
                    default => $this->options['background'] !== 'none' ? $this->options['background'] : null
                };
            }

            $background && imagefill($this->targetImage, 0, 0, $background);

            imagecopyresampled(
                $this->targetImage, $this->sourceImage,
                0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight
            ) || throw new ImageSourceException('Failed resampling target image [error: @error]');
        }

        // Store new dimensions.
        $this->newDimensions = [$newWidth, $newHeight];

        // Tick for using resized image & chaining (eg: $im->resize(100, 150)->crop(75, 75)).
        $this->resized = true;

        return $this;
    }

    /**
     * Resize as thumbnail.
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
     * Crop.
     *
     * @param  int        $width
     * @param  int|null   $height
     * @param  array|null $options
     * @return self
     * @throws froq\file\upload\ImageSourceException
     */
    public final function crop(int $width, int $height = null, array $options = null): self
    {
        if ($width < 0 || $height < 0) {
            throw new ImageSourceException('Both width and height must be greater than -1');
        } elseif ($width === 0 && $height === 0) {
            throw new ImageSourceException('Either width or height must be greater than 0');
        }

        // @default=false
        $proportion = (bool) ($options['proportion'] ?? false);

        [$origWidth, $origHeight, $type] = $this->getInfo();

        // Squares.
        $height = $height ?: $width;

        if ($proportion !== false) {
            $divisor    = 4;
            $factor     = max($width, $height);
            $cropWidth  = (int) (0.5 * $factor);
            $cropHeight = (int) (0.5 * $factor);
        } else {
            $divisor    = 2;
            $cropWidth  = $width;
            $cropHeight = $height;
        }

        $x = (int) (($options['x'] ?? null) ?? ($origWidth - $cropWidth) / $divisor);
        $y = (int) (($options['y'] ?? null) ?? ($origHeight - $cropHeight) / $divisor);

        $this->sourceImage = $this->createSourceImage();
        $this->targetImage = $this->createTargetImage([$width, $height]);

        if ($this->targetImage instanceof Imagick) {
            try {
                $this->targetImage->cropImage($width, $height, $x, $y);
                $this->targetImage->setImagePage(0, 0, 0, 0);
            } catch (ImagickException $e) {
                throw new ImageSourceException($e);
            }
        } else {
            $background = null;

            if ($this->options['background'] === 'none'
                && in_array($type, [IMAGETYPE_WEBP, IMAGETYPE_PNG, IMAGETYPE_GIF], true)) {
                // Transparent.
                $background = imagecolorallocatealpha($this->targetImage, 255, 255, 255, 127);
                imagealphablending($this->targetImage, false);
                imagesavealpha($this->targetImage, true);
                imageantialias($this->targetImage, true);
            } else {
                // Black & white.
                $background = match ($this->options['background']) {
                    'black' => imagecolorallocate($this->targetImage, 0, 0, 0),
                    'white' => imagecolorallocate($this->targetImage, 255, 255, 255),
                    default => $this->options['background'] !== 'none' ? $this->options['background'] : null
                };
            }

            $background && imagefill($this->targetImage, 0, 0, $background);

            imagecopyresampled(
                $this->targetImage, $this->sourceImage,
                0, 0, $x, $y, $width, $height, $width, $height
            ) || throw new ImageSourceException('Failed cropping target image [error: @error]');
        }

        // Store new dimensions.
        $this->newDimensions = [$width, $height];

        // Tick for using resized image.
        $this->resized = true;

        return $this;
    }

    /**
     * Crop as thumbnail.
     *
     * @param  int      $width
     * @param  int|null $height
     * @return self
     * @since  5.0
     */
    public final function cropThumbnail(int $width, int $height = null): self
    {
        if ($width < 0 || $height < 0) {
            throw new ImageSourceException('Both width and height must be greater than -1');
        } elseif ($width === 0 && $height === 0) {
            throw new ImageSourceException('Either width or height must be greater than 0');
        }

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

        return $this->resize($width, $height);
    }

    /**
     * Chop.
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
     * Rotate.
     *
     * @param  int|float       $degree
     * @param  int|string|null $background
     * @return self
     * @since  5.0
     */
    public final function rotate(int|float $degree, int|string $background = null): self
    {
        // Also fill info & source/target.
        $this->resized || $this->resample();

        // Use background option if none given.
        $background ??= $this->options['background'];

        if ($this->targetImage instanceof Imagick) {
            try {
                $this->targetImage->rotateImage($background, $degree);
                // Rotate is not enough, merge must be called here too.
                $this->targetImage = $this->targetImage->mergeImageLayers(0);
            } catch (ImagickException $e) {
                throw new ImageSourceException($e);
            }

            [$width, $height] = [$this->targetImage->getImageWidth(),
                                 $this->targetImage->getImageHeight()];
        } else {
            // Fix GD "counter clockwise" stuff.
            $degree = -$degree;

            // Handle background color.
            $background = match ($background) {
                'black' => imagecolorallocate($this->targetImage, 0, 0, 0),
                'white' => imagecolorallocate($this->targetImage, 255, 255, 255),
                default => (
                    // Make default as transparent.
                    $background !== 'none' ? $background
                        : imagecolorallocatealpha($this->targetImage, 255, 255, 255, 127)
            )};

            $this->targetImage = imagerotate($this->targetImage, $degree, $background)
                ?: throw new ImageSourceException('Failed rotating target image [error: @error]');

            [$width, $height] = [imagesx($this->targetImage), imagesy($this->targetImage)];
        }

        // Store new dimensions.
        $this->newDimensions = [$width, $height];

        // Tick for using resized image.
        $this->resized = true;

        return $this;
    }

    /**
     * @inheritDoc froq\file\upload\AbstractSource
     */
    public final function save(string $path = null, string $appendix = null, bool $appendDimensions = false): string
    {
        if ($appendDimensions) {
            $dimensions = $this->getNewDimensions() ?: $this->getDimensions();
            $appendix = ((string) $appendix === '')
                ? vsprintf('%dx%d', $dimensions)
                : vsprintf('%dx%d-%s', [...$dimensions, $appendix]);
        }

        // Resample as least for output.
        $this->resized || $this->resample();

        $target = $this->prepareTarget($path, $appendix);

        $this->overwriteCheck($target);

        if ($this->outputTo($target)) {
            return $target;
        }

        throw new ImageSourceException('Failed saving image [error: @error]');
    }

    /**
     * @inheritDoc froq\file\upload\AbstractSource
     */
    public final function move(string $path = null, string $appendix = null, bool $appendDimensions = false): string
    {
        if ($appendDimensions) {
            $dimensions = $this->getNewDimensions() ?: $this->getDimensions();
            $appendix = ((string) $appendix === '')
                ? vsprintf('%dx%d', $dimensions)
                : vsprintf('%dx%d-%s', [...$dimensions, $appendix]);
        }

        // Resample as least for output.
        $this->resized || $this->resample();

        $target = $this->prepareTarget($path, $appendix);

        $this->overwriteCheck($target);

        if ($this->outputTo($target)) {
            unlink($this->getSource());
            return $target;
        }

        throw new ImageSourceException('Failed moving image [error: @error]');
    }

    /**
     * @inheritDoc froq\file\upload\AbstractSource
     */
    public final function clear(bool $force = false): void
    {
        if ($force || $this->options['clearSource']) {
            $file = $this->getSource();
            is_file($file) && unlink($file);
        }

        // Free sources.
        if ($this->options['clear']) {
            if ($this->usesImagick()) {
                $this->sourceImage?->clear();
                $this->targetImage?->clear();
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
        print $this->toString();
    }

    /**
     * Get filling info.
     *
     * @return void
     * @throws froq\file\upload\ImageSourceException
     */
    public final function getInfo(): array
    {
        if (empty($this->info)) {
            $this->info = getimagesize($this->getSource());
        } elseif ($this->resized) {
            // Update using resized image as info source.
            $this->info = getimagesizefromstring($this->toString());
        }

        if (empty($this->info)) {
            throw new ImageSourceException('Failed getting source info [error: @error]');
        }
        if (empty($this->info[2]) || !in_array($this->info[2], self::SUPPORTED_TYPES, true)) {
            throw new ImageSourceException('Invalid image type [valids: JPEG,WEBP,PNG,GIF]');
        }

        // Add suggestive names.
        $this->info += ['type' => $this->info[2], 'width' => $this->info[0], 'height' => $this->info[1]];

        return $this->info;
    }

    /**
     * Get source image.
     *
     * @return GdImage|Imagick|null
     */
    public final function getSourceImage(): GdImage|Imagick|null
    {
        return $this->sourceImage;
    }

    /**
     * Get target image.
     *
     * @return GdImage|Imagick|null
     */
    public final function getTargetImage(): GdImage|Imagick|null
    {
        return $this->targetImage;
    }

    /**
     * Get dimensions.
     *
     * @return array
     */
    public final function getDimensions(): array
    {
        return array_slice($this->getInfo(), 0, 2);
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
     * Check whether uses GD.
     *
     * @return bool
     * @since  5.0
     */
    public final function usesGd(): bool
    {
        return !$this->usesImagick();
    }

    /**
     * Check whether uses Imagick.
     *
     * @return bool
     * @since  5.0
     */
    public final function usesImagick(): bool
    {
        return $this->options['useImagick'] || ($this->options['tryImagick'] && class_exists('Imagick'));
    }

    /**
     * Get contents Base64 encoded.
     *
     * @return string
     * @since  4.0
     */
    public final function toBase64(): string
    {
        return base64_encode($this->toString());
    }

    /**
     * Get contents Data URL.
     *
     * @return string
     * @since  4.0
     */
    public final function toDataUrl(): string
    {
        return 'data:' . $this->getMime() . ';base64,' . $this->toBase64();
    }

    /**
     * Create source image.
     *
     * @return GdImage|Imagick
     * @throws froq\file\upload\ImageSourceException
     */
    protected final function createSourceImage(): GdImage|Imagick
    {
        if ($this->usesImagick()) {
            if ($this->resized) {
                return $this->sourceImage;
            }
            return new Imagick($this->getSource());
        }

        if ($this->resized) {
            $image = imagecreatefromstring($this->toString());

            // Clear old stuff.
            $this->sourceImage = $this->targetImage = null;
        } else {
            $image = match ($this->info['type']) {
                IMAGETYPE_JPEG => imagecreatefromjpeg($this->getSource()),
                IMAGETYPE_WEBP => imagecreatefromwebp($this->getSource()),
                IMAGETYPE_PNG  => imagecreatefrompng($this->getSource()),
                IMAGETYPE_GIF  => imagecreatefromgif($this->getSource())
            };
        }

        return $image ?: throw new ImageSourceException('Failed creating source image [error: @error]');
    }

    /**
     * Create target image.
     *
     * @param  array|null $dimensions
     * @return GdImage|Imagick
     * @throws froq\file\upload\ImageSourceException
     */
    protected final function createTargetImage(array $dimensions = null): GdImage|Imagick
    {
        if ($this->usesImagick()) {
            if ($this->targetImage) {
                return $this->targetImage;
            } elseif ($this->sourceImage) {
                return $this->sourceImage->getImage();
            }

            throw new ImageSourceException('Cannot create target image, no source image exists');
        }

        $image = imagecreatetruecolor(...$dimensions);

        return $image ?: throw new ImageSourceException('Failed creating target image [error: @error]');
    }

    /**
     * Output processed image as binary string.
     *
     * @return string
     * @throws froq\file\upload\ImageSourceException
     */
    protected final function output(): string
    {
        $image = $this->targetImage ?: throw new ImageSourceException(
            'No target image created yet, call one of these methods first: resample(), '.
            'resize(), resizeThumbnail(), crop(), cropThumbnail(), chop(), rotate()'
        );

        $type = $this->info['type'];

        if ($image instanceof Imagick) {
            $quality = (int) ($this->options['jpegQuality'] ?? $this->options['webpQuality']);
            if ($quality > 0 && ($type === IMAGETYPE_JPEG || $type === IMAGETYPE_WEBP)) {
                $image->setImageCompression(Imagick::COMPRESSION_JPEG);
                $image->setImageCompressionQuality($quality);
            }

            // Strip image, optionally preserving ICC profile.
            if ($this->options['stripImage']) {
                $this->options['stripImageIcc'] || $profiles = $image->getImageProfiles('icc', true);
                $image->stripImage();
                if (!empty($profiles['icc'])) {
                    $image->profileImage('icc', $profiles['icc']);
                }
            }

            try {
                return $image->getImageBlob();
            } catch (ImagickException $e) {
                throw new ImageSourceException($e);
            }
        }

        ob_start();
        if (match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, null, $this->options['jpegQuality']),
            IMAGETYPE_WEBP => imagewebp($image, null, $this->options['webpQuality']),
            IMAGETYPE_PNG  => imagepng($image, null, $this->options['pngQuality'], $this->options['pngFilters']),
            IMAGETYPE_GIF  => imagegif($image)
        }) {
            return ob_get_clean();
        }

        throw new ImageSourceException('Failed processing image [error: @error]');
    }

    /**
     * Output processed image as an absolute file.
     *
     * @param  string $file
     * @return string
     * @throws froq\file\upload\ImageSourceException
     */
    protected final function outputTo(string $file): string
    {
        $image = $this->targetImage ?: throw new ImageSourceException(
            'No target image created yet, call one of these methods first: resample(), '.
            'resize(), resizeThumbnail(), crop(), cropThumbnail(), chop(), rotate()'
        );

        $file = trim($file) ?: throw new ImageSourceException('Empty target file');

        $type = $this->info['type'];

        if ($image instanceof Imagick) {
            $quality = (int) ($this->options['jpegQuality'] ?? $this->options['webpQuality']);
            if ($quality > 0 && ($type === IMAGETYPE_JPEG || $type === IMAGETYPE_WEBP)) {
                $image->setImageCompression(Imagick::COMPRESSION_JPEG);
                $image->setImageCompressionQuality($quality);
            }

            // Strip image, optionally preserving ICC profile.
            if ($this->options['stripImage']) {
                $this->options['stripImageIcc'] || $profiles = $image->getImageProfiles('icc', true);
                $image->stripImage();
                if (!empty($profiles['icc'])) {
                    $image->profileImage('icc', $profiles['icc']);
                }
            }

            try {
                $image->writeImage($file);
                return $file;
            } catch (ImagickException $e) {
                throw new ImageSourceException($e);
            }
        }

        if (match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $file, $this->options['jpegQuality']),
            IMAGETYPE_WEBP => imagewebp($image, $file, $this->options['webpQuality']),
            IMAGETYPE_PNG  => imagepng($image, $file, $this->options['pngQuality'], $this->options['pngFilters']),
            IMAGETYPE_GIF  => imagegif($image, $file)
        }) {
            return $file;
        }

        throw new ImageSourceException('Failed processing image [error: @error]');
    }
}
