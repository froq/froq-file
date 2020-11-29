<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file\upload;

use froq\file\upload\{AbstractUploader, UploadException};

/**
 * File Uploader.
 *
 * @package froq\file\upload
 * @object  froq\file\upload\FileUploader
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0, 5.0 Moved to upload directory.
 */
final class FileUploader extends AbstractUploader
{
    /**
     * @inheritDoc froq\file\upload\AbstractUploader
     */
    public function save(): string
    {
        $source = $this->getSource();
        $destination = $this->getDestination();

        $ok = copy($source, $destination);
        if (!$ok) {
            throw new UploadException('Cannot save file [error: %s]', '@error');
        }

        return $destination;
    }

    /**
     * @inheritDoc froq\file\upload\AbstractUploader
     */
    public function saveAs(string $name, string $nameAppendix = null): string
    {
        if ($name == '') {
            throw new UploadException('Name must not be empty');
        }

        $source = $this->getSource();
        $destination = $this->getDestination($name, $nameAppendix);

        $ok = copy($source, $destination);
        if (!$ok) {
            throw new UploadException('Cannot save file [error: %s]', '@error');
        }

        return $destination;
    }

    /**
     * @inheritDoc froq\file\upload\AbstractUploader
     */
    public function move(): string
    {
        $source = $this->getSource();
        $destination = $this->getDestination();

        $ok = copy($source, $destination);
        if (!$ok) {
            throw new UploadException('Cannot move file [error: %s]', '@error');
        }

        unlink($source); // Remove source instantly.

        return $destination;
    }

    /**
     * @inheritDoc froq\file\upload\AbstractUploader
     */
    public function moveAs(string $name, string $nameAppendix = null): string
    {
        if ($name == '') {
            throw new UploadException('Name must not be empty');
        }

        $source = $this->getSource();
        $destination = $this->getDestination($name, $nameAppendix);

        $ok = copy($source, $destination);
        if (!$ok) {
            throw new UploadException('Cannot move file [error: %s]', '@error');
        }

        unlink($source); // Remove source instantly.

        return $destination;
    }

    /**
     * @inheritDoc froq\file\upload\AbstractUploader
     */
    public function clear(bool $force = false): void
    {
        if (!$force) {
            if ($this->options['clearSource']) {
                unlink($this->getSource());
            }
        } else {
            unlink($this->getSource());
        }
    }
}
