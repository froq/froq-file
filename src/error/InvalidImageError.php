<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\error;

/**
 * @package froq\file\error
 * @class   froq\file\error\InvalidImageError
 * @author  Kerem Güneş
 * @since   7.0
 */
class InvalidImageError extends \froq\file\FileSystemError
{
    /**
     * @override
     */
    public function __construct(mixed ...$arguments)
    {
        $arguments['message'] ??= 'Invalid image';
        $arguments['code']      = parent::INVALID_IMAGE;

        parent::__construct(...$arguments);
    }
}
