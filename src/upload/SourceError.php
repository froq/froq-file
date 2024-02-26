<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\upload;

/**
 * @package froq\file\upload
 * @class   froq\file\upload\SourceError
 * @author  Kerem Güneş
 * @since   4.0, 5.0, 7.0
 */
class SourceError extends \froq\file\FileSystemError
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

    /** PHP Error codes.
     * @see https://www.php.net/manual/en/features.file-upload.errors.php */
    public const E_OK         = UPLOAD_ERR_OK,
                 E_INI_SIZE   = UPLOAD_ERR_INI_SIZE,
                 E_FORM_SIZE  = UPLOAD_ERR_FORM_SIZE,
                 E_PARTIAL    = UPLOAD_ERR_PARTIAL,
                 E_NO_FILE    = UPLOAD_ERR_NO_FILE,
                 E_NO_TMP_DIR = UPLOAD_ERR_NO_TMP_DIR,
                 E_CANT_WRITE = UPLOAD_ERR_CANT_WRITE,
                 E_EXTENSION  = UPLOAD_ERR_EXTENSION;


    /**
     * Convert given code to message.
     *
     * @param  int $code
     * @return string
     */
    public static function codeToMessage(int $code): string
    {
        return match ($code) {
            default            => 'Unknown error', // 0 won't work.
            self::E_INI_SIZE   => 'Uploaded file exceeds upload_max_filesize directive in php.ini',
            self::E_FORM_SIZE  => 'Uploaded file exceeds MAX_FILE_SIZE directive in HTML form',
            self::E_PARTIAL    => 'Uploaded file was only partially uploaded',
            self::E_NO_FILE    => 'No file was uploaded',
            self::E_NO_TMP_DIR => 'Missing a temporary folder',
            self::E_CANT_WRITE => 'Failed to write file to disk',
            self::E_EXTENSION  => 'A PHP extension stopped the file upload',
        };
    }

    /**
     * Create for given code.
     *
     * @param  int $code
     * @return static
     */
    public static function forCode(int $code): static
    {
        return new static(self::codeToMessage($code), code: $code);
    }
}
