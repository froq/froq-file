<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\error;

/**
 * @package froq\file\error
 * @class   froq\file\error\NotADirectoryError
 * @author  Kerem Güneş
 * @since   7.0
 */
class NotADirectoryError extends \froq\file\FileSystemError
{
    /**
     * @override
     */
    public function __construct(mixed ...$arguments)
    {
        $arguments['message'] ??= 'Not a directory';
        $arguments['code']      = parent::NOT_A_DIRECTORY;

        parent::__construct(...$arguments);
    }
}
