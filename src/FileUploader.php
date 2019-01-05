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

namespace Froq\File\Upload;

use Froq\File\{File as FileBase, FileInterface, FileException};

/**
 * @package    Froq
 * @subpackage Froq\File
 * @object     Froq\File\Upload\File
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class File extends FileBase implements FileInterface
{
    /**
     * @inheritDoc Froq\File\FileInterface
     */
    public function save(): void
    {
        @ $ok = copy($this->getSourcePath(), $this->getDestinationPath());
        if (!$ok) {
            throw new FileException(error_get_last()['message'] ?? 'Unknown error');
        }
    }

    /**
     * @inheritDoc Froq\File\FileInterface
     */
    public function saveAs(string $name): void
    {
        @ $ok = copy($this->getSourcePath(), $this->getDestinationPath($name));
        if (!$ok) {
            throw new FileException(error_get_last()['message'] ?? 'Unknown error');
        }
    }

    /**
     * @inheritDoc Froq\File\FileInterface
     */
    public function move(): void
    {
        @ $ok = move_uploaded_file($this->getSourcePath(), $this->getDestinationPath());
        if (!$ok) {
            throw new FileException(error_get_last()['message'] ?? 'Unknown error');
        }
    }

    /**
     * @inheritDoc Froq\File\FileInterface
     */
    public function moveAs(string $name): void
    {
        @ $ok = move_uploaded_file($this->getSourcePath(), $this->getDestinationPath($name));
        if (!$ok) {
            throw new FileException(error_get_last()['message'] ?? 'Unknown error');
        }
    }

    /**
     * @inheritDoc Froq\File\FileInterface
     */
    public function clear(): void
    {
        @ unlink($this->getSourcePath());
    }
}