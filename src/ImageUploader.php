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

use froq\common\interfaces\Stringable;
use froq\file\{AbstractUploader, UploaderException};

/**
 * Image Uploader.
 * @package froq\file
 * @object  froq\file\ImageUploader
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0
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
     * @param  bool $fixExcessiveDimensions
     * @return self
     * @throws froq\file\UploaderException
     */
    public function resize(int $width, int $height, bool $proportional = true, bool $fixExcessiveDimensions = true): self
    {
        // Fill/ensure info.
        $this->fillInfo();

        $this->sourceResource =@ $this->createSourceResource();
        if (!$this->sourceResource) {
            throw new UploaderException('Failed creating source resource [error: %s]', ['@error']);
        }

        [$origWidth, $origHeight] = $info = $this->getInfo();

        // Use original width/height if given ones excessive.
        if ($fixExcessiveDimensions) {
            if ($width > $origWidth)   $width  = $origWidth;
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
            throw new UploaderException('Failed creating destination resource [error: %s]', ['@error']);
        }

        // Handle PNG/GIFs.
        if (in_array($info['type'], [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP])) {
            imagealphablending($this->destinationResource, false);
            imagesavealpha($this->destinationResource, true);
            imageantialias($this->destinationResource, true);
            imagefill($this->destinationResource, 0, 0, imagecolorallocatealpha(
                $this->destinationResource, 0, 0, 0, 127 // Tranparent.
            ));
        }

        // Not using imagescale() cos images become dithered when width/height is small.
        $ok =@ imagecopyresampled($this->destinationResource, $this->sourceResource, 0, 0, 0, 0,
            $newWidth, $newHeight, $origWidth, $origHeight);
        if (!$ok) {
            throw new UploaderException('Failed resampling destination resource [error: %s]', ['@error']);
        }

        // Store new dimensions.
        $this->newDimensions = [$newWidth, $newHeight];

        // For chaining situations (eg: $up->resize(100, 150)->crop(75, 75)).
        $this->resized = true;

        return $this;
    }

    /**
     * Crop.
     * @param  int             $width
     * @param  int|null        $height
     * @param  bool            $proportional
     * @param  array<int>|null $xy @internal
     * @return self
     * @throws froq\file\UploaderException
     */
    public function crop(int $width, int $height = null, bool $proportional = true, array $xy = null): self
    {
        // Fill/ensure info.
        $this->fillInfo();

        $this->sourceResource =@ $this->createSourceResource();
        if (!$this->sourceResource) {
            throw new UploaderException('Failed creating source resource [error: %s]', ['@error']);
        }

        // Square crops.
        $height = $height ?? $width;

        [$origWidth, $origHeight] = $info = $this->getInfo();

        if ($proportional) {
            $factor     = ($width > $height) ? $width : $height;
            $cropWidth  = (int) (0.5 * $factor);
            $cropHeight = (int) (0.5 * $factor);
        } else {
            $cropWidth  = $width;
            $cropHeight = $height;
        }

        $x = $xy[0] ?? (int) (($origWidth - $cropWidth) / 2);
        $y = $xy[1] ?? (int) (($origHeight - $cropHeight) / 2);

        $this->destinationResource =@ imagecreatetruecolor($width, $height);
        if (!$this->destinationResource) {
            throw new UploaderException('Failed creating destination resource [error: %s]', ['@error']);
        }

        // Handle PNG/GIFs.
        if (in_array($info['type'], [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP])) {
            imagealphablending($this->destinationResource, false);
            imagesavealpha($this->destinationResource, true);
            imageantialias($this->destinationResource, true);
            imagefill($this->destinationResource, 0, 0, imagecolorallocatealpha(
                $this->destinationResource, 0, 0, 0, 127 // Tranparent.
            ));
        }

        $ok =@ imagecopyresampled($this->destinationResource, $this->sourceResource, 0, 0, $x, $y,
            $width, $height, $width, $height);
        if (!$ok) {
            throw new UploaderException('Failed resampling destination resource [error: %s]', ['@error']);
        }

        // Store new dimensions.
        $this->newDimensions = [$width, $height];

        return $this;
    }

    /**
     * Crop by.
     * @param  int      $width
     * @param  int|null $height
     * @param  int      $x
     * @param  int      $y
     * @param  bool     $proportional
     * @return self
     */
    public function cropBy(int $width, int $height = null, int $x, int $y, bool $proportional = true): self
    {
        return $this->crop($width, $height, $proportional, [$x, $y]);
    }

    /**
     * @inheritDoc froq\file\Uploader
     */
    public function save(): string
    {
        $destination = $this->getDestination();

        $ok =@ $this->outputTo($destination);
        if (!$ok) {
            throw new UploaderException('Cannot save file [error: %s]', ['@error']);
        }

        return $destination;
    }

    /**
     * @inheritDoc froq\file\Uploader
     */
    public function saveAs(string $name, string $nameAppendix = null, bool $useNewDimensionsAsNameAppendix = false): string
    {
        if ($name == '') {
            throw new UploaderException('Name cannot be empty');
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
            throw new UploaderException('Cannot save file [error: %s]', ['@error']);
        }

        return $destination;
    }

    /**
     * @inheritDoc froq\file\Uploader
     */
    public function move(): string
    {
        $source = $this->getSource();
        $destination = $this->getDestination();

        $ok =@ copy($source, $destination);
        if (!$ok) {
            throw new UploaderException('Cannot move file [error: %s]', ['@error']);
        }

        // Remove source instantly.
        @ unlink($source);

        return $destination;
    }

    /**
     * @inheritDoc froq\file\Uploader
     */
    public function moveAs(string $name, string $nameAppendix = null): string
    {
        if ($name == '') {
            throw new UploaderException('Name cannot be empty');
        }

        $source = $this->getSource();
        $destination = $this->getDestination($name, $nameAppendix);

        $ok =@ copy($source, $destination);
        if (!$ok) {
            throw new UploaderException('Cannot move file [error: %s]', ['@error']);
        }

        // Remove source instantly.
        @ unlink($source);

        return $destination;
    }

    /**
     * @inheritDoc froq\file\Uploader
     */
    public function clear(bool $force = false): void
    {
        if (!$force) {
            if ($this->options['clearSource']) {
                @ unlink($this->getSource());
            }

            if ($this->options['clear']) {
                is_resource($this->sourceResource) && imagedestroy($this->sourceResource);
                is_resource($this->destinationResource) && imagedestroy($this->destinationResource);

                $this->sourceResource = null;
                $this->destinationResource = null;
            }
        } else {
            @ unlink($this->getSource());

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
            throw new UploaderException('No info filled yet, try after calling fillInfo()');
        }
        return $this->info;
    }

    /**
     * Fill info.
     * @return void
     * @throws froq\file\UploaderException
     * @internal
     */
    public function fillInfo(): void
    {
        if ($this->resized) {
            // Use resized image as source.
            $info =@ getimagesizefromstring($this->toString());
        } elseif (!isset($this->info)) {
            $info =@ getimagesize($this->getSource());
        }

        if (empty($info)) {
            throw new UploaderException('Failed to get source info [error: %s]', ['@error']);
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
     * @throws froq\file\UploaderException
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
            throw new UploaderException('Unsupported image type, only "jpeg, png, gif, webp" are accepted');
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
     * @throws froq\file\UploaderException
     */
    private function output(): ?bool
    {
        $destinationResource = $this->getDestinationResource();
        if ($destinationResource == null) {
            throw new UploaderException('No destination resource created yet, call one of these method '.
                'first: resample(), resize(), crop() or cropBy()');
        }

        $type = $this->getInfo()['type'];
        if (!in_array($type, self::SUPPORTED_TYPES)) {
            throw new UploaderException('Unsupported image type, only "jpeg, png, gif, webp" are accepted');
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
     * @throws froq\file\UploaderException
     */
    private function outputTo(string $to): ?bool
    {
        $destinationResource = $this->getDestinationResource();
        if ($destinationResource == null) {
            throw new UploaderException('No destination resource created yet, call one of these method '.
                'first: resample(), resize(), crop() or cropBy()');
        }

        $type = $this->getInfo()['type'];
        if (!in_array($type, self::SUPPORTED_TYPES)) {
            throw new UploaderException('Unsupported image type, only "jpeg, png, gif, webp" are accepted');
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
