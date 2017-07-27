<?php
/**
 * Copyright (c) 2016 Kerem Güneş
 *    <k-gun@mail.com>
 *
 * GNU General Public License v3.0
 *    <http://www.gnu.org/licenses/gpl-3.0.txt>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Froq\File;

/**
 * @package    Froq
 * @subpackage Froq\File
 * @object     Froq\File\File
 * @author     Kerem Güneş <k-gun@mail.com>
 */
abstract class File
{
    /**
     * Directory.
     * @var string
     */
    protected $directory;

    /**
     * Name.
     * @var string
     */
    protected $name;

    /**
     * Tmp name.
     * @var string
     */
    protected $nameTmp;

    /**
     * Type.
     * @var string
     */
    protected $type;

    /**
     * Size.
     * @var int
     */
    protected $size;

    /**
     * Extension.
     * @var string
     */
    protected $extension;

    /**
     * Error.
     * @var int
     */
    protected $error;

    /**
     * Constructor.
     * @param string $directory
     * @param array|null  $data
     */
    public function __construct(string $directory, array $data = null)
    {
        $this->setDirectory($directory);

        if (!empty($data)) {
            isset($data['name']) &&
                $this->setName($data['name']);
            isset($data['type']) &&
                $this->setType($data['type']) && $this->setExtension($data['type']);
            isset($data['size']) &&
                $this->setSize($data['size']);

            if (isset($data['tmp_name'])) {
                $this->setNameTmp($data['tmp_name']);
            } elseif (isset($data['source'])) {
                $this->setNameTmp($data['source']);
            }

            $this->error = $data['error'] ?? null;
        }
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->clear();
    }

    /**
     * Set directory.
     * @param  string $directory
     * @return self
     */
    public final function setDirectory(string $directory): self
    {
        $this->directory = $directory;
        if (empty($this->directory)) {
            throw new FileException('File directory cannot be empty!');
        }

        if (!is_dir($this->directory)) {
            $ok =@ mkdir($directory, 0644, true);
            if (!$ok) {
                throw new FileException(sprintf('Cannot make directory [%s]!',
                    strtolower(error_get_last()['message'] ?? '')));
            }
        }

        return $this;
    }

    /**
     * Get directory.
     * @return ?string
     */
    public final function getDirectory(): ?string
    {
        return $this->directory;
    }

    /**
     * Set name.
     * @param  string $name
     * @return self
     */
    public final function setName(string $name): self
    {
        $this->name = pathinfo($name, PATHINFO_FILENAME);

        return $this;
    }

    /**
     * Get name.
     * @return ?string
     */
    public final function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set name tmp.
     * @param  string $nameTmp
     * @return self
     */
    public final function setNameTmp(string $nameTmp): self
    {
        $this->nameTmp = $nameTmp;

        return $this;
    }

    /**
     * Get name tmp.
     * @return ?string
     */
    public final function getNameTmp(): ?string
    {
        return $this->nameTmp;
    }

    /**
     * Set type.
     * @param  string $type
     * @return self
     */
    public final function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     * @return ?string
     */
    public final function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Set extension.
     * @param  string $type
     * @return self
     */
    public final function setExtension(string $type): self
    {
        $this->extension = Mime::getExtensionByType($type);

        return $this;
    }

    /**
     * Get extension.
     * @return ?string
     */
    public final function getExtension(): ?string
    {
        return $this->extension;
    }

    /**
     * Set size.
     * @param  int $size
     * @return self
     */
    public final function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size.
     * @return ?int
     */
    public final function getSize(): ?string
    {
        return $this->size;
    }

    /**
     * Get source file.
     * @return ?string
     */
    public final function getSourceFile(): ?string
    {
        return $this->nameTmp;
    }

    /**
     * Get target file.
     * @return ?string
     */
    public final function getTargetFile(): ?string
    {
        $sourceFile = $this->getSourceFile();
        if ($sourceFile != null) {
            return sprintf('%s/%s.%s', $this->directory, $this->name, $this->extension);
        }

        return null;
    }

    /**
     * Ok.
     * @return bool
     */
    public final function ok(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    /**
     * Save.
     * @return bool
     * @throws Froq\File\FileException
     */
    abstract public function save(): bool;

    /**
     * Save as.
     * @param  string $name
     * @return bool
     * @throws Froq\File\FileException
     */
    abstract public function saveAs(string $name): bool;

    /**
     * Move.
     * @return bool
     * @throws Froq\File\FileException
     */
    abstract public function move(): bool;

    /**
     * Move as.
     * @param  string $name
     * @return bool
     * @throws Froq\File\FileException
     */
    abstract public function moveAs(string $name): bool;

    /**
     * Clear.
     * @return void
     */
    abstract public function clear(): void;
}
