<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\upload;

use froq\file\Path;

/**
 * Source file entry for uploaded or given file sources.
 *
 * @package froq\file\upload
 * @class   froq\file\upload\SourceFile
 * @author  Kerem Güneş
 * @since   7.0
 * @internal
 */
class SourceFile extends \Item
{
    /** Path object. */
    public readonly Path $path;

    /** Default fields. */
    private array $fields = [
        'file', 'name', 'extension',
        'size', 'mime', 'error',
    ];

    /**
     * @override
     */
    public function __construct(array|string $file)
    {
        if (is_array($file)) {
            // From $_FILES global.
            $tmp = xarray($file)->default($this->fields);

            // Swap to related fields.
            $tmp->swap('type', 'mime');
            $tmp->swap('tmp_name', 'file');

            if ($file = strip($tmp->file)) {
                $this->path = new Path($tmp->file);
                $tmp['name'] ??= $this->path->getBaseName();
                $tmp['extension'] = $this->path->getExtension();

                if ($this->path->exists()) {
                    $tmp['size'] ??= $this->path->getSize();
                    $tmp['mime'] ??= $this->path->getMime();
                }
            }
        } else {
            // From direct path (relative or absolute).
            $tmp = xarray(['file' => $file])->default($this->fields);

            $this->path = new Path($file);
            $tmp['name'] = $this->path->getBaseName();
            $tmp['extension'] = $this->path->getExtension();

            if ($this->path->exists()) {
                $tmp['size'] = $this->path->getSize();
                $tmp['mime'] = $this->path->getMime();
            }
        }

        // Nullify empty fields & include defaults.
        $info = $tmp->apply(fn($v) => $v !== '' && $v !== null ? $v : null);

        parent::__construct($info);
    }

    /**
     * @override
     */
    public function __debugInfo(): array
    {
        return ['path' => $this->path, ...$this->toArray()];
    }
}
