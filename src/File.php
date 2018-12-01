<?php
/**
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Copyright (c) 2015 Kerem Güneş
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
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
