<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * Path object list class.
 *
 * @package froq\file
 * @class   froq\file\PathObjectList
 * @author  Kerem Güneş
 * @since   7.0
 * @internal
 */
class PathObjectList extends \ItemList
{
    /**
     * @override
     */
    public function __construct(iterable $items = [])
    {
        $types = match (true) {
            $this instanceof DirectoryList => [Directory::class],
            $this instanceof FileList      => [File::class],
            $this instanceof LinkList      => [Directory::class, File::class],
            $this instanceof PathList      => [Path::class],
            default                        => []
        };

        parent::__construct($items, type: ['string', ...$types]);
    }
}
