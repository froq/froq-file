<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file;

use froq\file\{AbstractUploader, UploaderException};

/**
 * File Uploader.
 *
 * @package froq\file
 * @object  froq\file\FileUploader
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0
 */
final class FileUploader extends AbstractUploader
{
    /**
     * @inheritDoc froq\file\Uploader
     */
    public function save(): string
    {
        $source = $this->getSource();
        $destination = $this->getDestination();

        $ok =@ copy($source, $destination);
        if (!$ok) {
            throw new UploaderException('Cannot save file [error: %s]', ['@error']);
        }

        return $destination;
    }

    /**
     * @inheritDoc froq\file\Uploader
     */
    public function saveAs(string $name, string $nameAppendix = null): string
    {
        if ($name == '') {
            throw new UploaderException('Name must not be empty');
        }

        $source = $this->getSource();
        $destination = $this->getDestination($name, $nameAppendix);

        $ok =@ copy($source, $destination);
        if (!$ok) {
            throw new UploaderException('Cannot save file [error: %s]', ['@error']);
        }

        return $destination;
    }

    /**
     * @inheritDoc froq\file\Uploader
     */
    public function move(): string
    {
        $source = $this->getSource();
        $destination = $this->getDestination();

        $ok =@ copy($source, $destination);
        if (!$ok) {
            throw new UploaderException('Cannot move file [error: %s]', ['@error']);
        }

        // Remove source instantly.
        @ unlink($source);

        return $destination;
    }

    /**
     * @inheritDoc froq\file\Uploader
     */
    public function moveAs(string $name, string $nameAppendix = null): string
    {
        if ($name == '') {
            throw new UploaderException('Name must not be empty');
        }

        $source = $this->getSource();
        $destination = $this->getDestination($name, $nameAppendix);

        $ok =@ copy($source, $destination);
        if (!$ok) {
            throw new UploaderException('Cannot move file [error: %s]', ['@error']);
        }

        // Remove source instantly.
        @ unlink($source);

        return $destination;
    }

    /**
     * @inheritDoc froq\file\Uploader
     */
    public function clear(bool $force = false): void
    {
        if (!$force) {
            if ($this->options['clearSource']) {
                @ unlink($this->getSource());
            }
        } else {
            @ unlink($this->getSource());
        }
    }
}
