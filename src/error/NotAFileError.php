<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\error;

/**
 * @package froq\file\error
 * @class   froq\file\error\NotAFileError
 * @author  Kerem Güneş
 * @since   7.0
 */
class NotAFileError extends \froq\file\FileSystemError
{
    /**
     * @override
     */
    public function __construct(mixed ...$arguments)
    {
        $arguments['message'] ??= 'Not a file';
        $arguments['code']      = parent::NOT_A_FILE;

        parent::__construct(...$arguments);
    }
}
