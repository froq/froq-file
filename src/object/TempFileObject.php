<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file\object;

use froq\file\object\FileObject;

/**
 * Temp File Object.
 *
 * @package froq\file\object
 * @object  froq\file\object\TempFileObject
 * @author  Kerem Güneş <k-gun@mail.com>
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
