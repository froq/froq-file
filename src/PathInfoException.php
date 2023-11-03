<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * @package froq\file
 * @class   froq\file\PathInfoException
 * @author  Kerem Güneş
 * @since   7.0
 */
class PathInfoException extends FileSystemException
{
    public static function forInvalidPath(string $message): static
    {
        return new static(
            'Invalid path: ' . $message,
            cause: new error\InvalidPathError(reduce: true)
        );
    }

    public static function forNoOpsGiven(): static
    {
        return new static('No ops given [use one or combine read,write,execute ops]');
    }
}
