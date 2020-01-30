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

use froq\file\{AbstractFile, FileError, FileException, Mime, MimeException, Util as FileUtil};
use Throwable;

/**
 * File.
 * @package froq\file
 * @object  froq\file\File
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   1.0
 */
class File extends AbstractFile
{
    /**
     * Constructor.
     * @param  array      $file
     * @param  string     $directory
     * @param  array|null $options
     * @throws froq\file\FileException
     */
    public function __construct(array $file, string $directory, array $options = null)
    {
        @ ['type' => $type, 'name'  => $name, 'tmp_name' => $source,
           'size' => $size, 'error' => $error] = $file + [
           'type' => null, 'name' => null, 'tmp_name' => null, 'size' => null, 'error' => null];

        // All these stuff are needed.
        if (!$type || !$name || !$source) {
            throw new FileException(
                'No valid file given, "type", "name" and "tmp_name" are required',
                [], FileError::NO_VALID_FILE
            );
        }

        $error = $error ? FileError::all()[$error] ?? 'Unknown' : null;
        if ($error) {
            throw new FileException($error, [], FileError::INTERNAL);
        }

        if (!is_file($source)) {
            throw new FileException(
                'No valid source file "%s" found by tmp_name',
                [$source], FileError::NO_VALID_SOURCE
            );
        }

        $size = $size ?? filesize($source);

        $options = array_merge($this->options, $options ?? []);
        extract($options, EXTR_PREFIX_ALL, 'options');

        $maxFileSize = FileUtil::convertBytes((string) $options_maxFileSize);
        if ($maxFileSize && $size > $maxFileSize) {
            throw new FileException(
                'File size exceeded, $options.maxFileSize is %s (%s bytes)',
                [$options_maxFileSize, $maxFileSize], FileError::OPTION_SIZE_EXCEEDED
            );
        }

        // Type & extension security.
        if (!$options_allowedTypes || !$options_allowedExtensions) {
            throw new FileException(
                '"allowedTypes" and "allowedExtensions" options cannot be empty for security '.
                'reasons, please provide types and extensions you allow (ie: for types '.
                '"image/jpeg,image/png" and for extensions "jpg,jpeg", or "*" to allow all)',
                [], FileError::OPTION_EMPTY
            );
        }

        $extension = pathinfo($name, PATHINFO_EXTENSION);
        if (!$extension && $options_allowEmptyExtensions === false) {
            throw new FileException(
                'Empty extensions not allowed by options',
                [], FileError::OPTION_EMPTY_EXTENSION
            );
        }

        // Type @override.
        try {
            $type = Mime::getType($source);
        } catch (MimeException $e) {
            throw new FileException($e);
        }

        if ($options_allowedTypes !== '*'
            && !in_array($type, explode(',', (string) $options_allowedTypes))) {
            throw new FileException(
                'Type "%s" not allowed by options, allowed types: "%s"'.
                [$type, $options_allowedTypes], FileError::OPTION_NOT_ALLOWED_TYPE
            );
        }

        $extension = $extension ?: Mime::getExtensionByType($type);
        if ($extension !== '' && $options_allowedExtensions !== '*'
            && !in_array($extension, explode(',', (string) $options_allowedExtensions))) {
            throw new FileException(
                'Extension "%s" not allowed by options, allowed extensions: "%s"',
                [$extension, $options_allowedExtensions], FileError::OPTION_NOT_ALLOWED_EXTENSION
            );
        }

        $directory = trim($directory);
        if (!$directory) {
            throw new FileException(
                'Directory cannot be empty', [], FileError::DIRECTORY_EMPTY
            );
        }

        if (!is_dir($directory)) {
            $ok =@ mkdir($directory, 0644, true);
            if (!$ok) {
                throw new FileException(
                    'Cannot make directory, error[%s]', ['@error'], FileError::DIRECTORY_ERROR
                );
            }
        }

        $this->name      = $this->prepareName($name);
        $this->type      = $type;
        $this->size      = (int) $size;
        $this->extension = (string) $extension;
        $this->directory = $directory;
        $this->source    = $source;
        $this->options   = $options;
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        try {
            $this->clear();
        } catch (Throwable $e) {}
    }
}
