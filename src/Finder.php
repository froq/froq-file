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
     * @param string|null $root
     */
    public function __construct(string $root = null)
    {
        $this->root = $root;
    }

    /**
     * Set root.
     *
     * @param  string $root
     * @return self
     */
    public function setRoot(string $root): self
    {
        $this->root = $root;

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
     * Find files/directories recursively by given pattern using glob utils.
     *
     * @param  string $pattern
     * @param  int    $flags
     * @return GlobIterator<SplFileInfo>
     * @throws froq\file\FinderException
     */
    public function glob(string $pattern, int $flags = 0): \GlobIterator
    {
        $root = $this->prepareRoot();

        try {
            $iterator = new \GlobIterator($root . $pattern, $flags);
        } catch (\Throwable $e) {
            throw new FinderException($e, extract: true);
        }

        return $iterator;
    }

    /**
     * Find files/directories recursively by given pattern using glob utils
     * but returning XArray.
     *
     * @param  string $pattern
     * @param  int    $flags
     * @return XArray<SplFileInfo>
     * @causes froq\file\FinderException
     */
    public function xglob(string $pattern, int $flags = 0): \XArray
    {
        $ret = new \XArray();

        foreach ($this->glob($pattern, $flags) as $info) {
            $ret[] = $info;
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
        $root = (string) $this->getRoot();

        if (trim($root) !== '') {
            // Must be a valid/present path if given.
            if (($path = realpath($root)) === false) {
                throw new FinderException('Root directory not exists: %q', $root);
            }

            $root = chop($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        } elseif ($check) {
            throw new FinderException('Root is empty yet, call setRoot()');
        }

        return $root;
    }
}
