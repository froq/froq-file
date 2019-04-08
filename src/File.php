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
 * File.
 * @package froq\file
 * @object  froq\file\File
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   1.0
 */
abstract class File
{
    /**
     * Name.
     * @var string
     */
    protected $name;

    /**
     * Type.
     * @var string
     */
    protected $type;

    /**
     * Size.
     * @var int
     */
    protected $size;

    /**
     * Extension.
     * @var string
     */
    protected $extension;

    /**
     * Directory.
     * @var string
     */
    protected $directory;

    /**
     * Source.
     * @var string
     */
    protected $source;

    /**
     * Options.
     * @var array
     */
    protected $options = [
        'hash' => null,                  // rand, file, fileName
        'hashLength' => null,            // 8, 16, 32 or 40 (default=16)
        'maxFileSize' => null,           // in binary mode: for 2 megabytes 2048, 2048k or 2m
        'allowedTypes' => null,          // * means all allowed or 'image/jpeg,image/png' etc.
        'allowedExtensions' => null,     // * means all allowed or 'jpg,jpeg' etc.
        'allowEmptyExtensions' => null,  // allow empty extension
        'jpegQuality' => 85,             // for image files
    ];

    /**
     * Constructor.
     * @param array  $file
     * @param string $directory
     * @param array  $options
     */
    public function __construct(array $file, string $directory, array $options = [])
    {
        // all these stuff are needed
        if (!isset($file['error'], $file['tmp_name'], $file['name'], $file['type'], $file['size'])) {
            throw new FileException("No valid file given, 'tmp_name,name,type,size,error' are ".
                "required", FileError::NO_VALID_FILE);
        }

        $error = $file['error'] ? FileError::all()[$file['error']] ?? 'Unknown' : null;
        if ($error != null) {
            throw new FileException($error, FileError::INTERNAL);
        }

        // check file exists
        $this->source = $file['tmp_name'];
        if (!is_file($this->source)) {
            throw new FileException("No valid source '{$this->source}' found by 'tmp_name'",
                FileError::NO_VALID_SOURCE);
        }

        $this->options = array_merge($this->options, $options);

        // check file size
        $maxFileSize = self::convertBytes($this->options['maxFileSize']);
        if ($maxFileSize && $file['size'] > $maxFileSize) {
            throw new FileException("File size exceeded, options maxFileSize is ".
                "'{$this->options['maxFileSize']}' ({$maxFileSize} bytes)",
                FileError::OPTION_SIZE_EXCEEDED);
        }

        { // type & extension stuff
            if (empty($this->options['allowedTypes']) || empty($this->options['allowedExtensions'])) {
                throw new FileException("'allowedTypes' and 'allowedExtensions' options cannot be ".
                    "empty for security, please provide types and extensions you allow (ie: for types ".
                    "'image/jpeg,image/png' and for extensions 'jpg,jpeg', or use '*' to allow all)",
                FileError::OPTION_EMPTY);
            }

            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            if ($this->options['allowEmptyExtensions'] === false && $fileExtension === '') {
                throw new FileException("Empty extensions not allowed by options",
                    FileError::OPTION_EMPTY_EXTENSION);
            }

            $type = Mime::getType($this->source);
            if ($this->options['allowedTypes'] !== '*'
                && !in_array($type, explode(',', (string) $this->options['allowedTypes']))) {
                throw new FileException("Type '{$type}' not allowed by options, allowed ".
                    "types: '{$this->options['allowedTypes']}'", FileError::OPTION_NOT_ALLOWED_TYPE);
            }

            $extension = ($fileExtension !== '') ? $fileExtension : Mime::getExtensionByType($type);
            if ($this->options['allowedExtensions'] !== '*'
                && !in_array($extension, explode(',', (string) $this->options['allowedExtensions']))) {
                throw new FileException("Extension '{$extension}' not allowed by options, allowed ".
                    "extensions: '{$this->options['allowedExtensions']}'", FileError::OPTION_NOT_ALLOWED_EXTENSION);
            }
        }

        $this->name = $this->prepareName($file['name']);
        $this->type = $type;
        $this->size = $file['size'];
        $this->extension = ($fileExtension !== '') ? $fileExtension : null;

        { // directory stuff
            if ($directory == '') {
                throw new FileException('Directory cannot be empty', FileError::DIRECTORY_EMPTY);
            }

            $this->directory = $directory;
            if (!is_dir($this->directory)) {
                @ $ok = mkdir($this->directory, 0644, true);
                if (!$ok) {
                    throw new FileException($this->prepareErrorMessage('Cannot make directory'),
                        FileError::DIRECTORY_ERROR);
                }
            }
        }
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->clear();
    }

