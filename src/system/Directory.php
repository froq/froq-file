<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file\system;

/**
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
    public final function okay(): bool
    {
        return is_dir($this->path);
    }

    /**
     * Glob of directory.
     *
     * @param  string $pattern
     * @param  int    $flags
     * @return array
     */
    public final function glob(string $pattern, int $flags = 0): array
    {
        // Prevent recursions for "*" stuff.
        if (!str_starts_with($pattern, '/')) {
            $pattern = '/' . $pattern;
        }

        return glob($this->path . $pattern, $flags) ?: [];
    }

    /**
     * Get (sub) dirs.
     *
     * @return array
     */
    public final function getDirs(): array
    {
        return array_filter($this->glob('/*'), 'is_dir');
    }

    /**
     * Get files.
     *
     * @return array
     */
    public final function getFiles(): array
    {
        return array_filter($this->glob('/*'), 'is_file');
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
            'Be sure before calling %s() and deleting all contents of directory %q',
            [__METHOD__, $this->path]
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
            if ($paths = glob($root . '/*')) {
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

        // Drop others (if any left).
        foreach ($this->getFiles() as $cur) {
            unlink($cur);
        }
        foreach ($this->getDirs() as $cur) {
            $rmrfExec($cur);
            $rmrf($cur);
            rmdir($cur);
        }

        return (bool) $this->isEmpty();
    }
}
