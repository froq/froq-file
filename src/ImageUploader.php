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

/**
 * Image Uploader.
 * @package froq\file
 * @object  froq\file\ImageUploader
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0
 */
final class ImageUploader extends File implements FileInterface
{
    /**
     * Supported types.
     * @const array
     */
    public const SUPPORTED_TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];

    /**
     * Jpeg quality.
     * @const int
     */
    public const JPEG_QUALITY = -1; // Use default quality.

    /**
     * Info.
     * @var array
     */
    private array $info;

    /**
     * Resource file.
     * @var resource
     */
    private $resourceFile;

    /**
     * Destination file.
     * @var resource
     */
    private $destinationFile;

    /**
     * New dimensions.
     * @var array<int, int>
     */
    private array $newDimensions;

    /**
     * Resample.
     * @return self
     * @throws froq\file\FileException
     */
    public function resample(): self
    {
        return $this->resize(0, 0, false);
    }

    /**
     * Resize.
     * @param  int  $width
     * @param  int  $height
     * @param  bool $proportional
     * @param  bool $fixExcessiveDimensions
     * @return self
     * @throws froq\file\FileException
     */
    public function resize(int $width, int $height, bool $proportional = true, bool $fixExcessiveDimensions = true): self
    {
        // Fill/ensure info.
        $this->fillInfo();

        $info = $this->getInfo();

        @ $this->resourceFile = $this->createResourceFile();
        if (!$this->resourceFile) {
            throw new FileException('Could not create resource file, error[%s]', ['@error']);
        }

        [$origWidth, $origHeight] = $info;

        if ($fixExcessiveDimensions) {
            if ($width > $origWidth) $width = $origWidth;
            if ($height > $origHeight) $height = $origHeight;
        }

        $newWidth = $newHeight = 0;
        if ($proportional) {
            if ($width == 0) $factor = $height / $origHeight;
            elseif ($height == 0) $factor = $width / $origWidth;
            else $factor = min($width / $origWidth, $height / $origHeight);

            $newWidth = (int) ($origWidth * $factor);
            $newHeight = (int) ($origHeight * $factor);
        } else {
            $newWidth = (int) ($width > 0 ? $width : $origWidth);
            $newHeight = (int) ($height > 0 ? $height : $origHeight);
        }

        @ $this->destinationFile = imagecreatetruecolor($newWidth, $newHeight);
        if (!$this->destinationFile) {
            throw new FileException('Could not create destination file, error[%s]', ['@error']);
        }

        // Handle PNGs.
        if ($info['type'] == IMAGETYPE_PNG) {
            imagealphablending($this->destinationFile, false);
            $transparent = imagecolorallocatealpha($this->destinationFile, 0, 0, 0, 127);
            imagefill($this->destinationFile, 0, 0, $transparent);
            imagesavealpha($this->destinationFile, true);
        }

        @ $ok = imagecopyresampled($this->destinationFile, $this->resourceFile, 0, 0, 0, 0,
            $newWidth, $newHeight, $origWidth, $origHeight);
        if (!$ok) {
            throw new FileException('Could not resample file, error[%s]', ['@error']);
        }

        // Store new dimensions.
        $this->newDimensions = [$newWidth, $newHeight];

        return $this;
    }

    /**
     * Crop.
     * @param  int                  $width
     * @param  int                  $height
     * @param  bool                 $proportional
     * @param  array<int, int>|null $xy @internal
     * @return self
     * @throws froq\file\FileException
     */
    public function crop(int $width, int $height, bool $proportional = true, array $xy = null): self
    {
        // Fill/ensure info.
        $this->fillInfo();

        $info = $this->getInfo();

        // Do not crop original width/height dimensions.
        if ($width == $info['width'] && $height == $info['height']) {
            return $this->resize($width, $height);
        }

        @ $this->resourceFile = $this->createResourceFile();
        if (!$this->resourceFile) {
            throw new FileException('Could not create resource file, error[%s]', ['@error']);
        }

        [$origWidth, $origHeight] = $info;

        if ($proportional) {
            $size = ($origWidth > $origHeight) ? $origWidth : $origHeight;
            $percent = 0.5;
            $cropWidth = (int) ($size * $percent);
            $cropHeight = (int) ($size * $percent);
        } else {
            $cropWidth = $width;
            $cropHeight = $height;
        }

        @ [$x, $y] = $xy;
        $x = $x ?? (int) (($origWidth - $cropWidth) / 2);
        $y = $y ?? (int) (($origHeight - $cropHeight) / 2);

        @ $this->destinationFile = imagecreatetruecolor($width, $height);
        if (!$this->destinationFile) {
            throw new FileException('Could not create destination file, error[%s]', ['@error']);
        }

        // Handle PNGs.
        if ($info['type'] == IMAGETYPE_PNG) {
            imagealphablending($this->destinationFile, false);
            $transparent = imagecolorallocatealpha($this->destinationFile, 0, 0, 0, 127);
            imagefill($this->destinationFile, 0, 0, $transparent);
            imagesavealpha($this->destinationFile, true);
        }

        @ $ok = imagecopyresampled($this->destinationFile, $this->resourceFile, 0, 0, $x, $y,
            $width, $height, $cropWidth, $cropHeight);
        if (!$ok) {
            throw new FileException('Could not resample file, error[%s]', ['@error']);
        }

        // Store new dimensions.
        $this->newDimensions = [$width, $height];

        return $this;
    }

    /**
     * Crop by.
     * @param  int  $width
     * @param  int  $height
     * @param  int  $x
     * @param  int  $y
     * @param  bool $proportional
     * @return bool
     * @throws froq\file\FileException
     */
    public function cropBy(int $width, int $height, int $x, int $y, bool $proportional = true): self
    {
        return $this->crop($width, $height, $proportional, [$x, $y]);
    }

    /**
     * @inheritDoc froq\file\FileInterface
     */
    public function save(): string
    {
        $resourceFile = $this->getResourceFile();
        $destinationFile = $this->getDestinationFile();

        if ($resourceFile == null || $destinationFile == null) {
            throw new FileException('No resource/destination file created yet, call one of these '.
                'method first: resample(), resize(), crop() or cropBy()');
        }

        $destination = $this->getDestination();

        @ $ok = $this->outputTo($destination);
        if (!$ok) {
            throw new FileException('Cannot save file, error[%s]', ['@error']);
        }

        return $destination;
    }

    /**
     * @inheritDoc froq\file\FileInterface
     */
    public function saveAs(string $name, string $nameAppendix = null, bool $useNewDimensionsAsNameAppendix = false): string
    {
        if ($name == '') {
            throw new FileException('Name cannot be empty');
        }

        $resourceFile = $this->getResourceFile();
        $destinationFile = $this->getDestinationFile();

        if ($resourceFile == null || $destinationFile == null) {
            throw new FileException('No resource/destination file created yet, call one of these '.
                'method first: resample(), resize(), crop() or cropBy()');
        }

        if ($useNewDimensionsAsNameAppendix) {
            $newDimensions = $this->getNewDimensions();
            $nameAppendix = ($nameAppendix == null)
                ? vsprintf('%dx%d', $newDimensions)
                : vsprintf('%dx%d-%s', array_merge($newDimensions, [$nameAppendix]));
        }

        $destination = $this->getDestination($name, $nameAppendix);

        @ $ok = $this->outputTo($destination);
        if (!$ok) {
            throw new FileException('Cannot save file, error[%s]', ['@error']);
        }

        return $destination;
    }

    /**
     * @inheritDoc froq\file\FileInterface
     */
    public function move(): string
    {
        $source = $this->getSource();
        $destination = $this->getDestination();

        @ $ok = copy($source, $destination);
        if (!$ok) {
            throw new FileException('Cannot move file, error[%s]', ['@error']);
        }

        // Remove source instantly.
        @ unlink($source);

        return $destination;
    }

    /**
     * @inheritDoc froq\file\FileInterface
     */
    public function moveAs(string $name, string $nameAppendix = null): string
    {
        if ($name == '') {
            throw new FileException('Name cannot be empty');
        }

        $source = $this->getSource();
        $destination = $this->getDestination($name, $nameAppendix);

        @ $ok = copy($source, $destination);
        if (!$ok) {
            throw new FileException('Cannot move file, error[%s]', ['@error']);
        }

        // Remove source instantly.
        @ unlink($source);

        return $destination;
    }

    /**
     * @inheritDoc froq\file\File
     */
    public function clear(): void
    {
        if ($this->options['clear']) {
            is_resource($this->resourceFile) && imagedestroy($this->resourceFile);
            is_resource($this->destinationFile) && imagedestroy($this->destinationFile);

            $this->resourceFile = null;
            $this->destinationFile = null;
        }

        if ($this->options['clearSource']) {
            @ unlink($this->getSource());
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
     * @return ?array
     */
    public function getInfo(): ?array
    {
        return $this->info ?? null;
    }

    /**
     * Fill info.
     * @return void
     * @throws froq\file\FileException
     * @internal
     */
    public function fillInfo(): void
    {
        if (empty($this->info)) {
            @ $info = getimagesize($this->getSource());
            if (!$info) {
                throw new FileException('Could not get file info, error[%s]', ['@error']);
            }

            // Add suggestive names..
            $info += ['width' => $info[0], 'height' => $info[1], 'type' => $info[2],
                'attributes' => $info[3]];

            $this->info = $info;
        }
    }

    /**
     * Get resource file.
     * @return ?resource
     */
    public function getResourceFile()
    {
        return $this->resourceFile;
    }

    /**
     * Get destination file.
     * @return ?resource
     */
    public function getDestinationFile()
    {
        return $this->destinationFile;
    }

    /**
     * Get new dimensions.
     * @param  bool $format
     * @return array<int, int>|string|null
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
     * Get output buffer.
     * @return string
     * @throws froq\file\FileException
     */
    public function getOutputBuffer(): string
    {
        $destinationFile = $this->getDestinationFile();

        if ($destinationFile == null) {
            throw new FileException('No destination file created yet, call one of these method '.
                'first: resample(), resize(), crop() or cropBy()');
        }

        ob_start();
        $this->output();
        return ob_get_clean();
    }

    /**
     * Create resource file.
     * @return ?resource
     * @throws froq\file\FileException
     */
    private function createResourceFile()
    {
        $type = $this->getInfo()['type'];

        if (!in_array($type, self::SUPPORTED_TYPES)) {
            throw new FileException('Unsupported image type');
        }

        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($this->getSource());
            case IMAGETYPE_PNG:
                return imagecreatefrompng($this->getSource());
            case IMAGETYPE_GIF:
                return imagecreatefromgif($this->getSource());
        }

        return null;
    }

    /**
     * Output.
     * @return ?bool
     */
    private function output(): ?bool
    {
        $type = $this->getInfo()['type'];
        $destinationFile = $this->getDestinationFile();

        if ($destinationFile != null) {
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $jpegQuality = intval($this->options['jpegQuality'] ?? self::JPEG_QUALITY);
                    return imagejpeg($destinationFile, null, $jpegQuality);
                case IMAGETYPE_PNG:
                    return imagepng($destinationFile);
                case IMAGETYPE_GIF:
                    return imagegif($destinationFile);
            }
        }

        return null;
    }

    /**
     * Output to.
     * @param  string $to
     * @return ?bool
     */
    private function outputTo(string $to): ?bool
    {
        $type = $this->getInfo()['type'];
        $destinationFile = $this->getDestinationFile();

        if ($destinationFile != null) {
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $jpegQuality = intval($this->options['jpegQuality'] ?? self::JPEG_QUALITY);
                    return imagejpeg($destinationFile, $to, $jpegQuality);
                case IMAGETYPE_PNG:
                    return imagepng($destinationFile, $to);
                case IMAGETYPE_GIF:
                    return imagegif($destinationFile, $to);
            }
        }

        return null;
    }
}
