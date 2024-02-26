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
    public const NO_FILE         = 1,
                 NO_PERMISSION   = 2,
                 NOT_A_DIRECTORY = 3,
                 NOT_A_FILE      = 4,
                 NOT_A_LINK      = 5,
                 INVALID_IMAGE   = 10,
                 INVALID_PATH    = 11,
                 INVALID_URL     = 12;
}
