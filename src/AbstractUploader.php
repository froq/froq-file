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

use froq\file\mime\{Mime, MimeException};
use froq\file\{UploaderError, UploaderException, Util as FileUtil};

/**
 * Abstract Uploader.
 * @package froq\file
 * @object  froq\file\AbstractUploader
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
abstract class AbstractUploader
{
    /**
     * Source.
     * @var string
     */
    protected string $source;

    /**
     * File info.
     * @var array
     */
    protected array $fileInfo;

    /**
     * Options.
     * @var array
     */
    protected array $options = [
        'hash'                 => null, // Available commands: 'rand', 'file' or 'fileName' (default=none).
        'hashLength'           => null, // 8, 16, 32 or 40 (default=32).
        'maxFileSize'          => null, // In binary mode: for 2 megabytes 2048, 2048k or 2m.
        'allowedTypes'         => null, // * means all allowed or 'image/jpeg,image/png' etc.
        'allowedExtensions'    => null, // * means all allowed or 'jpg,jpeg' etc.
        'allowEmptyExtensions' => null,
        'clear'                => true, // Useful to use resource files after upload etc.
        'clearSource'          => true, // Useful to display crop files after crop etc.
        'jpegQuality'          => -1,   // Use default quality.
        'webpQuality'          => -1,   // Use default quality.
        'directory'            => null, // Will be set in constructor via $file or $options argument.
    ];

    /**
     * Constructor.
     * @param array      $file
     * @param array|null $options
     */
    public final function __construct(array $file, array $options = null)
    {
        ['type' => $type, 'name'  => $name,  'tmp_name'  => $sourceTmp, 'file' => $source,
         'size' => $size, 'error' => $error, 'directory' => $directory] = $file + array_fill_keys([
            'type', 'name', 'tmp_name', 'size', 'error', 'file', 'directory'
        ], null);

        // Both "source" or "tmp_name" may be given (generally "tmp_name" come from $_FILES global).
        $source = $source ?? $sourceTmp;

        // All these stuff are needed.
        if (!$type || !$source) {
            throw new UploaderException(
                'No valid file given, "type" and "tmp_name" or "file" are required',
                null, UploaderError::NO_VALID_FILE
            );
        }

        $error = $error ? UploaderError::MESSAGES[$error] ?? 'Unknown file upload error' : null;
        if ($error) {
            throw new UploaderException($error, null, UploaderError::INTERNAL);
        }

        if (!is_string($source) || !is_file($source)) {
            throw new UploaderException(
                'No valid source file "%s" given by "tmp_name" or "file"',
                [$source], UploaderError::NO_VALID_SOURCE
            );
        }

        $name      = $name ?? uniqid();
        $size      = $size ?? filesize($source);
        $directory = $directory ?? $options['directory'] ?? null;

        $options = array_merge($this->options, $options ?? []);
        extract($options, EXTR_PREFIX_ALL, 'options');

        $maxFileSize = FileUtil::convertBytes((string) $options_maxFileSize);
        if ($maxFileSize && $size > $maxFileSize) {
            throw new UploaderException(
                'File size exceeded, "maxFileSize" option is "%s" (%s bytes)',
                [$options_maxFileSize, $maxFileSize], UploaderError::OPTION_SIZE_EXCEEDED
            );
        }

        // Type & extension security.
        if (!$options_allowedTypes || !$options_allowedExtensions) {
            throw new UploaderException(
                'Option "allowedTypes" and "allowedExtensions" must not be empty for '.
                'security reasons, please provide both types and extensions you allow (ie: for '.
                'types "image/jpeg,image/png" and for extensions "jpg,jpeg", or "*" to allow all)',
                null, UploaderError::OPTION_EMPTY
            );
        }

        $extension = pathinfo($name, PATHINFO_EXTENSION);
        if (!$extension && $options_allowEmptyExtensions === false) {
            throw new UploaderException(
                'Empty extensions not allowed via options',
                null, UploaderError::OPTION_EMPTY_EXTENSION
            );
        }

        // Type @override.
        try {
            $type = Mime::getType($source);
        } catch (MimeException $e) {
            throw new UploaderException($e);
        }

        if ($options_allowedTypes !== '*'
            && !in_array($type, explode(',', (string) $options_allowedTypes))) {
            throw new UploaderException(
                'Type "%s" not allowed via options, allowed types: "%s"'.
                [$type, $options_allowedTypes], UploaderError::OPTION_NOT_ALLOWED_TYPE
            );
        }

        $extension = $extension ?: Mime::getExtensionByType($type);
        if ($extension && $options_allowedExtensions !== '*'
            && !in_array($extension, explode(',', (string) $options_allowedExtensions))) {
            throw new UploaderException(
                'Extension "%s" not allowed via options, allowed extensions: "%s"',
                [$extension, $options_allowedExtensions], UploaderError::OPTION_NOT_ALLOWED_EXTENSION
            );
        }

        $directory = trim($directory ?: '');
        if (!$directory) {
            throw new UploaderException(
                'Directory must not be empty',
                null, UploaderError::DIRECTORY_EMPTY
            );
        }

        if (!is_dir($directory)) {
            $ok =@ mkdir($directory, 0644, true);
            if (!$ok) {
                throw new UploaderException(
                    'Cannot make directory [error: %s]',
                    ['@error'], UploaderError::DIRECTORY_ERROR
                );
            }
        }

        $this->source   = $source;
        $this->fileInfo = ['type' => $type, 'name'      => $this->prepareName($name),
                           'size' => $size, 'extension' => $extension];
        $this->options  = ['directory' => $directory] + $options;
    }

    /**
     * Destructor.
     */
    public final function __destruct()
    {
        $this->clear();
    }

    /**
     * Get source.
     * @return string
     */
    public final function getSource(): string
    {
        return $this->source;
    }

    /**
     * Get file info.
     * @return array
     */
    public final function getFileInfo(): array
    {
        return $this->fileInfo;
    }

    /**
     * Get options.
     * @return array
     */
    public final function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get destination.
     * @param  string|null $name
     * @param  string|null $nameAppendix
     * @return string
     */
    protected final function getDestination(string $name = null, string $nameAppendix = null): string
    {
        $extension = $this->fileInfo['extension'];

        // Check name & extension if given on runtime (with saveAs() or moveAs()).
        if ($name != null) {
            if (strpos($name, '.')) {
                $extension = pathinfo($name, PATHINFO_EXTENSION);
                if ($extension && $this->options['allowedExtensions'] !== '*'
                    && !in_array($extension, explode(',', (string) $this->options['allowedExtensions']))) {
                    throw new UploaderException(
                        'Extension "%s" not allowed via options, allowed extensions: "%s"',
                        [$extension, $this->options['allowedExtensions']], UploaderError::OPTION_NOT_ALLOWED_EXTENSION
                    );
                }
            }

            $name = $this->prepareName($name, $nameAppendix);
        }

        $destination = $this->options['directory'] .'/'. ($name ?? $this->fileInfo['name']);
        if ($extension != null) {
            $destination = $destination .'.'. $extension;
        }

        return $destination;
    }

    /**
     * Prepare name.
     * @param  string      $name
     * @param  string|null $nameAppendix
     * @return string
     * @throws froq\file\UploaderException
     */
    protected final function prepareName(string $name, string $nameAppendix = null): string
    {
        // Some security & standard stuff.
        $name = preg_replace('~[^a-z0-9-]+~i', '-', pathinfo($name, PATHINFO_FILENAME));
        if (strlen($name) > 250) {
            $name = substr($name, 0, 250);
        }

        // All names lower-cased.
        $name = strtolower(trim($name, '-'));

        // Hash name if option set.
        $hash = $this->options['hash'];
        if ($hash) {
            static $hashAlgos = [8 => 'fnv1a32', 16 => 'fnv1a64', 32 => 'md5', 40 => 'sha1'];

            $hashAlgo =@ $hashAlgos[$this->options['hashLength'] ?? 32];
            if (!$hashAlgo) {
                throw new UploaderException('Only "8,16,32,40" are accepted as "hashLength" option');
            }

            if ($hash == 'rand') {
                $name = hash($hashAlgo, uniqid(microtime(), true));
            } elseif ($hash == 'file') {
                $name = hash($hashAlgo, file_get_contents($this->source));
            } elseif ($hash == 'fileName') {
                $name = hash($hashAlgo, $name);
            } else {
                throw new UploaderException('Only "rand,file,fileName" are accepted as "hash" option');
            }
        }

        // Appendix like 'crop' (ie: abc123-crop.jpg).
        if ($nameAppendix) {
            $name .= '-'. strtolower(preg_replace('~[^a-z0-9-]~i', '', $nameAppendix));
        }

        return $name;
    }

    /**
     * Save.
     * @return string
     * @throws froq\file\UploaderException
     */
    abstract public function save(): string;

    /**
     * Save as.
     * @param  string      $name
     * @param  string|null $nameAppendix
     * @return string
     * @throws froq\file\UploaderException
     */
    abstract public function saveAs(string $name, string $nameAppendix = null): string;

    /**
     * Move.
     * @return string
     * @throws froq\file\UploaderException
     */
    abstract public function move(): string;

    /**
     * Move as.
     * @param  string $name
     * @param  string|null $nameAppendix
     * @return string
     * @throws froq\file\UploaderException
     */
    abstract public function moveAs(string $name, string $nameAppendix = null): string;

    /**
     * Clear.
     * @return void
     */
    abstract public function clear(bool $force = false): void;
}
