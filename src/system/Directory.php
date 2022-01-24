<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file\system;

/**
 * Directory.
 *
 * A class for working with directory objects.
 *
 * @package froq\file\system
 * @object  froq\file\system\Directory
 * @author  Kerem Güneş
 * @since   6.0
 */
class Directory extends AbstractSystem
{
    /** @const string */
    public final const SEPARATOR = DIRECTORY_SEPARATOR;

    /**
     * Constructor.
     *
     * @param  string $path
     * @throws froq\file\system\DirectoryException
     */
    public function __construct(string $path)
    {
        parent::__construct($path);

        if ($this->isFile()) {
            throw new DirectoryException(
                (realpath($path) != $this->path)
                    ? 'Given path is a file [path: %s, real path: %s]'
                    : 'Given path is a file [path: %s]',
                [$path, $this->path]
            );
        }
    }

    /** @override */
    public function ok(): bool
    {
        return is_dir($this->path);
    }

    /**
     * Glob of directory.
     *
     * @param  string $pattern
     * @param  int    $flags
     * @return array|null
     */
    public final function glob(string $pattern, int $flags = 0): array|null
    {
        // Prevent recursions for "*" stuff.
        if (!str_starts_with($pattern, '/')) {
            $pattern = '/' . $pattern;
        }

        $ret = glob($this->path . $pattern, $flags);

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Get (sub) dirs.
     *
     * @return array|null
     */
    public final function getDirs(): array|null
    {
        $glob = $this->glob('*');

        return ($glob !== null) ? array_filter($glob, 'is_dir') : null;
    }

    /**
     * Get files.
     *
     * @return array|null
     */
    public final function getFiles(): array|null
    {
        $glob = $this->glob('*');

        return ($glob !== null) ? array_filter($glob, 'is_file') : null;
    }

    /**
     * Empty entire contents of a directory.
     *
     * @param  bool $sure
     * @return bool
     */
    public final function empty(bool $sure = false): bool
    {
        $sure || throw new DirectoryException(
            'Be sure before calling %s() and deleting all contents of directory `%s`',
            [__method__, $this->path]
        );

        // A small safety check..
        if ($this->path == '/') {
            throw new DirectoryException(
                'Cannot call empty() method for root / directory'
            );
        }

        if (!$this->exists()) {
            return false;
        }
        if ($this->isEmpty()) {
            return true;
        }

        // The fastest way, so far..
        $rmrfExec = function ($root) {
            try {
                exec(
                    'find ' . escapeshellarg($root) . ' ' .
                    '-type f -print0 | xargs -0 rm 2> /dev/null'
                );
                clearstatcache();
            } catch (\Error) {}
        };
        // Oh, my lad..
        $rmrf = function ($root) use (&$rmrf, &$rmrfExec) {
            $paths = glob($root . '/*');
            if ($paths) {
                foreach ($paths as $path) {
                    if (is_file($path)) {
                        unlink($path);
                    } elseif (is_dir($path)) {
                        $rmrfExec($path);
                        $rmrf($path);
                        rmdir($path);
                    }
                }
            }
        };

        // Drop files (try).
        $rmrfExec($this->path);

        // Drop others if any left.
        foreach ((array) $this->getFiles() as $path) {
            unlink($path);
        }
        foreach ((array) $this->getDirs() as $path) {
            $rmrfExec($path);
            $rmrf($path);
            rmdir($path);
        }

        return (bool) $this->isEmpty();
    }
}
