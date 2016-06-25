<?php
declare(strict_types=1);

namespace Froq\File\Upload;

use Froq\File\{File as FileBase, FileException};

final class File extends FileBase
{
    final public function save(): bool
    {
        return move_uploaded_file($this->getSourceFile(), $this->getTargetFile());
    }

    final public function saveAs(string $target): bool
    {
        return move_uploaded_file($this->getSourceFile(), $target);
    }
}
