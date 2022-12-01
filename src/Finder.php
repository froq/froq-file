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

    /** File class. */
    protected ?string $fileClass = null;

    /** Info class. */
    protected ?string $infoClass = null;

    /**
     * Constructor.
     *
     * @param string|null $root
     * @param string|null $fileClass
     * @param string|null $infoClass
     */
    public function __construct(string $root = null, string $fileClass = null, string $infoClass = null)
    {
        $this->root      = $root;
        $this->fileClass = $fileClass;
        $this->infoClass = $infoClass;
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
     * Set file class.
     *
     * @param  string $fileClass
     * @return self
     */
    public function setFileClass(string $fileClass): self
    {
        $this->fileClass = $fileClass;

        return $this;
    }

    /**
     * Get file class.
     *
     * @return string|null
     */
    public function getFileClass(): string|null
    {
        return $this->fileClass;
    }

    /**
     * Set info class.
     *
     * @param  string $infoClass
     * @return self
     */
    public function setInfoClass(string $infoClass): self
    {
        $this->infoClass = $infoClass;

        return $this;
    }

    /**
     * Get info class.
     *
     * @return string|null
     */
    public function getInfoClass(): string|null
    {
        return $this->infoClass;
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

        $this->assignIteratorClasses($iterator);

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

        $this->assignIteratorClasses($iterator);

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

        $this->assignIteratorClasses($iterator);

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

    /**
     * Assign provided file / info classes for an iterator.
     *
     * @param  Iterator $iterator
     * @return void
     * @throws froq\file\FinderException
     */
    protected function assignIteratorClasses(\Iterator $iterator): void
    {
        if (($fileClass = $this->getFileClass()) !== null) {
            if (!class_exists($fileClass)) {
                throw new FinderException('File class %q not found', $fileClass);
            }
            $iterator->setFileClass($fileClass);
        }

        if (($infoClass = $this->getInfoClass()) !== null) {
            if (!class_exists($infoClass)) {
                throw new FinderException('Info class %q not found', $infoClass);
            }
            $iterator->setInfoClass($infoClass);
        }
    }
}
