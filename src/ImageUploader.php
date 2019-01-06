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

namespace Froq\File;

/**
 * @package    Froq
 * @subpackage Froq\File
 * @object     Froq\File\ImageUploader
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      3.0
 */
final class ImageUploader extends File implements FileInterface
{
    /**
     * Jpeg quality.
     * @const int
     */
    public const JPEG_QUALITY = 80;

    /**
     * Info.
     * @var array
     */
    private $info;

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
     * Resize.
     * @param  int|null $width
     * @param  int|null $height
     * @param  bool     $proportional
     * @return bool
     * @throws Froq\File\FileException
     */
    public function resize(int $width = null, int $height = null, bool $proportional = true): bool
    {
        // ensure file info
        $this->fillInfo();

        $this->resourceFile = $this->createResourceFile();
        if ($this->resourceFile == null) {
            throw new FileException('Could not create resource file');
        }

        $newWidth = $newHeight = 0;
        [$origWidth, $origHeight] = $this->info;

        if ($proportional) {
            if ($width == 0)      $factor = $height / $origHeight;
            elseif ($height == 0) $factor = $width / $origWidth;
            else                  $factor = min($width / $origWidth, $height / $origHeight);

            $newWidth = (int) round($origWidth * $factor);
            $newHeight = (int) round($origHeight * $factor);
        } else {
            $newWidth = (int) (($width <= 0) ? $origWidth : $width);
            $newHeight = (int) (($height <= 0) ? $origHeight : $height);
        }

        $this->destinationFile = imagecreatetruecolor($newWidth, $newHeight);

        // handle png's
        if ($this->info[2] == IMAGETYPE_PNG) {
            imagealphablending($this->destinationFile, false);
            $transparent = imagecolorallocatealpha($this->destinationFile, 0, 0, 0, 127);
            imagefill($this->destinationFile, 0, 0, $transparent);
            imagesavealpha($this->destinationFile, true);
        }

        return imagecopyresampled($this->destinationFile, $this->resourceFile, 0, 0, 0, 0, $newWidth, $newHeight,
            $origWidth, $origHeight);
    }

    /**
     * Crop.
     * @param  int  $width
     * @param  int  $height
     * @param  bool $proportional
     * @return bool
     * @throws Froq\File\FileException
     */
    public function crop(int $width, int $height, bool $proportional = true): bool
    {
        // ensure file info
        $this->fillInfo();

        // do not crop original width/height dims
        if ($width == $this->info[0] && $height == $this->info[1]) {
            return $this->resize($width, $height);
        }

        $this->resourceFile = $this->createResourceFile();
        if ($this->resourceFile == null) {
            throw new FileException('Could not create resource file');
        }

        $origWidth = $this->info[0];
        $origHeight = $this->info[1];
        if ($proportional) {
            $size = ($origWidth > $origHeight) ? $origWidth : $origHeight;
            $percent = .5;
            $cropWidth = (int) ($size * $percent);
            $cropHeight = (int) ($size * $percent);
        } else {
            $cropWidth = $width;
            $cropHeight = $height;
        }
        $x = (int) (($origWidth - $cropWidth) / 2);
        $y = (int) (($origHeight - $cropHeight) / 2);

        $this->destinationFile = imagecreatetruecolor($width, $height);

        // handle png's
        if ($this->info[2] == IMAGETYPE_PNG) {
            imagealphablending($this->destinationFile, false);
            $transparent = imagecolorallocatealpha($this->destinationFile, 0, 0, 0, 127);
            imagefill($this->destinationFile, 0, 0, $transparent);
            imagesavealpha($this->destinationFile, true);
        }

        return imagecopyresampled($this->destinationFile, $this->resourceFile, 0, 0, $x, $y, $width, $height,
            $cropWidth, $cropHeight);
    }

