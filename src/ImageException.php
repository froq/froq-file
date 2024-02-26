<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * @package froq\file
 * @class   froq\file\ImageException
 * @author  Kerem Güneş
 * @since   7.0
 */
class ImageException extends FileException
{
    public static function forInvalidImageFile(string $message): static
    {
        return new static($message);
    }

    public static function forInvalidImageData(): static
    {
        // Same error as imagecreatefromstring() gives.
        return new static('Data is not in a recognized format');
    }
}
