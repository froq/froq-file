<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file\glob;

/**
 * Glob class for files.
 *
 * @package froq\file\glob
 * @object  froq\file\glob\FileGlob
 * @author  Kerem Güneş
 * @since   6.1
 */
class FileGlob extends Glob
{
    /**
     * @override
     */
    public function __construct(string $pattern, int $flags = 0, string $fileClass = null, string $infoClass = null)
    {
        parent::__construct($pattern, $flags, $fileClass, $infoClass);

        /** @var SplFileInfo $info */
        $this->filter(fn($info) => $info->isFile());
    }
}
