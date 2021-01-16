<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file\upload;

use froq\file\upload\{UploadError, UploadException, ImageSource};
use froq\file\mime\{Mime, MimeException};
use froq\file\{File, Util as FileUtil};
use froq\common\trait\{ApplyTrait, OptionTrait};
use froq\common\interface\Stringable;
use Throwable;

/**
 * Abstract Source.
 *
 * Represents an abstract uploaded source entity which aims to work files/images in OOP style with a few
 * safety options.
 *
 * @package froq\file\upload
 * @object  froq\file\upload\AbstractSource
 * @author  Kerem Güneş
 * @since   4.0, 5.0 Moved to upload directory, derived from AbstractUploader.
 */
abstract class AbstractSource implements Stringable
{
    /**
     * @see froq\common\trait\ApplyTrait
     * @see froq\common\trait\OptionTrait
     * @since 5.0
     */
    use ApplyTrait, OptionTrait;

    /** @var string */
    protected string $source;

    /** @var array */
    protected array $sourceInfo;

    /** @var string */
    protected string $target;

    /** @var array */
    protected static array $optionsDefault = [
        'hash'              => null,  // Available commands: 'rand', 'file' or 'name' (default=none).
        'hashLength'        => null,  // 8, 16, 32 or 40 (default=32).
        'maxFileSize'       => null,  // In binary mode: for 2 megabytes 2048, 2048k or 2m.
        'allowedTypes'      => '*',   // "*" means all allowed or 'image/jpeg,image/png' etc.
        'allowedExtensions' => '*',   // "*" means all allowed or 'jpg,jpeg' etc.
        'clear'             => true,  // To free resources after saving/moving file etc.
        'clearSource'       => false, // To delete sources after saving/moving files etc.
        'overwrite'         => false, // To prevent existing file overwrite.
        'directory'         => null,  // Will be set via $file or $options input.
    ];

    /**
     * Constructor.
     *
     * @param array|null $options
     */
    public function __construct(array $options = null)
    {
        $this->setOptions($options, self::$optionsDefault);
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        try {
            $this->clear();
        } catch (Throwable) {}
    }

    /**
     * Get source file.
     *
     * @return string
     * @throws froq\file\upload\UploadException
     */
    public final function getSource(): string
    {
        if (isset($this->source)) {
            return $this->source;
        }

        throw new UploadException('No source ready yet, call prepare() first');
    }

    /**
     * Get source info.
     *
     * @return array
     * @throws froq\file\upload\UploadException
     */
    public final function getSourceInfo(): array
    {
        if (isset($this->sourceInfo)) {
            return $this->sourceInfo;
        }

        throw new UploadException('No source info ready yet, call prepare() first');
    }

    /**
     * Get target file.
     *
     * @return string
     * @throws froq\file\upload\UploadException
     * @since  5.0
     */
    public final function getTarget(): string
    {
        if (isset($this->target)) {
            return $this->target;
        }

        throw new UploadException('No target ready yet, call save() or move() first');
    }

    /**
     * Get mime type.
     *
     * @return string
     */
    public final function getMime(): string
    {
        return $this->getSourceInfo()['type'];
    }

    /**
     * Prepare a file for move/save etc.
     *
     * @param  array      $file
     * @param  array|null $options
     * @return self
     * @throws froq\file\upload\UploadException
     */
    public final function prepare(array $file, array $options = null): self
    {
        // Add deferred options.
        if ($options != null) {
            $this->options = array_merge($this->options, $options);

            if ($this->options['useImagick'] && $this instanceof ImageSource) {
                $this->useImagick = true;
            }
        }

        [$error, $source, $directory] = [
            $file['error']     ?? null,
            $file['file']      ?? $file['tmp_name']           ?? null,
            $file['directory'] ?? $this->options['directory'] ?? null,
        ];

        $error && throw new UploadException(
            UploadError::MESSAGES[$error] ?? 'Unknown error',
            null, UploadError::INTERNAL
        );

        $source = trim((string) $source);
        $source || throw new UploadException(
            'No source given, `file` or `tmp_name` field must not be empty',
            null, UploadError::NO_VALID_FILE
        );

        $directory = trim((string) $directory);
        $directory || throw new UploadException(
            'No directory given, `directory` field or option must not be empty',
            null, UploadError::DIRECTORY_EMPTY
        );

        // Validate file existence and give a proper error.
        if (!realpath($source)) {
            if (File::errorCheck($source, $error)) {
                throw new UploadException($error);
            }

            throw new UploadException(
                'No source file exists such `%s`',
                $source, UploadError::NO_VALID_SOURCE
            );
        }

        [$size, $name] = array_select($file, ['size', 'name']);

        $size    ??= filesize($source);
        $type    ??= Mime::getType($source);
        $extension = Mime::getExtension($source);

        if (!$this->isAllowedType($type)) {
            throw new UploadException(
                'Type `%s` not allowed via options, allowed types: %s',
                [$type, $this->options['allowedTypes']],
                UploadError::OPTION_NOT_ALLOWED_TYPE
            );
        }

        if ($extension && !$this->isAllowedExtension($extension)) {
            throw new UploadException(
                'Extension `%s` not allowed via options, allowed extensions: %s',
                [$extension, $this->options['allowedExtensions']],
                UploadError::OPTION_NOT_ALLOWED_EXTENSION
            );
        }

        if ($this->options['maxFileSize']) {
            $maxFileSize = FileUtil::convertBytes((string) $this->options['maxFileSize']);
            if ($maxFileSize != -1 && $size > $maxFileSize) {
                throw new UploadException(
                    'File size exceeded, `maxFileSize` option: %s (%s bytes)',
                    [$this->options['maxFileSize'], $maxFileSize],
                    UploadError::OPTION_SIZE_EXCEEDED
                );
            }
        }

        // Special directive for directory for using system tempory directory.
        $directory = ($directory != '@tmp') ? $directory : tmp();

        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new UploadException(
                'Cannot make directory [error: %s]',
                '@error', UploadError::DIRECTORY_ERROR
            );
        }

