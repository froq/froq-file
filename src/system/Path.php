<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file\system;

use froq\file\object\FileObject;

/**
 * Path.
 *
 * A class for working with path (file/directory) objects.
 *
 * @package froq\file\system
 * @object  froq\file\system\Path
 * @author  Kerem Güneş
 * @since   6.0
 */
class Path extends AbstractSystem
{
    /** @const string */
    public final const SEPARATOR = PATH_SEPARATOR;

    /** @const string */
    public final const TYPE_DIR  = 'dir',  TYPE_DIRECTORY = 'dir', // Alias.
                       TYPE_FILE = 'file', TYPE_LINK      = 'link';

    /** @const array<string> */
    public final const TYPES = ['dir', 'file', 'link'];

    /** @var string|null */
    public readonly string|null $type;

    /**
     * Constructor.
     *
     * @param  string      $path
     * @param  string|null $type
     * @throws froq\file\system\PathException
     */
    public function __construct(string $path, string $type = null)
    {
        parent::__construct($path);

        $realType = $this->getType();

        if ($type !== null) {
            if (!in_array($type, self::TYPES)) {
                throw new PathException(
                    'Invalid type `%s` [valids: %s]',
                    [$type, join(',', self::TYPES)]
                );
            } elseif ($type != $realType) {
                throw new PathException(
                    'Unmatched types `%s != %s`',
                    [$type, $realType]
                );
            }
            $this->type = $type;
        } else {
            $this->type = $realType;
        }
    }

    /**
     * Empty entire contents of a file/directory.
     *
     * @param  bool $sure
     * @return bool
     */
    public final function empty(bool $sure = false): bool
    {
        if (!$this->exists()) {
            return false;
        }

        if ($this->isDir()){
            return $this->toDir()->empty($sure);
        } elseif ($this->isFile()) {
            return $this->toFile()->empty($sure);
        }

        return false;
    }

    /**
     * Get contents of a file.
     *
     * @return string
     * @throws froq\file\system\PathException
     */
    public final function getFileContents(): string
    {
        try {
            return \froq\file\File::getContents($this->path);
        } catch (\froq\file\FileException $e) {
            throw new PathException($e->message, code: $e->code, cause: $e);
        }
    }

    /**
     * Set contents of a file.
     *
     * @param  string $contents
     * @return bool
     * @throws froq\file\system\PathException
     */
    public final function setFileContents(string $contents, int $flags = 0): bool
    {
        try {
            return \froq\file\File::setContents($this->path, $contents, $flags);
        } catch (\froq\file\FileException $e) {
            throw new PathException($e->message, code: $e->code, cause: $e);
        }
    }

    /**
     * Open a file as FileObject.
     *
     * @param  string      $mode
     * @param  string|null $mime
     * @param  array|null  $options
     * @return froq\file\FileObject
     */
    public final function openFile(string $mode = 'r+b', string $mime = null, array $options = null): FileObject
    {
        try {
            return \froq\file\File::open($this->path, $mode, $mime, $options);
        } catch (\froq\file\FileException $e) {
            throw new PathException($e->message, code: $e->code, cause: $e->cause);
        }
    }

    /**
     * Make a file.
     *
     * @param  int $mode
     * @return bool
     */
    public final function makeFile(int $mode = 0644): bool
    {
        return mkfile($this->path, $mode);
    }

    /**
     * Remove a file.
     *
     * @return bool
     */
    public final function removeFile(): bool
    {
        return rmfile($this->path);
    }

    /**
     * Make a directory.
     *
     * @param  int  $mode
     * @param  bool $recursive
     * @return bool
     */
    public final function makeDirectory(int $mode = 0755, bool $recursive = false): bool
    {
        return mkdir($this->path, $mode, $recursive);
    }

    /**
     * Remove a directory.
     *
     * @return bool
     */
    public final function removeDirectory(): bool
    {
        return rmdir($this->path);
    }

    /** @aliasOf makeDirectory() */
    public final function makeDir(...$args) { return $this->makeDirectory(...$args); }

    /** @aliasOf removeDirectory() */
    public final function removeDir(...$args) { return $this->removeDirectory(...$args); }
}
