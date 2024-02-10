<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * @package froq\file
 * @class   froq\file\FileSystemError
 * @author  Kerem Güneş
 * @since   7.0
 */
class FileSystemError extends \froq\common\Error
{
    /** Error codes. */
    public const INVALID_PATH    = 1,
                 INVALID_URL     = 2,
                 NO_FILE         = 3,
                 NO_PERMISSION   = 4,
                 NOT_A_DIRECTORY = 5,
                 NOT_A_FILE      = 6,
                 NOT_A_LINK      = 7;
}
