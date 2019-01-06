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
 * @object     Froq\File\FileUploader
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      3.0
 */
final class FileUploader extends File implements FileInterface
{
    /**
     * @inheritDoc Froq\File\FileInterface
     */
    public function save(): string
    {
        @ $ok = copy($this->getSourcePath(), $destinationPath = $this->getDestinationPath());
        if (!$ok) {
            throw new FileException(error_get_last()['message'] ?? 'Unknown error');
        }

        return $destinationPath;
    }

    /**
     * @inheritDoc Froq\File\FileInterface
     */
    public function saveAs(string $name = null): string
    {
        $name = $name ?? $this->getNewName();
        if ($name == null) {
            throw new FileException('New name cannot be empty');
        }

        @ $ok = copy($this->getSourcePath(), $destinationPath = $this->getDestinationPath($name));
        if (!$ok) {
            throw new FileException(error_get_last()['message'] ?? 'Unknown error');
        }

        return $destinationPath;
    }

    /**
     * @inheritDoc Froq\File\FileInterface
     */
    public function move(): string
    {
        @ $ok = move_uploaded_file($this->getSourcePath(), $destinationPath = $this->getDestinationPath());
        if (!$ok) {
            throw new FileException(error_get_last()['message'] ?? 'Unknown error');
        }

        return $destinationPath;
    }

    /**
     * @inheritDoc Froq\File\FileInterface
     */
    public function moveAs(string $name = null): string
    {
        $name = $name ?? $this->getNewName();
        if ($name == null) {
            throw new FileException('New name cannot be empty');
        }

        @ $ok = move_uploaded_file($this->getSourcePath(), $destinationPath = $this->getDestinationPath($name));
        if (!$ok) {
            throw new FileException(error_get_last()['message'] ?? 'Unknown error');
        }

        return $destinationPath;
    }

    /**
     * @inheritDoc Froq\File\FileInterface
     */
    public function clear(): void
    {
        @ unlink($this->getSourcePath());
    }
}
