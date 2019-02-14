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
 * File interface.
 * @package froq\file
 * @object  froq\file\FileInterface
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   1.0
 */
interface FileInterface
{
    /**
     * Save.
     * @return string
     * @throws froq\file\FileException
     */
    public function save(): string;

    /**
     * Save as.
     * @param  string $name
     * @param  string $nameAppendix
     * @return string
     * @throws froq\file\FileException
     */
    public function saveAs(string $name, string $nameAppendix = ''): string;

    /**
     * Move.
     * @return string
     * @throws froq\file\FileException
     */
    public function move(): string;

    /**
     * Move as.
     * @param  string $name
     * @param  string $nameAppendix
     * @return string
     * @throws froq\file\FileException
     */
    public function moveAs(string $name, string $nameAppendix = ''): string;

    /**
     * Clear.
     * @return void
     */
    public function clear(): void;
}
