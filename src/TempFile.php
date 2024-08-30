<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * File class for working with temp files.
 *
 * @package froq\file
 * @class   froq\file\TempFile
 * @author  Kerem Güneş
 * @since   7.0
 */
class TempFile extends File
{
    /**
     * @override
     */
    public function __construct(bool $drop = true, array $options = null)
    {
        // For constructor.
        $options['temp']       = true;
        $options['tempDrop']   = $options['drop'] ?? $drop;
        $options['tempPrefix'] = $options['prefix'] ?? null;

        parent::__construct('', $options);
    }
}
