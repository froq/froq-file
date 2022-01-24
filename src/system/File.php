<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file\system;

use froq\file\object\{FileObject, FileObjectException};

/**
 * File.
 *
 * A class for working with file objects.
 *
 * @package froq\file\system
 * @object  froq\file\system\File
 * @author  Kerem Güneş
 * @since   6.0
 */
class File extends AbstractSystem
{
    /**
     * Constructor.
     *
     * @param  string $path
     * @throws froq\file\system\FileException
     */
    public function __construct(string $path)
    {
        parent::__construct($path);

        if ($this->isDir()) {
            throw new FileException(
                (realpath($path) != $this->path)
                    ? 'Given path is a directory [path: %s, real path: %s]'
                    : 'Given path is a directory [path: %s]',
                [$path, $this->path]
            );
        }
    }

    /** @override */
    public function ok(): bool
    {
        return is_file($this->path);
    }

    /**
     * Empty entire contents of a file.
     *
     * @param  bool $sure
     * @return bool
     */
    public final function empty(bool $sure = false): bool
    {
        $sure || throw new FileException(
            'Be sure before calling %s() and deleting all contents of file `%s`',
            [__method__, $this->path]
        );

        if (!$this->exists()) {
            return false;
        }

        if (file_put_contents($this->path, '', LOCK_EX) === false) {
            throw new FileException(
                'Cannot empty file [file: %s, error: %s]', [$this->path, '@error']
            );
        }

        return (bool) $this->isEmpty();
    }

    /**
     * Get contents of a file.
     *
     * @return string
     * @throws froq\file\system\FileException
     */
    public final function getContents(): string
    {
        try {
            return \froq\file\File::getContents($this->path);
        } catch (\froq\file\FileException $e) {
            throw new FileException($e->message, code: $e->code, cause: $e);
        }
    }

    /**
     * Set contents of a file.
     *
     * @param  string $contents
     * @return string
     * @throws froq\file\system\FileException
     */
    public final function setContents(string $contents, int $flags = 0)
    {
        try {
            return \froq\file\File::setContents($this->path, $contents, $flags);
        } catch (\froq\file\FileException $e) {
            throw new FileException($e->message, code: $e->code, cause: $e);
        }
    }

    /**
     * Open a file as FileObject.
     *
     * @param  string      $mode
     * @param  string|null $mime
     * @param  array|null  $options
     * @return froq\file\FileObject
     * @throws froq\file\system\FileException
     */
    public final function open(string $mode = 'r+b', string $mime = null, array $options = null): FileObject
    {
        try {
            return \froq\file\File::open($this->path, $mode, $mime, $options);
        } catch (\froq\file\FileException $e) {
            throw new FileException($e->message, code: $e->code, cause: $e->cause);
        }
    }
}