    /**
     * Get name.
     * @return string
     */
    public final function getName(): string
    {
        return $this->name;
    }

    /**
     * Get type.
     * @return string
     */
    public final function getType(): string
    {
        return $this->type;
    }

    /**
     * Get size.
     * @return int
     */
    public final function getSize(): int
    {
        return $this->size;
    }

    /**
     * Get extension.
     * @return ?string
     */
    public final function getExtension(): ?string
    {
        return $this->extension;
    }

    /**
     * Get directory.
     * @return string
     */
    public final function getDirectory(): string
    {
        return $this->directory;
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
     * Get source.
     * @return string
     */
    public final function getSource(): string
    {
        return $this->source;
    }

    /**
     * Get destination.
     * @param  string|null $name
     * @param  string      $nameAppendix
     * @return string
     */
    public final function getDestination(string $name = null, string $nameAppendix = ''): string
    {
        // update name
        if ($name != null) {
            $this->name = $name = $this->prepareName($name, $nameAppendix);
        }

        $return = $this->directory .'/'. ($name ?? $this->name);
        if ($this->extension != null) {
            $return .= '.'. $this->extension;
        }

        return $return;
    }

    /**
     * Delete.
     * @param  string $file
     * @return void
     * @since  3.0
     */
    public final function delete(string $file): void
    {
        @ $ok = unlink($file);
        if (!$ok) {
            throw new FileException($this->prepareErrorMessage("Cannot delete file '{$file}'"));
        }
    }

    /**
     * To array.
     * @return array
     */
    public final function toArray(): array
    {
        return [
            'name'        => $this->name,
            'type'        => $this->type,
            'size'        => $this->size,
            'extension'   => $this->extension,
            'source'      => $this->source,
            'directory'   => $this->directory
        ];
    }

    /**
     * Prepare name.
     * @param  string $name
     * @param  string $nameAppendix
     * @return string
     * @throws froq\file\FileException
     * @since  1.0
     */
    protected final function prepareName(string $name, string $nameAppendix = ''): string
    {
        // some security & standard stuff
        $name = preg_replace(['~[\s_-]+~', '~[^a-z0-9-]~i'], ['-', ''], pathinfo($name, PATHINFO_FILENAME));
        $nameAppendix = preg_replace('~[^a-z0-9-]~i', '', $nameAppendix);
        if (strlen($name) > 250) {
            $name = substr($name, 0, 250);
        }

        // all names lower-cased
        $name = strtolower($name);
        $nameAppendix = strtolower($nameAppendix);

        // hash name if option set
        $hash = $this->options['hash'];
        if ($hash != '') {
            static $hashAlgos = [8 => 'fnv1a32', 16 => 'fnv1a64', 32 => 'md5', 40 => 'sha1'];
            @ $hashAlgo = $hashAlgos[$this->options['hashLength'] ?? 16];
            if ($hashAlgo == null) {
                throw new FileException("Only '8,16,32,40' are accepted");
            }

            if ($hash == 'rand') {
                $name = hash($hashAlgo, uniqid(microtime(), true));
            } elseif ($hash == 'file') {
                $name = hash($hashAlgo, file_get_contents($this->source));
            } elseif ($hash == 'fileName') {
                $name = hash($hashAlgo, $name);
            }

        }

        // appendix like '-50x50-crop' (ie: abc123-50x50-crop.jpg)
        $name .= $nameAppendix;

        return $name;
    }

    /**
     * Prepare error message.
     * @param  string $message
     * @return string
     * @since  3.0
     */
    protected final function prepareErrorMessage(string $message): string
    {
        return sprintf("{$message}, error[%s]", error_get_last()['message'] ?? 'Unknown');
    }

    /**
     * Convert bytes.
     * @param  int|string $value
     * @return int
     * @since  3.0
     */
    public static final function convertBytes($value): int
    {
        if ($value && !is_numeric($value)) {
            $scan = sscanf($value, '%d%2s');
            if (isset($scan[1])) {
                $value = (int) $value;
                switch (strtoupper($scan[1])) {
                    case 'K': case 'KB': $value *= 1024; break;
                    case 'M': case 'MB': $value *= 1048576; break;
                    case 'G': case 'GB': $value *= 1073741824; break;
                }
            }
        }

        return (int) $value;
    }
}
