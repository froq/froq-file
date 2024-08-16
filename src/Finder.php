<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * Simple finder class with RegExp and Glob utilities.
 *
 * @package froq\file
 * @class   froq\file\Finder
 * @author  Kerem Güneş
 * @since   6.0
 */
class Finder
{
    /** Root directory for queries. */
    protected ?string $root = null;

    /**
     * Constructor.
     *
     * @param string|Path|PathInfo|null $root
     */
    public function __construct(string|Path|PathInfo $root = null)
    {
        if ($root !== null) {
            $this->setRoot($root);
        }
    }

    /**
     * Set root.
     *
     * @param  string|Path|PathInfo $root
     * @return self
     */
    public function setRoot(string|Path|PathInfo $root): self
    {
        $this->root = (string) $root;

        return $this;
    }

    /**
     * Get root.
     *
     * @return string|null
     */
    public function getRoot(): string|null
    {
        return $this->root;
    }

    /**
     * Find files/directories by given pattern using regex utils.
     *
     * @param  string $pattern
     * @param  int    $flags
     * @return RegexIterator<SplFileInfo>
     * @throws froq\file\FinderException
     */
    public function find(string $pattern, int $flags = 0): \RegexIterator
    {
        $root = $this->prepareRoot(true);

        try {
            /** @var RegexIterator<SplFileInfo> */
            $iterator = new \RegexIterator(
                new \FilesystemIterator($root),
                $pattern, \RegexIterator::MATCH, $flags
            );
        } catch (\Throwable $e) {
            throw new FinderException($e, extract: true);
        }

        return $iterator;
    }

    /**
     * Find files/directories recursively by given pattern using regex utils.
     *
     * @param  string $pattern
     * @param  int    $flags
     * @return RegexIterator<SplFileInfo>
     * @throws froq\file\FinderException
     */
    public function findAll(string $pattern, int $flags = 0): \RegexIterator
    {
        $root = $this->prepareRoot(true);

        try {
            /** @var RegexIterator<SplFileInfo> */
            $iterator = new \RegexIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(
                        $root, \RecursiveDirectoryIterator::SKIP_DOTS
                    )
                ),
                $pattern, \RegexIterator::MATCH, $flags
            );
        } catch (\Throwable $e) {
            throw new FinderException($e, extract: true);
        }

        return $iterator;
    }

    /**
     * Glob self root with given pattern.
     *
     * @param  string $pattern
     * @param  int    $flags
     * @return GlobIterator<SplFileInfo>
     * @throws froq\file\FinderException
     */
    public function glob(string $pattern, int $flags = 0): \GlobIterator
    {
        $root = $this->prepareRoot();
        $pattern = $this->preparePattern($pattern);

        try {
            $iterator = new \GlobIterator($root . $pattern, $flags);
        } catch (\Throwable $e) {
            throw new FinderException($e, extract: true);
        }

        return $iterator;
    }

    /**
     * X-Glob self root with given pattern (accepts GLOB_BRACE etc. flags).
     *
     * @param  string      $pattern
     * @param  int         $flags
     * @param  bool|string $map
     * @param  bool        $list
     * @return XArray<SplFileInfo|object|string>
     */
    public function xglob(string $pattern, int $flags = 0, bool|string $map = true, bool $list = true): \XArray
    {
        $root = $this->prepareRoot();
        $pattern = $this->preparePattern($pattern);

        /** @var XArray */
        $ret = xglob($root . $pattern, $flags);

        // Use paths as keys.
        if (!$list) {
            $tmp = xarray();
            foreach ($ret as $path) {
                $tmp[$path] = $path;
            }
            [$ret, $tmp] = [$tmp, null];
        }

        // Map to SplFileInfo or given class.
        if ($map !== false) {
            $class = 'SplFileInfo';
            if (is_string($map)) {
                $class = $map;
            }
            $ret->map(fn($path) => new $class($path));
        }

        return $ret;
    }

    /**
     * Prepare root and check whether root is a valid/present path if given or check
     * options is true.
     *
     * @param  bool $check
     * @return string
     * @throws froq\file\FinderException
     */
    protected function prepareRoot(bool $check = false): string
    {
        $root = (string) $this->root;

        if (trim($root) !== '') {
            // Must be a valid/present path if given.
            if (($path = realpath($root)) === false) {
                throw new FinderException('Root directory not exists: %q', $root);
            }

            $root = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        } elseif ($check) {
            throw new FinderException('Root is empty yet, call setRoot()');
        }

        return $root;
    }

    /**
     * Prepare pattern.
     *
     * @param  string $pattern
     * @return string
     * @throws froq\file\FinderException
     */
    protected function preparePattern(string $pattern): string
    {
        $root = (string) $this->root;

        // Drop "//" stuff from pattern.
        if ($root && $root[-1] === DIRECTORY_SEPARATOR) {
            $pattern = ltrim($pattern, DIRECTORY_SEPARATOR);
        }

        if ($pattern === '') {
            throw new FinderException('Empty pattern');
        }

        return $pattern;
    }
}
