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
 * @object     Froq\File\File
 * @author     Kerem Güneş <k-gun@mail.com>
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
        'hash' => null,              // 'file' or 'fileName'
        'hashAlgo' => null,          // 'md5' or 'sha1' (default='md5')
        'allowedExtensions' => null, // null (all allowed) or 'jpg,jpeg' etc.
        'jpegQuality' => 80,         // for image files
    ];

    /**
     * Error.
     * @var string
     */
    protected $error;

    /**
     * Errors.
     * @var array
     */
    protected static $errors = [
        0 => null,
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
        3 => 'The uploaded file was only partially uploaded.',
        4 => 'No file was uploaded.',
        6 => 'Missing a temporary folder.',
        7 => 'Failed to write file to disk.',
        8 => 'A PHP extension stopped the file upload.',
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
        if (!isset($file['tmp_name'], $file['name'], $file['type'], $file['size'], $file['error'])) {
            throw new FileException("No valid file given, 'tmp_name,name,type,size,error' are required");
        }

        // check file exists
        $this->source = $file['tmp_name'];
        if (!is_file($this->source)) {
            throw new FileException("No file '{$this->source}' found by 'tmp_name'");
        }

        // check file size
        $maxFileSize = ini_get('upload_max_filesize');
        $maxFileSizeBytes = $this->convertBytes((string) $maxFileSize);
        if ($file['size'] > $maxFileSizeBytes) {
            throw new FileException("File size exceeded, ini upload_max_filesize is '{$maxFileSize}'");
        }

        $this->options = array_merge($this->options, $options);

        // check extension
        $extension = Mime::getExtensionByType($file['type']);
        if ($this->options['allowedExtensions'] != null &&
            !in_array($extension, (array) explode(',', (string) $this->options['allowedExtensions']))) {
            throw new FileException("Extension '{$extension}' is not allowed");
        }

        $this->name = $this->prepareName($file['name']);
        $this->type = $file['type'];
        $this->size = $file['size'];
        $this->extension = $extension;
        $this->error = $file['error'] ? self::$errors[$file['error']] ?? 'Unknown' : null;

        if ($directory == '') {
            throw new FileException('Directory cannot be empty');
        }

        $this->directory = $directory;
        if (!is_dir($this->directory)) {
            @ $ok = mkdir($this->directory, 0644, true);
            if (!$ok) {
                throw new FileException(sprintf('Cannot make directory, error[%s]',
                    error_get_last()['message'] ?? 'Unknown'));
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
     * @return string
     */
    public final function getExtension(): string
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
     * @return string
     */
    public final function getDestination(string $name = null): string
    {
        // update name
        if ($name != null) {
            $this->name = $name = $this->prepareName($name);
        }

        return sprintf('%s/%s.%s', $this->directory, $name ?? $this->name, $this->extension);
    }

    /**
     * Has error.
     * @return bool
     */
    public final function hasError(): bool
    {
        return $this->error != null;
    }

    /**
     * Get error.
     * @return ?string
     */
    public final function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Delete.
     * @param  string $file
     * @return void
     */
    public final function delete(string $file): void
    {
        @ $ok = unlink($file);
        if (!$ok) {
            throw new FileException(sprintf("Cannot delete file '{$file}', error[%s]",
                error_get_last()['message'] ?? 'Unknown'));
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
            'error'       => $this->error,
            'directory'   => $this->directory
        ];
    }

    /**
     * Prepare name.
     * @param  string $name
     * @param  bool   $hash @internal
     * @return string
     * @throws Froq\File\FileException
     */
    protected final function prepareName(string $name, bool $hash = null): string
    {
        // some security stuff
        $name = preg_replace('~[^\w-.]~u', '', pathinfo($name, PATHINFO_FILENAME));
        if (strlen($name) > 250) {
            $name = substr($name, 0, 250);
        }

        // hash name if option set
        $hash = $hash ?? $this->options['hash'];
        if ($hash) {
            $hashAlgo = $this->options['hashAlgo'] ?? 'md5';
            if (!in_array($hashAlgo, ['md5', 'sha1'])) {
                throw new FileException("Only 'md5,sha1' algos accepted");
            }

            if ($hash === 'fileName') {
                $name = call_user_func($hashAlgo, $name);
            } elseif ($hash === 'file') {
                $name = call_user_func($hashAlgo .'_file', $this->source);
            }
        }

        return $name;
    }

    /**
     * Convert bytes.
     * @param  string $value
     * @return int
     */
    protected final function convertBytes(string $value): int
    {
        if (!is_numeric($value)) {
            $scan = sscanf($value, '%d%s');
            if (isset($scan[1])) {
                $value = (int) $value;
                switch (strtoupper($scan[1])) {
                    case 'K'; $value *= 1024; break;
                    case 'M'; $value *= 1048576; break;
                    case 'G'; $value *= 1073741824; break;
                }
            }
        }

        return (int) $value;
    }
}
