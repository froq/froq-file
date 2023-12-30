<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * @package froq\file
 * @class   froq\file\PathException
 * @author  Kerem Güneş
 * @since   7.0, 7.1
 */
class PathException extends PathInfoException
{
    public static function forNoFile(string $path): static
    {
        return new static(
            'No such file or directory: ' . $path,
            cause: new error\NoFileError(reduce: true)
        );
    }

    public static function forNoPartsGiven(): static
    {
        return new static('No parts given, provide at least 1 part');
    }
}
