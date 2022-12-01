<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file\system;

use froq\file\object\FileObject;

/**
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

    /**
     * @override
     */
    public final function okay(): bool
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
            'Be sure before calling %s() and deleting all contents of file %q',
            [__METHOD__, $this->path]
        );

        if (!$this->exists()) {
            return false;
        }

        if (file_put_contents($this->path, '', LOCK_EX) === false) {
            throw new FileException(
                'Cannot empty file %q [error: %s]', [$this->path, '@error']
            );
        }

        return (bool) $this->isEmpty();
    }

    /**
     * Open a file as `FileObject`.
     *
     * @param  string      $mode
     * @param  string|null $mime
     * @param  array|null  $options
     * @return froq\file\object\FileObject
     * @throws froq\file\system\FileException
     */
    public final function open(string $mode = 'r+b', string $mime = null, array $options = null): FileObject
    {
        try {
            return \froq\file\File::open($this->path, $mode, $mime, $options);
        } catch (\froq\file\FileException $e) {
            throw new FileException($e);
        }
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
            throw new FileException($e);
        }
    }

    /**
     * Set contents of a file.
     *
     * @param  string $contents
     * @param  int    $flags
     * @return bool
     * @throws froq\file\system\FileException
     */
    public final function setContents(string $contents, int $flags = 0): bool
    {
        try {
            return \froq\file\File::setContents($this->path, $contents, $flags);
        } catch (\froq\file\FileException $e) {
            throw new FileException($e);
        }
    }
}