        // Set target name as random UUID default when no name given.
        $name = $this->prepareName((string) $name) ?: uuid(timed: true);

        $this->source     = $source;
        $this->sourceInfo = [
            'type' => $type, 'size'      => $size,
            'name' => $name, 'extension' => $extension
        ];
        $this->options    = ['directory' => $directory] + $this->options; // Reset.

        return $this;
    }

    /**
     * Prepare a file name with/without name appendix.
     *
     * @param  string      $name
     * @param  string|null $appendix
     * @return string|null
     * @throws froq\file\upload\UploadException
     */
    public final function prepareName(string $name, string $appendix = null): string|null
    {
        $name = trim($name);
        if ($name == '') {
            return null;
        }

        // Some security & standard stuff.
        $name = preg_replace('~[^\w\-]~i', '-', $name);
        if (strlen($name) > 255) {
            $name = substr($name, 0, 255);
        }

        // Hash name if option set.
        $hash = $this->options['hash'];
        if ($hash !== null) {
            static $hashAlgos = [8 => 'fnv1a32', 16 => 'fnv1a64', 32 => 'md5', 40 => 'sha1'],
                   $hashLengthDefault = 32;

            $hashAlgo = $hashAlgos[$this->options['hashLength'] ?? $hashLengthDefault] ?? null;
            $hashAlgo || throw new UploadException(
                'Invalid `hashLength` option `%s`, valids are: 8, 16, 32, 40',
                $this->options['hashLength']
            );

            $name = match ($hash) {
                'rand'  => hash($hashAlgo, uuid(timed: true)),
                'file'  => hash_file($hashAlgo, $this->source),
                'name'  => hash($hashAlgo, $name),
                default => throw new UploadException(
                    'Invalid `hash` option `%s`, valids are: rand, file, name', $hash
                ),
            };

            $name || throw new UploadException('Cannot hash file name [error: %s]', '@error');
        }

        // Appendix like 'crop' (ie: abc123-crop.jpg).
        if ($appendix !== null) {
            $name .= '-'. preg_replace('~[^\w\-]~i', '', $appendix);
        }

        $name = trim($name, '-');

        return $name;
    }

    /**
     * Prepare target file path with/without given name & name appendix.
     *
     * @param  string|null $name
     * @param  string|null $appendix
     * @return string
     * @throws froq\file\upload\UploadException
     */
    public final function prepareTarget(string $name = null, string $appendix = null): string
    {
        $sourceInfo = $this->getSourceInfo();

        // Check name & extension when given with save() or move().
        if ($name !== null) {
            if (str_contains($name, '.')) {
                $extension = file_extension($name);
                if ($extension !== null) {
                    if (!$this->isAllowedExtension($extension)) {
                        throw new UploadException(
                            'Extension `%s` not allowed via options, allowed extensions: %s',
                            [$extension, $this->options['allowedExtensions']],
                            UploadError::OPTION_NOT_ALLOWED_EXTENSION
                        );
                    }

                    // Drop extension duplication.
                    $name = substr($name, 0, -(strlen($extension) + 1));
                }
            }

            $name = $this->prepareName($name, $appendix);
        }

        $target = $this->options['directory'] .'/'. ($name ?: $sourceInfo['name']);

        // Add extension.
        if (($extension ??= $sourceInfo['extension']) !== null) {
            $target = $target .'.'. $extension;
        }

        // Store.
        $this->target = $target;

        return $target;
    }

    /**
     * Check whether a type allowed.
     *
     * @param  string $type
     * @return bool
     * @since  5.0
     */
    public final function isAllowedType(string $type): bool
    {
        $types = (string) $this->options['allowedTypes'];

        return (
                $types === '*'
            || ($types[0] == '~' && preg_match($types, $type))
            || in_array($type, preg_split('~\s*,\s*~', $types, flags: 1))
        );
    }

    /**
     * Check whether an extension allowed.
     *
     * @param  string $extension
     * @return bool
     * @since  5.0
     */
    public final function isAllowedExtension(string $extension): bool
    {
        $extensions = (string) $this->options['allowedExtensions'];

        return (
                $extensions === '*'
            || ($extensions[0] == '~' && preg_match($extensions, $extension))
            || in_array($extension, preg_split('~\s*,\s*~', $extensions, flags: 1))
        );
    }

    /**
     * Check overwrite avaibility.
     *
     * @param  string $target
     * @return void
     * @throws froq\file\upload\UploadException
     * @since  5.0
     */
    protected final function overwriteCheck(string $target): void
    {
        if (!$this->options['overwrite'] && file_exists($target)) {
            throw new UploadException(
                'Cannot overwrite existing file `%s`', $target,
                UploadError::OPTION_NOT_ALLOWED_OVERWRITE
            );
        }
    }

    /**
     * Save a file with given or generated name, return name.
     *
     * @param  string      $name
     * @param  string|null $appendix
     * @return string
     * @throws froq\file\upload\UploadException
     */
    abstract public function save(string $name = null, string $appendix = null): string;

    /**
     * Move a file with given or generated name, return name.
     *
     * @param  string $name
     * @param  string|null $appendix
     * @return string
     * @throws froq\file\upload\UploadException
     */
    abstract public function move(string $name = null, string $appendix = null): string;

    /**
     * Clear sources/resources.
     *
     * @param  bool $force
     * @return void
     */
    abstract public function clear(bool $force = false): void;
}
