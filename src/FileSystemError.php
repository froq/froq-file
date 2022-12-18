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
                 NO_FILE         = 2,
                 NO_PERMISSION   = 3,
                 NOT_A_DIRECTORY = 4,
                 NOT_A_FILE      = 5,
                 NOT_A_LINK      = 6;
}
