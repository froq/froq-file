<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file\glob;

/**
 * Glob class for directories.
 *
 * @package froq\file\glob
 * @object  froq\file\glob\DirectoryGlob
 * @author  Kerem Güneş
 * @since   6.1
 */
class DirectoryGlob extends Glob
{
    /**
     * @override
     */
    public function __construct(string $pattern, int $flags = 0, string $fileClass = null, string $infoClass = null)
    {
        parent::__construct($pattern, $flags, $fileClass, $infoClass);

        // Filter directories only.
        $this->filter(fn(\SplFileInfo $info): bool => $info->isDir());
    }
}
