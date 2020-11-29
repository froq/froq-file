<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file;

use froq\file\FileError;

/**
 * Uploader Error.
 *
 * @package froq\file
 * @object  froq\file\UploaderError
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
final class UploaderError extends FileError
{
    /**
     * Errors.
     * @const int
     */
    public const INTERNAL                     = 1,
                 NO_VALID_FILE                = 2,
                 NO_VALID_SOURCE              = 3,
                 OPTION_EMPTY                 = 4,
                 OPTION_SIZE_EXCEEDED         = 5,
                 OPTION_EMPTY_EXTENSION       = 6,
                 OPTION_NOT_ALLOWED_TYPE      = 7,
                 OPTION_NOT_ALLOWED_EXTENSION = 8,
                 DIRECTORY_EMPTY              = 10,
                 DIRECTORY_ERROR              = 11;

    /**
     * Messages.
     * @var array
     */
    public const MESSAGES = [
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk',
        8 => 'A PHP extension stopped the file upload'
    ];
}
