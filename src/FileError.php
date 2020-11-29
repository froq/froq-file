<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file;

use froq\common\Error;

/**
 * File Error.
 *
 * @package froq\file
 * @object  froq\file\FileError
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0
 */
class FileError extends Error
{
    /**
     * Errors.
     * @const int
     */
    public const DIRECTORY_GIVEN = 1,
                 NO_VALID_PATH   = 2,
                 NO_SUCH_FILE    = 3,
                 NO_PERMISSION   = 4;
}