    /**
     * Crop by.
     * @param  int  $width
     * @param  int  $height
     * @param  int  $x
     * @param  int  $y
     * @param  bool $proportional
     * @return bool
     * @throws Froq\File\FileException
     */
    public function cropBy(int $width, int $height, int $x, int $y, bool $proportional = true): bool
    {
        // ensure file info
        $this->fillInfo();

        // do not crop original width/height dims
        if ($width == $this->info[0] && $height == $this->info[1]) {
            return $this->resize($width, $height);
        }

        $this->resourceFile = $this->createResourceFile();
        if ($this->resourceFile == null) {
            throw new FileException('Could not create resource file');
        }

        $origWidth = $this->info[0];
        $origHeight = $this->info[1];
        if ($proportional) {
            $size = ($origWidth > $origHeight) ? $origWidth : $origHeight;
            $percent = .5;
            $cropWidth = (int) ($size * $percent);
            $cropHeight = (int) ($size * $percent);
        } else {
            $cropWidth = $width;
            $cropHeight = $height;
        }

        $this->destinationFile = imagecreatetruecolor($width, $height);

        // handle png's
        if ($this->info[2] == IMAGETYPE_PNG) {
            imagealphablending($this->destinationFile, false);
            $transparent = imagecolorallocatealpha($this->destinationFile, 0, 0, 0, 127);
            imagefill($this->destinationFile, 0, 0, $transparent);
            imagesavealpha($this->destinationFile, true);
        }

        return imagecopyresampled($this->destinationFile, $this->resourceFile, 0, 0, $x, $y, $width, $height,
            $cropWidth, $cropHeight);
    }

    /**
     * @inheritDoc Froq\File\File
     */
    public function save(): void
    {
        $ok = $this->outputTo($this->getDestinationPath());
        if (!$ok) {
            throw new FileException(error_get_last()['message'] ?? 'Unknown error');
        }
    }

    /**
     * @inheritDoc Froq\File\File
     */
    public function saveAs(string $name = null): void
    {
        $name = $name ?? $this->getNewName();
        if ($name == null) {
            throw new FileException('New name cannot be empty');
        }

        $ok = $this->outputTo($this->getDestinationPath($name));
        if (!$ok) {
            throw new FileException(error_get_last()['message'] ?? 'Unknown error');
        }
    }

    /**
     * @inheritDoc Froq\File\File
     */
    public function move(): void
    {
        @ $ok = move_uploaded_file($this->getSourcePath(), $this->getDestinationPath());
        if (!$ok) {
            throw new FileException(error_get_last()['message'] ?? 'Unknown error');
        }
    }

    /**
     * @inheritDoc Froq\File\File
     */
    public function moveAs(string $name = null): void
    {
        $name = $name ?? $this->getNewName();
        if ($name == null) {
            throw new FileException('New name cannot be empty');
        }

        @ $ok = move_uploaded_file($this->getSourcePath(), $this->getDestinationPath($name));
        if (!$ok) {
            throw new FileException(error_get_last()['message'] ?? 'Unknown error');
        }
    }

    /**
     * @inheritDoc Froq\File\File
     */
    public function clear(): void
    {
        is_resource($this->resourceFile) && imagedestroy($this->resourceFile);
        is_resource($this->destinationFile) && imagedestroy($this->destinationFile);

        $this->destinationFile = null;
        $this->resourceFile = null;

        @ unlink($this->getSourcePath());
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
        return $this->info;
    }

    /**
     * Fill info.
     * @return void
     * @throws Froq\File\FileException
     */
    public function fillInfo(): void
    {
        if ($this->info == null) {
            @ $this->info = getimagesize($this->getSourcePath());
            if (!isset($this->info[0], $this->info[1])) {
                throw new FileException('Could not get file info');
            }
        }
    }

    /**
     * Create resource file.
     * @return resource|null
     */
    private function createResourceFile()
    {
        if (!empty($this->info[2])) {
            switch ($this->info[2]) {
                case IMAGETYPE_JPEG:
                    return imagecreatefromjpeg($this->getSourcePath());
                case IMAGETYPE_PNG:
                    return imagecreatefrompng($this->getSourcePath());
                case IMAGETYPE_GIF:
                    return imagecreatefromgif($this->getSourcePath());
            }
        }
        return null;
    }

    /**
     * Output.
     * @return ?bool
     * @throws Froq\File\FileException
     */
    private function output(): ?bool
    {
        if (!empty($this->info[2]) && !empty($this->destinationFile)) {
            switch ($this->info[2]) {
                case IMAGETYPE_JPEG:
                    return imagejpeg($this->destinationFile, null,
                        $this->options['jpegQuality'] ?? self::JPEG_QUALITY);
                case IMAGETYPE_PNG:
                    return imagepng($this->destinationFile);
                case IMAGETYPE_GIF:
                    return imagegif($this->destinationFile);
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
        if (!empty($this->info[2]) && !empty($this->destinationFile)) {
            switch ($this->info[2]) {
                case IMAGETYPE_JPEG:
                    return imagejpeg($this->destinationFile, $to,
                        $this->options['jpegQuality'] ?? self::JPEG_QUALITY);
                case IMAGETYPE_PNG:
                    return imagepng($this->destinationFile, $to);
                case IMAGETYPE_GIF:
                    return imagegif($this->destinationFile, $to);
            }
        }
        return null;
    }
}
