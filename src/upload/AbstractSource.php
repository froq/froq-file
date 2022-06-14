<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file\upload;

use froq\file\File;
use froq\file\mime\{Mime, MimeException};
use froq\common\interface\Stringable;
use froq\common\trait\{ApplyTrait, OptionTrait};
use froq\util\Util;

/**
 * An abstract uploaded source entity for working with files/images in OOP style with a few
 * safety options.
 *
 * @package froq\file\upload
 * @object  froq\file\upload\AbstractSource
 * @author  Kerem Güneş
 * @since   4.0, 5.0
 */
abstract class AbstractSource implements Stringable
{
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
        'hashLength'        => null,  // Available lengths: 8, 16, 32 or 40 (default=32).
        'maxFileSize'       => null,  // In binary mode: for 2 megabytes 2048, 2048k or 2m.
        'allowedTypes'      => '*',   // All '*' allowed or 'image/jpeg,image/png' etc.
        'allowedExtensions' => '*',   // All '*' allowed or 'jpg,jpeg' etc.
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
        $this->setOptions(array_options($options, self::$optionsDefault));
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        try { $this->clear(); } catch (\Throwable) {}
    }

    /**
     * Get source file.
     *
     * @return string
     * @causes froq\file\upload\{FileSourceException|ImageSourceException}
     */
    public final function getSource(): string
    {
        return $this->source ?? self::throw('No source yet, call prepare() first');
    }

    /**
     * Get source info.
     *
     * @return array
     * @causes froq\file\upload\{FileSourceException|ImageSourceException}
     */
    public final function getSourceInfo(): array
    {
        return $this->sourceInfo ?? self::throw('No source info yet, call prepare() first');
    }

    /**
     * Get target file.
     *
     * @return string
     * @causes froq\file\upload\{FileSourceException|ImageSourceException}
     * @since  5.0
     */
    public final function getTarget(): string
    {
        return $this->target ?? self::throw('No target yet, call save() or move() first');
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
     * @causes froq\file\upload\{FileSourceException|ImageSourceException}
     */
    public final function prepare(array $file, array $options = null): self
    {
        // Add deferred options.
        $this->options = array_options($options, $this->options);

        [$error, $source, $directory] = [
            $file['error']     ?? null,
            $file['file']      ?? $file['tmp_name']           ?? null,
            $file['directory'] ?? $this->options['directory'] ?? null,
        ];

        $error && self::throw(
            UploadError::MESSAGES[$error] ?? 'Unknown error',
            code: UploadError::INTERNAL
        );

        $source = get_real_path((string) $source, true);
        $source || self::throw(
            'No source given, `file` or `tmp_name` field cannot be empty',
            code: UploadError::NO_VALID_FILE
        );

        $directory = get_real_path((string) $directory);
        $directory || self::throw(
            'No directory given, `directory` field or option cannot be empty',
            code: UploadError::DIRECTORY_EMPTY
        );

        // Validate file existence and give a proper error.
        if (!$source) {
            if (File::errorCheck($source, $error)) {
                self::throw($error->message, code: $error->code, cause: $error);
            }

            self::throw(
                'No source file exists such `%s`', $source,
                code: UploadError::NO_VALID_SOURCE
            );
        }

        [$size, $type, $name] = array_select($file, ['size', 'type', 'name']);

        $size    ??= filesize($source);
        $type    ??= Mime::getType($source);
        $extension = Mime::getExtension($source) ?: Mime::getExtensionByType($type);

        if (!$this->isAllowedType($type)) {
            self::throw(
                'Type `%s` not allowed via options, allowed types: %s',
                [$type, $this->options['allowedTypes']],
                code: UploadError::OPTION_NOT_ALLOWED_TYPE
            );
        }

        if ($extension && !$this->isAllowedExtension($extension)) {
            self::throw(
                'Extension `%s` not allowed via options, allowed extensions: %s',
                [$extension, $this->options['allowedExtensions']],
                code: UploadError::OPTION_NOT_ALLOWED_EXTENSION
            );
        }

        if ($this->options['maxFileSize']) {
            $maxFileSize = Util::convertBytes((string) $this->options['maxFileSize']);
            if ($maxFileSize > -1 && $size > $maxFileSize) {
                self::throw(
                    'File size exceeded, `maxFileSize` option: %s (%s bytes)',
                    [$this->options['maxFileSize'], $maxFileSize],
                    code: UploadError::OPTION_SIZE_EXCEEDED
                );
            }
        }

        if (!dirmake($directory)) {
            self::throw(
                'Cannot make directory [directory: %S, error: %s]',
                [$directory, '@error'],
                code: UploadError::DIRECTORY_ERROR
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
     * @causes froq\file\upload\{FileSourceException|ImageSourceException}
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
            $hashAlgo || self::throw(
                'Invalid `hashLength` option `%s` [valids: 8,16,32,40]',
                $this->options['hashLength']
            );

            $name = match ($hash) {
                'rand'  => hash($hashAlgo, uuid(timed: true)),
                'file'  => hash_file($hashAlgo, $this->source),
                'name'  => hash($hashAlgo, $name),
                default => self::throw(
                    'Invalid `hash` option `%s` [valids: rand,file,name]', $hash
                ),
            };

            $name || self::throw('Cannot hash file name [error: @error]');
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
     * @causes froq\file\upload\{FileSourceException|ImageSourceException}
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
                        self::throw(
                            'Extension `%s` not allowed via options, allowed extensions: %s',
                            [$extension, $this->options['allowedExtensions']],
                            code: UploadError::OPTION_NOT_ALLOWED_EXTENSION
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
                $types == '*' // All.
            || ($types[0] == '~' && preg_test($types, $type))
            || in_array($type, preg_split('~\s*,\s*~', $types, flags: 1), true)
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
                $extensions == '*' // All.
            || ($extensions[0] == '~' && preg_test($extensions, $extension))
            || in_array($extension, preg_split('~\s*,\s*~', $extensions, flags: 1), true)
        );
    }

    /**
     * Check overwrite avaibility.
     *
     * @param  string $target
     * @return void
     * @causes froq\file\upload\{FileSourceException|ImageSourceException}
     * @since  5.0
     */
    protected final function overwriteCheck(string $target): void
    {
        if (!$this->options['overwrite'] && file_exists($target)) {
            self::throw(
                'Cannot overwrite existing file `%s`', $target,
                code: UploadError::OPTION_NOT_ALLOWED_OVERWRITE
            );
        }
    }

    /**
     * Throw a related exception.
     */
    private static function throw(...$args): void
    {
        $exception = match (true) {
            is_class_of(static::class, FileSource::class)  => FileSourceException::class,
            is_class_of(static::class, ImageSource::class) => ImageSourceException::class,
        };

        throw new $exception(...$args);
    }

    /**
     * Save a file with given or generated name, return name.
     *
     * @param  string      $name
     * @param  string|null $appendix
     * @return string
     * @throws froq\file\upload\{FileSourceException|ImageSourceException}
     */
    abstract public function save(string $name = null, string $appendix = null): string;

    /**
     * Move a file with given or generated name, return name.
     *
     * @param  string $name
     * @param  string|null $appendix
     * @return string
     * @throws froq\file\upload\{FileSourceException|ImageSourceException}
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
