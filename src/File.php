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
     * Directory.
     * @var string
     */
    protected $directory;

    /**
     * Name.
     * @var string
     */
    protected $name;

    /**
     * Tmp name.
     * @var string
     */
    protected $tmpName;

    /**
     * New name.
     * @var string
     */
    protected $newName;

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
     * Error code.
     * @var int
     */
    protected $errorCode;

    /**
     * Error string.
     * @var string
     */
    protected $errorString;

    /**
     * Options.
     * @var array
     */
    protected $options = [
        'hash' => null,              // 'file' or 'fileName'
        'hashAlgo' => null,          // 'md5' or 'sha1' (default='md5')
        'allowedExtensions' => null, // null (all allowed) or 'jpg,jpeg'
        'jpegQuality' => 80,         // for image files
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
        $this->tmpName = $file['tmp_name'];
        if (!is_file($this->tmpName)) {
            throw new FileException("No file '{$this->tmpName}' found by 'tmp_name'");
        }

        // check file size
        $maxFileSize = self::convertBytes((string) ini_get('upload_max_filesize'));
        if ($file['size'] > $maxFileSize) {
            throw new FileException("File size exceeded, fileSize={$file['size']} maxFileSize={$maxFileSize}");
        }

        // prepare options
        $this->options = array_merge($this->options, $options);

        $this->name = self::prepareName($file['name']);
        $this->type = $file['type'];
        $this->size = $file['size'];
        $this->error = $file['error'] ?: null;
        $this->errorString = ($this->error != null) ? self::$errors[$this->error] ?? 'Unknown.' : null;

        $this->extension = Mime::getExtensionByType($this->type);
        if (!empty($this->options['allowedExtensions'])
            && !in_array($this->extension, (array) explode(',', $this->options['allowedExtensions']))) {
            throw new FileException("Extension '{$this->extension}' is not allowed");
        }

        // directory stuff
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
     * Get tmp name.
     * @return string
     */
    public final function getTmpName(): string
    {
        return $this->tmpName;
    }

    /**
     * Set new name.
     * @param  string $newName
     * @return void
     */
    public final function setNewName($newName): void
    {
        $this->newName = self::prepareName($newName);
    }

    /**
     * Get new name.
     * @return ?string
     */
    public final function getNewName(): ?string
    {
        return $this->newName;
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
     * Get extension.
     * @return string
     */
    public final function getExtension(): string
    {
        return $this->extension;
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
     * Get error.
     * @return ?int
     */
    public final function getError(): ?int
    {
        return $this->error;
    }

    /**
     * Get error string.
     * @return ?string
     */
    public final function getErrorString(): ?string
    {
        return $this->errorString;
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
     * Get source path.
     * @return string
     */
    public final function getSourcePath(): string
    {
        return $this->tmpName;
    }

    /**
     * Get destination path.
     * @param  string|null $name
     * @return string
     */
    public final function getDestinationPath(string $name = null): string
    {
        // safe name if provided
        $name = ($name === null) ? $this->name : self::prepareName($name, false);

        return sprintf('%s/%s.%s', $this->directory, $name, $this->extension);
    }

    /**
     * Ok.
     * @return bool
     */
    public final function ok(): bool
    {
        return $this->error === null;
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
            'tmp_name'    => $this->tmpName,
            'new_name'    => $this->newName,
            'type'        => $this->type,
            'size'        => $this->size,
            'error'       => $this->error,
            'errorString' => $this->errorString
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
            $hashAlgo = $this->options['hashAlgo'] ?? 'sha1';
            if (!in_array($hashAlgo, ['md5', 'sha1'])) {
                throw new FileException("Only 'md5,sha1' algos accepted");
            }

            if ($hash == 'fileName') {
                $name = call_user_func($hashAlgo, $this->tmpName);
            } elseif ($hash == 'file') {
                $name = call_user_func($hashAlgo .'_file', $this->tmpName);
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
