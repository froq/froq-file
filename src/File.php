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
    private $dir;

    private $name;
    private $nameTmp;
    private $type;
    private $size;
    private $extension;
    private $error;

    public function __construct(string $dir = null, array $data = null)
    {
        $dir && $this->setDir($dir);
        if (!empty($data)) {
            isset($data['name']) &&
                $this->setName($data['name']);
            isset($data['temp_name']) &&
                $this->setNameTmp($data['temp_name']);
            isset($data['type']) &&
                $this->setType($data['type']) &&
                $this->setExtension($data['type']);
            isset($data['size']) &&
                $this->setSize($data['size']);
            $this->error = $data['error'] ?? null;
        }
    }

    final public function setDir(string $dir): self
    {
        $this->dir = $dir;
        if (!is_dir($this->dir)) {
            mkdir($dir, 0644, true);
        }
        return $this;
    }
    final public function getDir()
    {
        return $this->dir;
    }

    final public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
    final public function getName()
    {
        return $this->name;
    }

    final public function setNameTmp(string $nameTmp): self
    {
        $this->nameTmp = $nameTmp;
        return $this;
    }
    final public function getNameTmp()
    {
        return $this->nameTmp;
    }

    final public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }
    final public function getType()
    {
        return $this->type;
    }

    final public function setExtension(string $type): self
    {
        $this->extension = Mime::getExtension($type);
        return $this;
    }
    final public function getExtension()
    {
        return $this->extension;
    }

    final public function setSize(int $size): self
    {
        $this->size = $size;
        return $this;
    }
    final public function getSize()
    {
        return $this->size;
    }

    final public function hasError(): bool
    {
        return ($this->error == UPLOAD_ERR_OK);
    }

    final public function getSourceFile()
    {
        return $this->nameTmp;
    }

    final public function getTargetFile()
    {
        $src = $this->getSourceFile();
        if ($src) {
            return sprintf('%s/%s.%s', $this->dir, $this->name, $this->extension);
        }
    }

    abstract public function save(): bool;
    abstract public function saveAs(string $target): bool;
}
