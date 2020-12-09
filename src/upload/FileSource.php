<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file\upload;

use froq\file\upload\{AbstractSource, UploadException};

/**
 * File Source
 *
 * Represents an uploaded file source entity which aims to with files in OOP style.
 *
 * @package froq\file\upload
 * @object  froq\file\upload\FileSource
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0, 5.0 Moved to upload directory, derived from FileUploader.
 */
class FileSource extends AbstractSource
{
    /**
     * @inheritDoc froq\file\upload\AbstractSource
     */
    public final function save(): string
    {
        $source = $this->getSource();
        $destination = $this->getDestination();

        $this->overwriteCheck($destination);

        copy($source, $destination)
            || throw new UploadException('Failed saving file [error: %s]', '@error');

        return $destination;
    }

    /**
     * @inheritDoc froq\file\upload\AbstractSource
     */
    public final function saveAs(string $name, string $appendix = null): string
    {
        $source = $this->getSource();
        $destination = $this->getDestination($name, $appendix);

        $this->overwriteCheck($destination);

        copy($source, $destination)
            || throw new UploadException('Failed saving file [error: %s]', '@error');

        return $destination;
    }

    /**
     * @inheritDoc froq\file\upload\AbstractSource
     */
    public final function move(): string
    {
        $source = $this->getSource();
        $destination = $this->getDestination();

        $this->overwriteCheck($destination);

        copy($source, $destination)
            || throw new UploadException('Failed moving file [error: %s]', '@error');

        unlink($source); // Remove source instantly.

        return $destination;
    }

    /**
     * @inheritDoc froq\file\upload\AbstractSource
     */
    public final function moveAs(string $name, string $appendix = null): string
    {
        $source = $this->getSource();
        $destination = $this->getDestination($name, $appendix);

        $this->overwriteCheck($destination);

        copy($source, $destination)
            || throw new UploadException('Failed moving file [error: %s]', '@error');

        unlink($source); // Remove source instantly.

        return $destination;
    }

    /**
     * @inheritDoc froq\file\upload\AbstractSource
     */
    public final function clear(bool $force = false): void
    {
        if ($force || $this->options['clearSource']) {
            unlink($this->getSource());
        }
    }
}