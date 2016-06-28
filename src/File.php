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
     * Dir.
     * @var string
     */
    protected $dir;

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
     * Construct.
     * @param string|null $dir
     * @param array|null  $data
     */
    public function __construct(string $dir = null, array $data = null)
    {
        $dir && $this->setDir($dir);
        if (!empty($data)) {
            isset($data['name']) &&
                $this->setName($data['name']);
            isset($data['tmp_name']) &&
                $this->setNameTmp($data['tmp_name']);
            isset($data['type']) &&
                $this->setType($data['type']) &&
                $this->setExtension($data['type']);
            isset($data['size']) &&
                $this->setSize($data['size']);
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
     * Set dir.
     * @param  string $dir
     * @return self
     */
    final public function setDir(string $dir): self
    {
        $this->dir = $dir;
        if (!is_dir($this->dir)) {
            mkdir($dir, 0644, true);
        }

        return $this;
    }

    /**
     * Get dir.
     * @return string|null
     */
    final public function getDir()
    {
        return $this->dir;
    }

    /**
     * Set name.
     * @param  string $name
     * @return self
     */
    final public function setName(string $name): self
    {
        $this->name = pathinfo($name, PATHINFO_FILENAME);

        return $this;
    }

    /**
     * Get name.
     * @return string|null
     */
    final public function getName()
    {
        return $this->name;
    }

    /**
     * Set name tmp.
     * @param  string $nameTmp
     * @return self
     */
    final public function setNameTmp(string $nameTmp): self
    {
        $this->nameTmp = $nameTmp;

        return $this;
    }

    /**
     * Get name tmp.
     * @return string|null
     */
    final public function getNameTmp()
    {
        return $this->nameTmp;
    }

    /**
     * Set type.
     * @param  string $type
     * @return self
     */
    final public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     * @return string|null
     */
    final public function getType()
    {
        return $this->type;
    }

    /**
     * Set extension.
     * @param  string $type
     * @return self
     */
    final public function setExtension(string $type): self
    {
        $this->extension = Mime::getExtension($type);

        return $this;
    }

    /**
     * Get extension.
     * @return string|null
     */
    final public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Set size.
     * @param  int $size
     * @return self
     */
    final public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size.
     * @return int
     */
    final public function getSize()
    {
        return $this->size;
    }

    /**
     * Is ok.
     * @return bool
     */
    final public function isOK(): bool
    {
        return ($this->error == UPLOAD_ERR_OK);
    }

    /**
     * Get source file.
     * @return string|null
     */
    final public function getSourceFile()
    {
        return $this->nameTmp;
    }

    /**
     * Get target file.
     * @return string|null
     */
    final public function getTargetFile()
    {
        $src = $this->getSourceFile();
        if ($src) {
            return sprintf('%s/%s.%s', $this->dir, $this->name, $this->extension);
        }
    }

    /**
     * Save.
     * @return bool
     */
    abstract public function save(): bool;

    /**
     * Save as.
     * @param  string $name
     * @return bool
     */
    abstract public function saveAs(string $name): bool;

    /**
     * Clear.
     * @return void
     */
    abstract public function clear();
}
