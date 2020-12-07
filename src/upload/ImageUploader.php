<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file\upload;

use froq\file\upload\{AbstractUploader, UploadException};
use froq\common\interfaces\Stringable;
use GdImage;

/**
 * Image Uploader.
 *
 * Represents an updloader entity which aims to upload images in OOP style.
 *
 * @package froq\file\upload
 * @object  froq\file\upload\ImageUploader
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0, 5.0 Moved to upload directory.
 */
final class ImageUploader extends AbstractUploader implements Stringable
{
    /** @const int */
    public const QUALITY = -1;

    /** @const array */
    public const SUPPORTED_TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];

    /** @var ?GdImage */
    private ?GdImage $sourceImage;

    /** @var ?GdImage */
    private ?GdImage $destinationImage;

    /** @var array */
    private array $info;

    /** @var array<int> */
    private array $newDimensions;

    /** @var bool */
    private bool $resized = false;

    /**
     * Apply resample process.
     *
     * @return self
     */
    public function resample(): self
    {
        return $this->resize(-1, -1, ['proportion' => false]);
    }

    /**
     * Apply resize process.
     *
     * @param  int        $width
     * @param  int        $height
     * @param  array|null $options
     * @return self
     * @throws froq\file\upload\UploadException
     */
    public function resize(int $width, int $height, array $options = null): self
    {
        // Fill/ensure info.
        $this->fillInfo();

        $this->sourceImage = $this->createSourceImage();

        [$origWidth, $origHeight] = $info = $this->getInfo();
        @ ['adjust' => $adjust, 'proportion' => $proportion] = $options; // @defaults=true

        // Use original width/height if given ones excessive.
        if ($adjust !== false) {
            if ($width > $origWidth) $width = $origWidth;
            if ($height > $origHeight) $height = $origHeight;
        }

        $newWidth = $newHeight = 0;
        if ($proportion !== false) {
            $factor    = (
                $width == -1 ? $height / $origHeight : (
                    $height == -1 ? $width / $origWidth : (
                        min($width / $origWidth, $height / $origHeight)
                    )
                )
            );
            $newWidth  = (int) ($origWidth * $factor);
            $newHeight = (int) ($origHeight * $factor);
        } else {
            $newWidth  = (int) ($width > -1 ? $width : $origWidth);
            $newHeight = (int) ($height > -1 ? $height : $origHeight);
        }

        $this->destinationImage = $this->createDestinationImage([$newWidth, $newHeight]);

        // Handle PNGs/GIFs.
        if (in_array($info['type'], [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            imagealphablending($this->destinationImage, false);
            imagesavealpha($this->destinationImage, true);
            imageantialias($this->destinationImage, true);
            imagefill($this->destinationImage, 0, 0, imagecolorallocatealpha(
                $this->destinationImage, 255, 255, 255, 127 // Tranparent.
            ));
        }

        // Not using imagescale() cos images become dithered when width/height is small.
        imagecopyresampled(
            $this->destinationImage, $this->sourceImage,
            0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight
        ) || throw new UploadException('Failed resampling destination image [error: %s]', '@error');

        // Store new dimensions.
        $this->newDimensions = [$newWidth, $newHeight];

        // For chaining purposes (eg: $up->resize(100, 150)->crop(75, 75)).
        $this->resized = true;

        return $this;
    }

    /**
     * Apply crop process.
     *
     * @param  int        $width
     * @param  int|null   $height
     * @param  array|null $options
     * @return self
     * @throws froq\file\upload\UploadException
     */
    public function crop(int $width, int $height = null, array $options = null): self
    {
        // Fill/ensure info.
        $this->fillInfo();

        $this->sourceImage = $this->createSourceImage();

        // Square crops.
        $height ??= $width;

        [$origWidth, $origHeight] = $info = $this->getInfo();
        @ ['x' => $x, 'y' => $y, 'proportion' => $proportion] = $options; // @defaults=null

        if ($proportion) {
            $factor     = max($width, $height);
            $cropWidth  = (int) (0.5 * $factor);
            $cropHeight = (int) (0.5 * $factor);
            $divisionBy = 4;
        } else {
            $cropWidth  = $width;
            $cropHeight = $height;
            $divisionBy = 2;
        }

        $x ??= (int) (($origWidth - $cropWidth) / $divisionBy);
        $y ??= (int) (($origHeight - $cropHeight) / $divisionBy);

        $this->destinationImage = $this->createDestinationImage([$width, $height]);

        // Handle PNGs/GIFs.
        if (in_array($info['type'], [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            imagealphablending($this->destinationImage, false);
            imagesavealpha($this->destinationImage, true);
            imageantialias($this->destinationImage, true);
            imagefill($this->destinationImage, 0, 0, imagecolorallocatealpha(
                $this->destinationImage, 255, 255, 255, 127 // Tranparent.
            ));
        }

        imagecopyresampled(
            $this->destinationImage, $this->sourceImage,
            0, 0, $x, $y, $width, $height, $width, $height
        ) || throw new UploadException('Failed resampling destination image [error: %s]', '@error');

        // Store new dimensions.
        $this->newDimensions = [$width, $height];

        return $this;
    }

    /**
     * @inheritDoc froq\file\upload\AbstractUploader
     */
    public function save(): string
    {
        $destination = $this->getDestination();

        $this->overwriteCheck($destination);

        $this->outputTo($destination)
            || throw new UploadException('Failed saving image [error: %s]', '@error');

        return $destination;
    }

    /**
     * @inheritDoc froq\file\upload\AbstractUploader
     */
    public function saveAs(string $name, string $appendix = null, bool $appendNewDimensions = false): string
    {
        if ($appendNewDimensions) {
            $newDimensions = $this->getNewDimensions();
            $appendix = ($appendix == null)
                ? vsprintf('%dx%d', $newDimensions)
                : vsprintf('%dx%d-%s', array_merge($newDimensions, [$appendix]));
        }

        $destination = $this->getDestination($name, $appendix);

        $this->overwriteCheck($destination);

        $this->outputTo($destination)
            || throw new UploadException('Failed saving image [error: %s]', '@error');

        return $destination;
    }

    /**
     * @inheritDoc froq\file\upload\AbstractUploader
     */
    public function move(): string
    {
        $source = $this->getSource();
        $destination = $this->getDestination();

        $this->overwriteCheck($destination);

        copy($source, $destination)
            || throw new UploadException('Failed moving image [error: %s]', '@error');

        unlink($source); // Remove source instantly.

        return $destination;
    }

    /**
     * @inheritDoc froq\file\upload\AbstractUploader
     */
    public function moveAs(string $name, string $appendix = null): string
    {
        $source = $this->getSource();
        $destination = $this->getDestination($name, $appendix);

        $this->overwriteCheck($destination);

        copy($source, $destination)
            || throw new UploadException('Failed moving image [error: %s]', '@error');

        unlink($source); // Remove source instantly.

        return $destination;
    }

    /**
     * @inheritDoc froq\file\upload\AbstractUploader
     */
    public function clear(bool $force = false): void
    {
        if ($force) {
            unlink($this->getSource());

            $this->sourceImage = $this->destinationImage = null;
        } else {
            if ($this->options['clearSource']) {
                unlink($this->getSource());
            }

            if ($this->options['clear']) {
                $this->sourceImage = $this->destinationImage = null;
            }
        }
    }

    /**
     * Display processed image as binary string.
     *
     * @return void
     */
    public function display(): void
    {
        $this->output();
    }

    /**
     * Get image info.
     *
     * @return array
     * @throws froq\file\upload\UploadException
     */
    public function getInfo(): array
    {
        if (empty($this->info)) {
            throw new UploadException('No info filled yet, try after calling fillInfo()');
        }

        return $this->info;
    }

    /**
     * Fill image info.
     *
     * @return void
     * @throws froq\file\upload\UploadException
     * @internal
     */
    public function fillInfo(): void
    {
        if ($this->resized) { // Use resized image as source.
            $info = getimagesizefromstring($this->toString());
        } elseif (empty($this->info)) {
            $info = getimagesize($this->getSource());
        }

        if (empty($info)) {
            throw new UploadException('Failed to get source info [error: %s]', '@error');
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
     * @return GdImage|null
     */
    public function getSourceImage(): GdImage|null
    {
        return $this->sourceImage ?? null;
    }

    /**
     * Get destination image.
     *
     * @return GdImage|null
     */
    public function getDestinationImage(): GdImage|null
    {
        return $this->destinationImage ?? null;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->getInfo()['type'];
    }

    /**
     * Get mime.
     *
     * @return string
     */
    public function getMime(): string
    {
        return image_type_to_mime_type($this->getType());
    }

    /**
     * Get dimensions.
     *
     * @return array
     */
    public function getDimensions(): array
    {
        $info = $this->getInfo();

        return [$info['width'], $info['height']];
    }

    /**
     * Get new dimensions.
     *
     * @return array|null
     */
    public function getNewDimensions(): array|null
    {
        return $this->newDimensions ?? null;
    }

    /**
     * Get Base64 contents.
     *
     * @return string
     * @since  4.0
     */
    public function toBase64(): string
    {
        return base64_encode($this->toString());
    }

    /**
     * Get Base64 URL.
     *
     * @return string
     * @since  4.0
     */
    public function toBase64Url(): string
    {
        return 'data:' . $this->getMime() . ';base64,' . $this->toBase64();
    }

    /**
     * @inheritDoc froq\common\interfaces\Stringable
     * @since 4.0 Replaced with getOutputBuffer().
     */
    public function toString(): string
    {
        ob_start();
        $this->output();
        return ob_get_clean();
    }

    /**
     * Create source image.
     *
     * @return GdImage
     * @throws froq\file\upload\UploadException
     */
    private function createSourceImage(): GdImage
    {
        if ($this->resized) {
            // Use resized image as source.
            $image = imagecreatefromstring($this->toString());

            // Clear old sources.
            $this->sourceImage = $this->destinationImage = null;
        } else {
            $image = match ($this->getType()) {
                IMAGETYPE_JPEG => imagecreatefromjpeg($this->getSource()),
                IMAGETYPE_PNG  => imagecreatefrompng($this->getSource()),
                IMAGETYPE_GIF  => imagecreatefromgif($this->getSource()),
                IMAGETYPE_WEBP => imagecreatefromwebp($this->getSource()),
            };
        }

        return $image ? $image : throw new UploadException('Failed creating source image [error: %s]', '@error');
    }

    /**
     * Create destination image.
     *
     * @param  array<int> $dimensions
     * @return GdImage
     * @throws froq\file\upload\UploadException
     */
    private function createDestinationImage(array $dimensions): GdImage
    {
        $image = imagecreatetruecolor(...$dimensions);

        return $image ? $image : throw new UploadException('Failed creating destination image [error: %s]', '@error');
    }

    /**
     * Output processed image as binary string.
     *
     * @return bool
     * @throws froq\file\upload\UploadException
     */
    private function output(): bool
    {
        $image = $this->getDestinationImage();
        $image || throw new UploadException('No destination image created yet, call one of these methods first: '
            . 'resample(), resize(), crop() or cropBy()');

        $ok = match ($this->getType()) {
            IMAGETYPE_JPEG => imagejpeg($image, null, $this->options['jpegQuality']),
            IMAGETYPE_PNG  => imagepng($image),
            IMAGETYPE_GIF  => imagegif($image),
            IMAGETYPE_WEBP => imagewebp($image, null, $this->options['webpQuality']),
        };

        return $ok ? $ok : throw new UploadException('Failed processing image [error: %s]', '@error');
    }

    /**
     * Output processed image as an absolute file.
     *
     * @param  string $to
     * @return bool
     * @throws froq\file\upload\UploadException
     */
    private function outputTo(string $to): bool
    {
        $to = trim($to);
        $to || throw new UploadException('Empty destination file path given');

        $image = $this->getDestinationImage();
        $image || throw new UploadException('No destination image created yet, call one of these methods first: '
            . 'resample(), resize(), crop() or cropBy()');

        $ok = match ($this->getType()) {
            IMAGETYPE_JPEG => imagejpeg($image, $to, $this->options['jpegQuality']),
            IMAGETYPE_PNG  => imagepng($image),
            IMAGETYPE_GIF  => imagegif($image),
            IMAGETYPE_WEBP => imagewebp($image, $to, $this->options['webpQuality']),
        };

        return $ok ? $ok : throw new UploadException('Failed processing image [error: %s]', '@error');
    }
}
