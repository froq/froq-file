<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file;

/**
 * @package froq\file
 * @object  froq\file\FileError
 * @author  Kerem Güneş
 * @since   3.0
 */
class FileError extends \froq\common\Error
{
    /**
     * Codes.
     * @const int
     */
    public final const DIRECTORY            = 1,
                       NO_FILE_EXISTS       = 2,
                       NO_ACCESS_PERMISSION = 3,
                       NO_VALID_PATH        = 4;
}
