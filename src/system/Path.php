<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\system;

use froq\file\object\FileObject;

/**
 * A class for working with path (file/directory) objects.
 *
 * @package froq\file\system
 * @class   froq\file\system\Path
 * @author  Kerem Güneş
 * @since   6.0
 */
class Path extends AbstractSystem
{
    /** Path separator. */
    public final const SEPARATOR = PATH_SEPARATOR;

    /** Path types. */
    public final const TYPE_DIR  = 'dir',  TYPE_DIRECTORY = 'dir', // Alias.
                       TYPE_FILE = 'file', TYPE_LINK      = 'link';

    /** Valid path types. */
    public final const TYPES = ['dir', 'file', 'link'];

    /** Path type. */
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
            // Validate given type.
            if (!in_array($type, self::TYPES, true)) {
                throw new PathException('Invalid type %q [valids: %A]', [$type, self::TYPES]);
            } elseif ($realType && $type !== $realType) {
                throw new PathException('Unmatched types %q - %q', [$type, $realType]);
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

        if ($this->isFile()){
            return $this->toFile()->empty($sure);
        } elseif ($this->isDirectory()) {
            return $this->toDirectory()->empty($sure);
        }

        return false;
    }

    /**
     * Open a file as `FileObject`.
     *
     * @param  string      $mode
     * @param  string|null $mime
     * @param  array|null  $options
     * @return froq\file\object\FileObject
     */
    public final function openFile(string $mode = 'r+b', string $mime = null, array $options = null): FileObject
    {
        return $this->toFile()->open($mode, $mime, $options);
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
    public final function makeDirectory(int $mode = 0755, bool $recursive = true): bool
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

    /**
     * Create a File instance with self path.
     *
     * @return froq\file\system\File
     */
    public final function toFile(): File
    {
        return new File($this->path);
    }

    /**
     * Create a Directory instance with self path.
     *
     * @return froq\file\system\Directory
     */
    public final function toDirectory(): Directory
    {
        return new Directory($this->path);
    }

    /**
     * @alias makeDirectory()
     */
    public final function makeDir(...$args)
    {
        return $this->makeDirectory(...$args);
    }

    /**
     * @alias removeDirectory()
     */
    public final function removeDir()
    {
        return $this->removeDirectory();
    }

    /**
     * @alias toDirectory()
     */
    public final function toDir()
    {
        return $this->toDirectory();
    }
}
