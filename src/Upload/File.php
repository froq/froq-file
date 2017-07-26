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

namespace Froq\File\Upload;

use Froq\File\{File as FileBase, FileException};

/**
 * @package    Froq
 * @subpackage Froq\File\Upload
 * @object     Froq\File\Upload\File
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class File extends FileBase
{
    /**
     * Save.
     * @return bool
     */
    public final function save(): bool
    {
        return copy($this->getSourceFile(), $this->getTargetFile());
    }

    /**
     * Save as.
     * @param  string $name
     * @return bool
     */
    public final function saveAs(string $name): bool
    {
        return copy($this->getSourceFile(), "{$this->directory}/{$name}.{$this->extension}");
    }

    /**
     * Clear.
     * @return void
     */
    public final function clear()
    {
        @unlink($this->getSourceFile());
    }
}
