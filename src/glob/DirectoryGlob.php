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
    public function __construct(string $pattern, int $flags = 0)
    {
        parent::__construct($pattern, $flags);

        /** @var SplFileInfo $info */
        $this->filter(fn($info) => $info->isDir());
    }
}
