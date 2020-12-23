<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file;

use froq\common\Error;

/**
 * File Error.
 *
 * @package froq\file
 * @object  froq\file\FileError
 * @author  Kerem Güneş
 * @since   3.0
 */
class FileError extends Error
{
    /**
     * Error codes.
     * @const int
     */
    public const DIRECTORY_GIVEN = 1,
                 INVALID_PATH    = 2,
                 NO_FILE         = 3,
                 NO_PERMISSION   = 4;
}
