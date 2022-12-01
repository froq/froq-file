<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\upload;

/**
 * @package froq\file\upload
 * @class   froq\file\upload\UploadError
 * @author  Kerem Güneş
 * @since   4.0, 5.0
 */
class UploadError extends \froq\file\FileError
{
    /** Error codes. */
    public const INTERNAL                     = 1,
                 NO_VALID_FILE                = 2,
                 NO_VALID_SOURCE              = 3,
                 OPTION_EMPTY                 = 4,
                 OPTION_SIZE_EXCEEDED         = 5,
                 OPTION_EMPTY_EXTENSION       = 6,
                 OPTION_NOT_ALLOWED_MIME      = 7,
                 OPTION_NOT_ALLOWED_EXTENSION = 8,
                 OPTION_NOT_ALLOWED_OVERWRITE = 9,
                 DIRECTORY_EMPTY              = 10,
                 DIRECTORY_ERROR              = 11;

    /** Message map by code. */
    public const MESSAGES = [
        0 => '',
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk',
        8 => 'A PHP extension stopped the file upload'
    ];

    /**
     * Convert given code to message.
     *
     * @param  int $code
     * @return string
     * @since  6.0
     */
    public static function toMessage(int $code): string
    {
        return self::MESSAGES[$code] ?? 'Unknown error';
    }
}
