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
    private $data = [];

    // bunlari koyalim
    private $name;
    private $size;
    private $mime;
    private $extension;

    public function __construct(string $dir = null, array $data = null)
    {
        $dir && $this->setDir($dir);
        $data && $this->setData($data);
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

    final public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }
    final public function getData(): array
    {
        return $this->data;
    }
    final public function getDataValue(string $key)
    {
        return $this->data['name'] ?? null;
    }

    final public function getSourceFile()
    {
        return $this->data['temp_name'] ?? null;
    }

    final public function getTargetFile()
    {
        $src = $this->getSourceFile();
        if ($src) {
            return sprintf('%s/%s.%s', $this->dir,
                $this->getDataValue('name'), $this->getDataValue('extension'));
        }
    }

    abstract public function save(): bool;
    abstract public function saveAs(string $target): bool;
}
