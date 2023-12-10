<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * A path class for accessing to info and stat of directories / files.
 *
 * @package froq\file
 * @class   froq\file\Path
 * @author  Kerem Güneş
 * @since   7.0, 7.1
 */
class Path extends PathInfo
{
    /** Path name. */
    public readonly string $name;

    /**
     * Constructor.
     *
     * @param  string $path
     * @throws froq\file\PathException
     */
    public function __construct(string $path)
    {
        try {
            parent::__construct($path);

            $this->name = $this->path;
        } catch (\Throwable $e) {
            throw PathException::exception($e);
        }
    }

    /**
     * Just for concise dumps.
     *
     * @magic
     */
    public function __debugInfo(): array
    {
        return ['name' => $this->name, 'info' => $this->info];
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
