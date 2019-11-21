<?php
/**
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Copyright (c) 2015 Kerem Güneş
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace froq\file;

use froq\interfaces\Arrayable;

/**
 * Abstract File.
 * @package froq\file
 * @object  froq\file\AbstractFile
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
abstract class AbstractFile implements Arrayable
{
    /**
     * Name.
     * @var string
     */
    protected string $name;

    /**
     * Type.
     * @var string
     */
    protected string $type;

    /**
     * Size.
     * @var int
     */
    protected int $size;

    /**
     * Extension.
     * @var string
     */
    protected string $extension;

    /**
     * Directory.
     * @var string
     */
    protected string $directory;

    /**
     * Source.
     * @var string
     */
    protected string $source;

    /**
     * Options.
     * @var array
     */
    protected array $options = [
        'hash'                 => null, // Rand, file, fileName (default='').
        'hashLength'           => null, // 8, 16, 32 or 40 (default=16).
        'maxFileSize'          => null, // In binary mode: for 2 megabytes 2048, 2048k or 2m.
        'allowedTypes'         => null, // * means all allowed or 'image/jpeg,image/png' etc.
        'allowedExtensions'    => null, // * means all allowed or 'jpg,jpeg' etc.
        'allowEmptyExtensions' => null,
        'clear'                => true, // Useful to use resource files after upload etc.
        'clearSource'          => true, // Useful to display crop files after crop etc.
        'jpegQuality'          => -1,   // Use default quality.
    ];

    /**
     * To array.
     * @return array
     */
    public final function toArray(): array
    {
        return [
            'name'      => $this->name,
            'type'      => $this->type,
            'size'      => $this->size,
            'extension' => $this->extension,
            'source'    => $this->source,
            'directory' => $this->directory,
            'options'   => $this->options
        ];
    }



    /**
     * Get name.
     * @return string
     */
    public final function getName(): string
    {
        return $this->name;
    }

    /**
     * Get type.
     * @return string
     */
    public final function getType(): string
    {
        return $this->type;
    }

    /**
     * Get size.
     * @return int
     */
    public final function getSize(): int
    {
        return $this->size;
    }

    /**
     * Get extension.
     * @return string
     */
    public final function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * Get directory.
     * @return string
     */
    public final function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * Get source.
     * @return string
     */
    public final function getSource(): string
    {
        return $this->source;
    }

    /**
     * Get options.
     * @return array
     */
    public final function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get destination.
     * @param  string|null $name
     * @param  string|null $nameAppendix
     * @return string
     */
    public final function getDestination(string $name = null, string $nameAppendix = null): string
    {
        // Update name if given on runtime (with saveAs() or moveAs()).
        if ($name != null) {
            $name = $this->name = $this->prepareName($name, $nameAppendix);
        }

        $destination = $this->directory .'/'. ($name ?? $this->name);
        if ($this->extension != '') {
            $destination .= '.'. $this->extension;
        }

        return $destination;
    }

    /**
     * Prepare name.
     * @param  string      $name
     * @param  string|null $nameAppendix
     * @return string
     * @throws froq\file\FileException
     * @since  1.0
     */
    protected final function prepareName(string $name, string $nameAppendix = null): string
    {
        // Some security & standard stuff.
        $name = preg_replace(['~[\s_-]+~', '~[^a-z0-9-]~i'], ['-', ''],
            pathinfo($name, PATHINFO_FILENAME));
        if (strlen($name) > 250) {
            $name = substr($name, 0, 250);
        }

        // All names lower-cased.
        $name = strtolower($name);

        // Hash name if option set.
        $hash = $this->options['hash'];
        if ($hash != null) {
            static $hashAlgos = [8 => 'fnv1a32', 16 => 'fnv1a64', 32 => 'md5', 40 => 'sha1'];
            @ $hashAlgo = $hashAlgos[$this->options['hashLength'] ?? 16];
            if ($hashAlgo == null) {
                throw new FileException("Only '8,16,32,40' are accepted");
            }

            if ($hash == 'rand') {
                $name = hash($hashAlgo, uniqid(microtime(), true));
            } elseif ($hash == 'file') {
                $name = hash($hashAlgo, file_get_contents($this->source));
            } elseif ($hash == 'fileName') {
                $name = hash($hashAlgo, $name);
            }
        }

        // Appendix like 'crop' (ie: abc123-crop.jpg).
        if ($nameAppendix != null) {
            $name .= '-'. strtolower(preg_replace('~[^a-z0-9-]~i', '', $nameAppendix));
        }

        return $name;
    }
}
