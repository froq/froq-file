<?php
/**
 * Copyright (c) 2016 Kerem Güneş
 *    <k-gun@mail.com>
 *
 * GNU General Public License v3.0
 *    <http://www.gnu.org/licenses/gpl-3.0.txt>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Froq\File\Upload;

use Froq\File\{File as FileBase, FileException};

/**
 * @package    Froq
 * @subpackage Froq\File
 * @object     Froq\File\Upload\Image
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Image extends FileBase
{
    /**
     * Info.
     * @var array
     */
    private $info;

    /**
     * Src file.
     * @var resource
     */
    private $srcFile;

    /**
     * Dst file.
     * @var resource
     */
    private $dstFile;

    /**
     * Jpeg quality.
     * @var int
     */
    private $jpegQuality = 80;

    /**
     * Resize.
     * @param  int|null $width
     * @param  int|null $height
     * @param  bool     $proportional
     * @return bool
     */
    public function resize(int $width = null, int $height = null, bool $proportional = true): bool
    {
        // ensure file info
        $this->fillInfo();

        $newWidth = $newHeight = 0;
        [$origWidth, $origHeight] = $this->info;

        if ($proportional) {
            if ($width == 0) {
                $factor = $height / $origHeight;
            } elseif ($height == 0) {
                $factor = $width / $origWidth;
            } else {
                $factor = min($width / $origWidth, $height / $origHeight);
            }
            $newWidth = (int) round($origWidth * $factor);
            $newHeight = (int) round($origHeight * $factor);
        } else {
            $newWidth = (int) (($width <= 0) ? $origWidth : $width);
            $newHeight = (int) (($height <= 0) ? $origHeight : $height);
        }

        $this->srcFile = $this->createImageFile();
        if ($this->srcFile == null) {
            return false;
        }

        $this->dstFile = imagecreatetruecolor($newWidth, $newHeight);

        // handle png's
        if ($this->info[2] == IMAGETYPE_PNG) {
            imagealphablending($this->dstFile, false);
            $transparent = imagecolorallocatealpha($this->dstFile, 0, 0, 0, 127);
            imagefill($this->dstFile, 0, 0, $transparent);
            imagesavealpha($this->dstFile, true);
        }

        return imagecopyresampled($this->dstFile, $this->srcFile, 0, 0, 0, 0, $newWidth, $newHeight,
            $origWidth, $origHeight);
    }

    /**
     * Crop.
     * @param  int  $width
     * @param  int  $height
     * @param  bool $proportional
     * @return bool
     */
    public function crop(int $width, int $height, bool $proportional = true): bool
    {
        // ensure file info
        $this->fillInfo();

        // do not crop original width/height dims
        if ($width == $this->info[0] && $height == $this->info[1]) {
            return $this->resize($width, $height);
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

        $this->srcFile = $this->createImageFile();
        if ($this->srcFile == null) {
            return false;
        }

        $this->dstFile = imagecreatetruecolor($width, $height);

        // handle png's
        if ($this->info[2] == IMAGETYPE_PNG) {
            imagealphablending($this->dstFile, false);
            $transparent = imagecolorallocatealpha($this->dstFile, 0, 0, 0, 127);
            imagefill($this->dstFile, 0, 0, $transparent);
            imagesavealpha($this->dstFile, true);
        }

        return imagecopyresampled($this->dstFile, $this->srcFile, 0, 0, $x, $y, $width, $height,
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
     */
    public function cropBy(int $width, int $height, int $x, int $y, bool $proportional = true): bool
    {
        // ensure file info
        $this->fillInfo();

        // do not crop original width/height dims
        if ($width == $this->info[0] && $height == $this->info[1]) {
            return $this->resize($width, $height);
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

        $this->srcFile = $this->createImageFile();
        if ($this->srcFile == null) {
            return false;
        }

        $this->dstFile = imagecreatetruecolor($width, $height);

        // handle png's
        if ($this->info[2] == IMAGETYPE_PNG) {
            imagealphablending($this->dstFile, false);
            $transparent = imagecolorallocatealpha($this->dstFile, 0, 0, 0, 127);
            imagefill($this->dstFile, 0, 0, $transparent);
            imagesavealpha($this->dstFile, true);
        }

        return imagecopyresampled($this->dstFile, $this->srcFile, 0, 0, $x, $y, $width, $height,
            $cropWidth, $cropHeight);
    }

    /**
     * @inheritDoc Froq\File\File
     */
    public function save(): bool
    {
        return (bool) $this->outputFile($this->getTargetFile());
    }

    /**
     * @inheritDoc Froq\File\File
     */
    public function saveAs(string $name): bool
    {
        return (bool) $this->outputFile("{$this->directory}/{$name}.{$this->extension}");
    }

    /**
     * @inheritDoc Froq\File\File
     */
    public function move(): bool
    {
        return move_uploaded_file($this->getSourceFile(), $this->getTargetFile());
    }

    /**
     * @inheritDoc Froq\File\File
     */
    public function moveAs(string $name): bool
    {
        return move_uploaded_file($this->getSourceFile(), "{$this->directory}/{$name}.{$this->extension}");
    }

    /**
     * @inheritDoc Froq\File\File
     */
    public function clear(): void
    {
        if (is_resource($this->srcFile)) {
            imagedestroy($this->srcFile);
        }
        if (is_resource($this->dstFile)) {
            imagedestroy($this->dstFile);
        }

        @unlink($this->getSourceFile());

        $this->dstFile = null;
        $this->srcFile = null;
    }

    /**
     * Display.
     * @return bool
     */
    public function display(): bool
    {
        return (bool) $this->output($this->getTargetFile());
    }

    /**
     * Set jpeg quality.
     * @param  int $jpegQuality
     * @return self
     */
    public function setJpegQuality(int $jpegQuality): self
    {
        $this->jpegQuality = $jpegQuality;

        return $this;
    }

    /**
     * Get jpeg quality.
     * @return int
     */
    public function getJpegQuality(): int
    {
        return $this->jpegQuality;
    }

    /**
     * Fill info.
     * @return void
     * @throws Froq\File\FileException
     */
    public function fillInfo(): void
    {
        if ($this->nameTmp == null) {
            throw new FileException('tmp_name is empty yet!');
        }

        if ($this->info == null) {
            $this->info =@ getimagesize($this->nameTmp);
        }

        if (!isset($this->info[0], $this->info[1])) {
            throw new FileException('Could not get file info!');
        }
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
     * Create image file.
     * @return resource|null
     */
    private function createImageFile()
    {
        if ($this->nameTmp) {
            switch ($this->info[2]) {
                case IMAGETYPE_JPEG:
                    return imagecreatefromjpeg($this->nameTmp);
                case IMAGETYPE_PNG:
                    return imagecreatefrompng($this->nameTmp);
                case IMAGETYPE_GIF:
                    return imagecreatefromgif($this->nameTmp);
            }
        }
    }

    /**
     * Output.
     * @return ?bool
     */
    private function output(): ?bool
    {
        if (!empty($this->dstFile) && !empty($this->directory)) {
            switch ($this->info[2]) {
                case IMAGETYPE_JPEG:
                    return imagejpeg($this->dstFile, null, $this->jpegQuality);
                case IMAGETYPE_PNG:
                    return imagepng($this->dstFile);
                case IMAGETYPE_GIF:
                    return imagegif($this->dstFile);
            }
        }
    }

    /**
     * Output file.
     * @param  string $file
     * @return ?bool
     */
    private function outputFile(string $file): ?bool
    {
        if (!empty($this->dstFile) && !empty($this->directory)) {
            switch ($this->info[2]) {
                case IMAGETYPE_JPEG:
                    return imagejpeg($this->dstFile, $file, $this->jpegQuality);
                case IMAGETYPE_PNG:
                    return imagepng($this->dstFile, $file);
                case IMAGETYPE_GIF:
                    return imagegif($this->dstFile, $file);
            }
        }
    }
}
