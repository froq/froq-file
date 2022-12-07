<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\object;

/**
 * A class, for working with temp files.
 *
 * @package froq\file\object
 * @class   froq\file\object\TempFileObject
 * @author  Kerem Güneş
 * @since   5.0
 */
class TempFileObject extends FileObject
{
    /**
     * Constructor.
     *
     * @param string|null $mime
     */
    public final function __construct(string $mime = null)
    {
        parent::__construct(tmpfile(), $mime);
    }
}
