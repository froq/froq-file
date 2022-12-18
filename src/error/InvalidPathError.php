<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\error;

/**
 * @package froq\file\error
 * @class   froq\file\error\InvalidPathError
 * @author  Kerem Güneş
 * @since   7.0
 */
class InvalidPathError extends \froq\file\FileSystemError
{
    /**
     * @override
     */
    public function __construct(mixed ...$arguments)
    {
        $arguments['message'] ??= 'Invalid path';
        $arguments['code']      = parent::INVALID_PATH;

        parent::__construct(...$arguments);
    }
}
