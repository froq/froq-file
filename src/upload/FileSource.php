<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file\upload;

use froq\file\upload\{AbstractSource, UploadException};

/**
 * File Source
 *
 * Represents an uploaded file entity which aims to with files in OOP style with a few safety options.
 *
 * @package froq\file\upload
 * @object  froq\file\upload\FileSource
 * @author  Kerem Güneş
 * @since   3.0, 5.0 Moved to upload directory, derived from FileUploader.
 */
class FileSource extends AbstractSource
{
    /**
     * @inheritDoc froq\file\upload\AbstractSource
     */
    public final function save(string $name = null, string $appendix = null): string
    {
        $source = $this->getSource();
        $target = $this->prepareTarget($name, $appendix);

        $this->overwriteCheck($target);

        if (copy($source, $target)) {
            return $target;
        }

        throw new UploadException('Failed saving file [error: %s]', '@error');
    }

    /**
     * @inheritDoc froq\file\upload\AbstractSource
     */
    public final function move(string $name = null, string $appendix = null): string
    {
        $source = $this->getSource();
        $target = $this->prepareTarget($name, $appendix);

        $this->overwriteCheck($target);

        if (rename($source, $target)) {
            return $target;
        }

        throw new UploadException('Failed moving file [error: %s]', '@error');
    }

    /**
     * @inheritDoc froq\file\upload\AbstractSource
     */
    public final function clear(bool $force = false): void
    {
        if ($force || $this->options['clearSource']) {
            is_file($file = $this->getSource()) && unlink($file);
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
