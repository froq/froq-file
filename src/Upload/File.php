<?php
/**
 * Copyright (c) 2015 Kerem Güneş
 *
 * MIT License <https://opensource.org/licenses/mit>
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

namespace Froq\File\Upload;

use Froq\File\{File as FileBase, FileException};

/**
 * @package    Froq
 * @subpackage Froq\File
 * @object     Froq\File\Upload\File
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class File extends FileBase
{
    /**
     * @inheritDoc Froq\File\File
     */
    public function save(): bool
    {
        $sourceFile = $this->getSourceFile();
        if ($sourceFile == null) {
            throw new FileException('No source file yet!');
        }

        $targetFile = $this->getTargetFile();
        if ($targetFile == null) {
            throw new FileException('No target file yet!');
        }

        return copy($sourceFile, $targetFile);
    }

    /**
     * @inheritDoc Froq\File\File
     */
    public function saveAs(string $name): bool
    {
        $sourceFile = $this->getSourceFile();
        if ($sourceFile == null) {
            throw new FileException('No source file yet!');
        }

        return copy($sourceFile, "{$this->directory}/{$name}.{$this->extension}");
    }

    /**
     * @inheritDoc Froq\File\File
     */
    public function move(): bool
    {
        $sourceFile = $this->getSourceFile();
        if ($sourceFile == null) {
            throw new FileException('No source file yet!');
        }

        $targetFile = $this->getTargetFile();
        if ($targetFile == null) {
            throw new FileException('No target file yet!');
        }

        return move_uploaded_file($sourceFile, $targetFile);
    }

    /**
     * @inheritDoc Froq\File\File
     */
    public function moveAs(string $name): bool
    {
        $sourceFile = $this->getSourceFile();
        if ($sourceFile == null) {
            throw new FileException('No source file yet!');
        }

        return move_uploaded_file($sourceFile, "{$this->directory}/{$name}.{$this->extension}");
    }

    /**
     * @inheritDoc Froq\File\File
     */
    public function clear(): void
    {
        $sourceFile = $this->getSourceFile();
        if ($sourceFile != null) {
            @unlink($sourceFile);
        }
    }
}
