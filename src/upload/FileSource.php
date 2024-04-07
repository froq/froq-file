<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\upload;

/**
 * File class for working with uploaded files.
 *
 * @package froq\file\upload
 * @class   froq\file\upload\FileSource
 * @author  Kerem Güneş
 * @since   3.0, 5.0, 7.0
 */
class FileSource extends Source
{
    /**
     * @inheritDoc froq\file\upload\Source
     */
    public function save(string $to, string $appendix = null): string
    {
        $source = $this->getSourceFile();
        $target = $this->prepareTarget($to, $appendix);

        $this->overwriteCheck($target);

        if (copy($source, $target)) {
            $this->applyMode($target);
            return $target;
        }

        // Option overwrite=true issue.
        if (is_file($target) && copy($source, $tmp = tmpnam())) {
            if (copy($source, $tmp) && copy($tmp, $target)) {
                unlink($tmp); // Drop temp file.
                $this->applyMode($target);
                return $target;
            }
        }

        throw FileSourceException::error();
    }

    /**
     * @inheritDoc froq\file\upload\Source
     */
    public function move(string $to, string $appendix = null): string
    {
        $source = $this->getSourceFile();
        $target = $this->prepareTarget($to, $appendix);

        $this->overwriteCheck($target);

        if (rename($source, $target)) {
            $this->applyMode($target);
            return $target;
        }

        throw FileSourceException::error();
    }

    /**
     * @inheritDoc froq\file\upload\Source
     */
    public function clear(bool $force = false): void
    {
        if ($force || $this->options['clearSource']) {
            $this->clearSource();
        }
    }

    /**
     * @inheritDoc froq\common\interface\Stringable
     */
    public function toString(): string
    {
        return (string) file_get_contents($this->getSourceFile());
    }

    /**
     * Get contents Base64 encoded.
     *
     * @return string
     */
    public function toBase64(): string
    {
        return base64_encode($this->toString());
    }

    /**
     * Get contents Data URL.
     *
     * @return string
     */
    public function toDataUrl(): string
    {
        return 'data:' . $this->getMime() . ';base64,' . $this->toBase64();
    }

    /**
     * Get contents as hashed.
     *
     * @param  string $algo
     * @return string
     */
    public function toHash(string $algo = 'md5'): string
    {
        return hash($algo, $this->toString());
    }
}
