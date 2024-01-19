<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * @package froq\file
 * @class   froq\file\FileException
 * @author  Kerem Güneş
 * @since   7.0
 */
class FileException extends PathObjectException
{
    public static function forInvalidTypeOption(mixed $type): static
    {
        return new static("Option @type must be 'file' or 'image', %q given", $type);
    }
}
