<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file\upload;

use froq\file\upload\{AbstractUploader, UploadException};
use froq\common\interfaces\Stringable;

/**
 * Image Uploader.
 *
 * @package froq\file\upload
 * @object  froq\file\upload\ImageUploader
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0, 5.0 Moved to upload directory.
 */
final class ImageUploader extends AbstractUploader implements Stringable
{
    /**
     * Supported types.
     * @const array
     */
    public const SUPPORTED_TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];

    /**
     * Quality.
     * @const int
     */
    public const QUALITY = -1;

    /**
     * Info.
     * @var array
     */
    private array $info;

    /**
     * Source resource.
     * @var resource
     */
    private $sourceResource;

    /**
     * Destination resource.
     * @var resource
     */
    private $destinationResource;

    /**
     * New dimensions.
     * @var array<int>
     */
    private array $newDimensions;

    /**
     * Resized.
     * @var bool
     */
    private bool $resized = false;

    /**
     * Resample.
     * @return self
     */
    public function resample(): self
    {
        return $this->resize(-1, -1, false);
    }

    /**
     * Resize.
     * @param  int  $width
     * @param  int  $height
     * @param  bool $proportional
     * @param  bool $fixExtraDimensions
     * @return self
     * @throws froq\file\upload\UploadException
     */
    public function resize(int $width, int $height, bool $proportional = true, bool $fixExtraDimensions = true): self
    {
        // Fill/ensure info.
        $this->fillInfo();

        $this->sourceResource =@ $this->createSourceResource();
        if (!$this->sourceResource) {
            throw new UploadException('Failed creating source resource [error: %s]', ['@error']);
        }

        [$origWidth, $origHeight] = $info = $this->getInfo();

        // Use original width/height if given ones excessive.
        if ($fixExtraDimensions) {
            if ($width > $origWidth) $width = $origWidth;
            if ($height > $origHeight) $height = $origHeight;
        }

        $newWidth = $newHeight = 0;
        if ($proportional) {
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

        $this->destinationResource =@ imagecreatetruecolor($newWidth, $newHeight);
        if (!$this->destinationResource) {
            throw new UploadException('Failed creating destination resource [error: %s]', ['@error']);
        }

        // Handle PNGs/GIFs.
        if (in_array($info['type'], [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            imagealphablending($this->destinationResource, false);
            imagesavealpha($this->destinationResource, true);
            imageantialias($this->destinationResource, true);
            imagefill($this->destinationResource, 0, 0, imagecolorallocatealpha(
                $this->destinationResource, 255, 255, 255, 127 // Tranparent.
            ));
        }

        // Not using imagescale() cos images become dithered when width/height is small.
        $ok =@ imagecopyresampled($this->destinationResource, $this->sourceResource, 0, 0, 0, 0,
            $newWidth, $newHeight, $origWidth, $origHeight);
        if (!$ok) {
            throw new UploadException('Failed resampling destination resource [error: %s]', ['@error']);
        }

        // Store new dimensions.
        $this->newDimensions = [$newWidth, $newHeight];

        // For chaining situations (eg: $up->resize(100, 150)->crop(75, 75)).
        $this->resized = true;

        return $this;
    }

    /**
     * Crop.
     * @param  int      $width
     * @param  int|null $height
     * @param  int|null $x
     * @param  int|null $y
     * @param  bool     $proportional
     * @return self
     * @throws froq\file\upload\UploadException
     */
    public function crop(int $width, int $height = null, int $x = null, int $y = null, bool $proportional = false): self
    {
        // Fill/ensure info.
        $this->fillInfo();

        $this->sourceResource =@ $this->createSourceResource();
        if (!$this->sourceResource) {
            throw new UploadException('Failed creating source resource [error: %s]', ['@error']);
        }

        // Square crops.
        $height = $height ?? $width;

        [$origWidth, $origHeight] = $info = $this->getInfo();

        if (!$proportional) {
            $cropWidth  = $width;
            $cropHeight = $height;
            $divisionBy = 2;
        } else {
            $factor     = ($width > $height) ? $width : $height;
            $cropWidth  = (int) (0.5 * $factor);
            $cropHeight = (int) (0.5 * $factor);
            $divisionBy = 4;
        }

        $x ??= (int) (($origWidth - $cropWidth) / $divisionBy);
        $y ??= (int) (($origHeight - $cropHeight) / $divisionBy);

        $this->destinationResource =@ imagecreatetruecolor($width, $height);
        if (!$this->destinationResource) {
            throw new UploadException('Failed creating destination resource [error: %s]', ['@error']);
        }

        // Handle PNGs/GIFs.
        if (in_array($info['type'], [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            imagealphablending($this->destinationResource, false);
            imagesavealpha($this->destinationResource, true);
            imageantialias($this->destinationResource, true);
            imagefill($this->destinationResource, 0, 0, imagecolorallocatealpha(
                $this->destinationResource, 255, 255, 255, 127 // Tranparent.
            ));
        }

        $ok =@ imagecopyresampled($this->destinationResource, $this->sourceResource, 0, 0, $x, $y,
            $width, $height, $width, $height);
        if (!$ok) {
            throw new UploadException('Failed resampling destination resource [error: %s]', ['@error']);
        }

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

        $ok =@ $this->outputTo($destination);
        if (!$ok) {
            throw new UploadException('Cannot save file [error: %s]', ['@error']);
        }

        return $destination;
    }

    /**
     * @inheritDoc froq\file\upload\AbstractUploader
     */
    public function saveAs(string $name, string $nameAppendix = null,
        bool $useNewDimensionsAsNameAppendix = false): string
    {
        if ($name == '') {
            throw new UploadException('Name must not be empty');
        }

        if ($useNewDimensionsAsNameAppendix) {
            $newDimensions = $this->getNewDimensions();
            $nameAppendix = ($nameAppendix == null)
                ? vsprintf('%dx%d', $newDimensions)
                : vsprintf('%dx%d-%s', array_merge($newDimensions, [$nameAppendix]));
        }

        $destination = $this->getDestination($name, $nameAppendix);

        $ok =@ $this->outputTo($destination);
        if (!$ok) {
            throw new UploadException('Cannot save file [error: %s]', ['@error']);
        }

        return $destination;
    }

    /**
     * @inheritDoc froq\file\upload\AbstractUploader
     */
    public function move(): string
    {
        $source = $this->getSource();
        $destination = $this->getDestination();

        $ok =@ copy($source, $destination);
        if (!$ok) {
            throw new UploadException('Cannot move file [error: %s]', ['@error']);
        }

        unlink($source); // Remove source instantly.

        return $destination;
    }

    /**
     * @inheritDoc froq\file\upload\AbstractUploader
     */
    public function moveAs(string $name, string $nameAppendix = null): string
    {
        if ($name == '') {
            throw new UploadException('Name must not be empty');
        }

        $source = $this->getSource();
        $destination = $this->getDestination($name, $nameAppendix);

        $ok =@ copy($source, $destination);
        if (!$ok) {
            throw new UploadException('Cannot move file [error: %s]', ['@error']);
        }

        unlink($source); // Remove source instantly.

        return $destination;
    }

    /**
     * @inheritDoc froq\file\upload\AbstractUploader
     */
    public function clear(bool $force = false): void
    {
        if (!$force) {
            if ($this->options['clearSource']) {
                unlink($this->getSource());
            }

            if ($this->options['clear']) {
                is_resource($this->sourceResource) && imagedestroy($this->sourceResource);
                is_resource($this->destinationResource) && imagedestroy($this->destinationResource);

                $this->sourceResource = null;
                $this->destinationResource = null;
            }
        } else {
            unlink($this->getSource());

            is_resource($this->sourceResource) && imagedestroy($this->sourceResource);
            is_resource($this->destinationResource) && imagedestroy($this->destinationResource);

            $this->sourceResource = null;
            $this->destinationResource = null;
        }
    }

    /**
     * Display.
     * @return void
     */
    public function display(): void
    {
        $this->output();
    }

    /**
     * Get info.
     * @return array
     */
    public function getInfo(): array
    {
        if (empty($this->info)) {
            throw new UploadException('No info filled yet, try after calling fillInfo()');
        }

        return $this->info;
    }

    /**
     * Fill info.
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
            throw new UploadException('Failed to get source info [error: %s]', ['@error']);
        }

        // Add suggestive names.
        $info += ['type' => $info[2], 'width' => $info[0], 'height' => $info[1]];

        $this->info = $info;
    }

    /**
     * Get source resource.
     * @return ?resource
     */
    public function getSourceResource()
    {
        return $this->sourceResource;
    }

    /**
     * Get destination resource.
     * @return ?resource
     */
    public function getDestinationResource()
    {
        return $this->destinationResource;
    }

    /**
     * Get new dimensions.
     * @param  bool $format
     * @return array<int>|string|null
     */
    public function getNewDimensions(bool $format = false)
    {
        $newDimensions = $this->newDimensions ?? null;

        if ($newDimensions != null && $format) {
            $newDimensions = vsprintf('%dx%d', $newDimensions);
        }

        return $newDimensions;
    }

    /**
     * To base 64.
     * @return string
     * @since  4.0
     */
    public function toBase64(): string
    {
        return base64_encode($this->toString());
    }

    /**
     * To base 64 url.
     * @return string
     * @since  4.0
     */
    public function toBase64Url(): string
    {
        $base64 = base64_encode($this->toString());

        return 'data:'. $this->info['mime'] .';base64,'. $base64;
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
     * Create source resource.
     * @return ?resource
     * @throws froq\file\upload\UploadException
     */
    private function createSourceResource()
    {
        if ($this->resized) {
            // Use resized image as source.
            $sourceResource = imagecreatefromstring($this->toString());

            is_resource($this->sourceResource) && imagedestroy($this->sourceResource);
            is_resource($this->destinationResource) && imagedestroy($this->destinationResource);

            return $sourceResource;
        }

        $type = $this->getInfo()['type'];
        if (!in_array($type, self::SUPPORTED_TYPES)) {
            throw new UploadException('Unsupported image type, only "jpeg, png, gif, webp" are accepted');
        }

        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($this->getSource());
            case IMAGETYPE_PNG:
                return imagecreatefrompng($this->getSource());
            case IMAGETYPE_GIF:
                return imagecreatefromgif($this->getSource());
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($this->getSource());
        }

        return null;
    }

    /**
     * Output.
     * @return ?bool
     * @throws froq\file\upload\UploadException
     */
    private function output(): ?bool
    {
        $destinationResource = $this->getDestinationResource();
        if ($destinationResource == null) {
            throw new UploadException('No destination resource created yet, call one of these method '.
                'first: resample(), resize(), crop() or cropBy()');
        }

        $type = $this->getInfo()['type'];
        if (!in_array($type, self::SUPPORTED_TYPES)) {
            throw new UploadException('Unsupported image type, only "jpeg, png, gif, webp" are accepted');
        }

        switch ($type) {
            case IMAGETYPE_JPEG:
                $jpegQuality = intval($this->options['jpegQuality'] ?? self::QUALITY);
                return imagejpeg($destinationResource, null, $jpegQuality);
            case IMAGETYPE_PNG:
                return imagepng($destinationResource);
            case IMAGETYPE_GIF:
                return imagegif($destinationResource);
            case IMAGETYPE_WEBP:
                $webpQuality = intval($this->options['webpQuality'] ?? self::QUALITY);
                return imagewebp($destinationResource, null, $webpQuality);
        }

        return null;
    }

    /**
     * Output to.
     * @param  string $to
     * @return ?bool
     * @throws froq\file\upload\UploadException
     */
    private function outputTo(string $to): ?bool
    {
        $destinationResource = $this->getDestinationResource();
        if ($destinationResource == null) {
            throw new UploadException('No destination resource created yet, call one of these method '.
                'first: resample(), resize(), crop() or cropBy()');
        }

        $type = $this->getInfo()['type'];
        if (!in_array($type, self::SUPPORTED_TYPES)) {
            throw new UploadException('Unsupported image type, only "jpeg, png, gif, webp" are accepted');
        }

        switch ($type) {
            case IMAGETYPE_JPEG:
                $jpegQuality = intval($this->options['jpegQuality'] ?? self::QUALITY);
                return imagejpeg($destinationResource, $to, $jpegQuality);
            case IMAGETYPE_PNG:
                return imagepng($destinationResource, $to);
            case IMAGETYPE_GIF:
                return imagegif($destinationResource, $to);
            case IMAGETYPE_WEBP:
                $webpQuality = intval($this->options['webpQuality'] ?? self::QUALITY);
                return imagewebp($destinationResource, $to, $webpQuality);
        }

        return null;
    }
}
