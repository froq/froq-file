<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\glob;

/**
 * Glob class for directories.
 *
 * @package froq\file\glob
 * @class   froq\file\glob\DirectoryGlob
 * @author  Kerem Güneş
 * @since   6.1
 */
class DirectoryGlob extends Glob
{
    /**
     * @param mixed ...$arguments Same as Glob.__construct() method.
     * @override
     */
    public function __construct(mixed ...$arguments)
    {
        parent::__construct(...$arguments);

        // Filter directories only.
        $this->filter(fn(\SplFileInfo $info): bool => $info->isDir());
    }
}
