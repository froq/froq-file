<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file\upload;

use froq\file\FileError;

/**
 * Upload Error.
 *
 * @package froq\file\upload
 * @object  froq\file\upload\UploadError
 * @author  Kerem Güneş
 * @since   4.0, 5.0
 */
class UploadError extends FileError
{
    /**
     * Codes.
     * @const int
     */
    public final const INTERNAL                     = 1,
                       NO_VALID_FILE                = 2,
                       NO_VALID_SOURCE              = 3,
                       OPTION_EMPTY                 = 4,
                       OPTION_SIZE_EXCEEDED         = 5,
                       OPTION_EMPTY_EXTENSION       = 6,
                       OPTION_NOT_ALLOWED_TYPE      = 7,
                       OPTION_NOT_ALLOWED_EXTENSION = 8,
                       OPTION_NOT_ALLOWED_OVERWRITE = 9,
                       DIRECTORY_EMPTY              = 10,
                       DIRECTORY_ERROR              = 11;

    /**
     * Messages.
     * @const array
     */
    public final const MESSAGES = [
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk',
        8 => 'A PHP extension stopped the file upload'
    ];
}
