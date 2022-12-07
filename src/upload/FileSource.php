<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\upload;

/**
 * An file class for working with files.
 *
 * @package froq\file\upload
 * @class   froq\file\upload\FileSource
 * @author  Kerem Güneş
 * @since   3.0, 5.0
 */
class FileSource extends AbstractSource
{
    /**
     * @inheritDoc froq\file\upload\AbstractSource
     */
    public final function save(string $path = null, string $appendix = null): string
    {
        $source = $this->getSource();
        $target = $this->prepareTarget($path, $appendix);

        $this->overwriteCheck($target);

        if (copy($source, $target)) {
            return $target;
        }

        throw new FileSourceException('Failed saving file [error: @error]');
    }

    /**
     * @inheritDoc froq\file\upload\AbstractSource
     */
    public final function move(string $path = null, string $appendix = null): string
    {
        $source = $this->getSource();
        $target = $this->prepareTarget($path, $appendix);

        $this->overwriteCheck($target);

        if (rename($source, $target)) {
            return $target;
        }

        throw new FileSourceException('Failed moving file [error: @error]');
    }

    /**
     * @inheritDoc froq\file\upload\AbstractSource
     */
    public final function clear(bool $force = false): void
    {
        if ($force || $this->options['clearSource']) {
            $file = $this->getSource();
            is_file($file) && unlink($file);
        }
    }

    /**
     * @inheritDoc froq\common\interface\Stringable
     */
    public final function toString(): string
    {
        return file_get_contents($this->getSource());
    }
}
