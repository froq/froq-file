<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\error;

/**
 * @package froq\file\error
 * @class   froq\file\error\NoFileError
 * @author  Kerem Güneş
 * @since   7.0
 */
class NoFileError extends \froq\file\FileSystemError
{
    /**
     * @override
     */
    public function __construct(mixed ...$arguments)
    {
        $arguments['message'] ??= 'No such file or directory';
        $arguments['code']      = parent::NO_FILE;

        parent::__construct(...$arguments);
    }
}
