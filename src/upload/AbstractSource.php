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
 * Base upload class.
 *
 * @package froq\file\upload
 * @object  froq\file\upload\AbstractSource
 * @author  Kerem Güneş
 * @since   4.0, 5.0
 * @internal
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
        'hash'              => null,  // Available commands: 'rand' or 'name' (default=none).
        'hashLength'        => null,  // Available lengths: 8, 16, 32 or 40 (default=32).
        'allowedMimes'      => '*',   // All '*' allowed or 'image/jpeg,image/png' etc.
        'allowedExtensions' => '*',   // All '*' allowed or 'jpg,jpeg' etc.
        'maxFileSize'       => null,  // In binary mode: for 2 megabytes 2048, 2048k or 2m.
        'clear'             => true,  // To free resources after saving/moving file etc.
        'clearSource'       => false, // To delete sources after saving/moving files etc.
        'overwrite'         => false, // To prevent existing file overwrite.
        'directory'         => null,  // Will be set by $file or $options input.
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
        return $this->source ?? self::throw('No source yet, call prepare()');
    }

    /**
     * Get source info.
     *
     * @return array
     * @causes froq\file\upload\{FileSourceException|ImageSourceException}
     */
    public final function getSourceInfo(): array
    {
        return $this->sourceInfo ?? self::throw('No source info yet, call prepare()');
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
        return $this->target ?? self::throw('No target yet, call save() or move()');
    }

    /**
     * Get source file size.
     *
     * @return int
     */
    public final function getSize(): int
    {
        return $this->getSourceInfo()['size'];
    }

    /**
     * Get source file mime.
     *
     * @return string
     */
    public final function getMime(): string
    {
        return $this->getSourceInfo()['mime'];
    }

    /**
     * Get given or generated source file name.
     *
     * @return string
     */
    public final function getName(): string
    {
        return $this->getSourceInfo()['name'];
    }

    /**
     * Get given or detected source file extension.
     *
     * @return string
     */
    public final function getExtension(): string
    {
        return $this->getSourceInfo()['extension'] ?? '';
    }

    /**
     * Prepare a file for move/save etc.
     *
     * @param  array|string $file
     * @param  array|null   $options
     * @return self
     * @causes froq\file\upload\{FileSourceException|ImageSourceException}
     */
    public final function prepare(array|string $file, array $options = null): self
    {
        // Only file path given.
        is_string($file) && $file = ['file' => $file];

        // Add deferred options.
        $options = array_options($options, $this->options);

        [$error, $source, $directory] = [
            $file['error']     ?? null,
            $file['file']      ?? $file['tmp_name']     ?? '',
            $file['directory'] ?? $options['directory'] ?? null,
        ];

        // Errors come with $_FILES global.
        if ($error && ($message = UploadError::toMessage($error))) {
            $error = new UploadError($message, code: $error);
            self::throw($message, code: UploadError::INTERNAL, cause: $error);
        }

        // Validate file's real path & existence.
        if (!$source = get_real_path($source, true)) {
            self::throw(
                'No source given, `file` or `tmp_name` field cannot be empty',
                code: UploadError::NO_VALID_FILE
            );
        } elseif (File::errorCheck($source, $error)) {
            self::throw(
                'No valid source given such `%s`', $source,
                code: UploadError::NO_VALID_SOURCE, cause: $error
            );
        }

        [$size, $mime, $name, $extension] = array_select($file, ['size', 'mime', 'name', 'extension']);

        // Separate name & extension.
        if ($name && preg_match('~(.+)\.(\w+)$~', $name, $match)) {
            [$name, $extension] = array_slice($match, 1);
        }

        $size      ??= filesize($source);
        $mime      ??= Mime::getType($source, false);
        $extension ??= File::getExtension($source) ?: Mime::getExtensionByType($mime);

        if (!$this->isAllowedMime((string) $mime)) {
            self::throw(
                'Mime `%s` not allowed by options, allowed mimes: %s',
                [$mime, $options['allowedMimes']],
                code: UploadError::OPTION_NOT_ALLOWED_MIME
            );
        }
        if (!$this->isAllowedExtension((string) $extension)) {
            self::throw(
                'Extension `%s` not allowed by options, allowed extensions: %s',
                [$extension, $options['allowedExtensions']],
                code: UploadError::OPTION_NOT_ALLOWED_EXTENSION
            );
        }
        if ($options['maxFileSize']) {
            $maxFileSize = Util::convertBytes((string) $options['maxFileSize']);
            if ($maxFileSize > -1 && $size > $maxFileSize) {
                self::throw(
                    'File size exceeded, `maxFileSize` option: %s (%s bytes)',
                    [$options['maxFileSize'], $maxFileSize],
                    code: UploadError::OPTION_SIZE_EXCEEDED
                );
            }
        }

        // Set target name to UUID as default if none given.
        $name = $name ? $this->prepareName($name) : uuid(with: 'time');

        $this->source     = $source;
        $this->sourceInfo = [
            'mime' => $mime, 'size'      => $size,
            'name' => $name, 'extension' => $extension
        ];

        // Reset options.
        $this->setOptions(['directory' => $directory] + $options);

        return $this;
    }

    /**
     * Prepare a file name with/without name appendix.
     *
     * @param  string      $name
     * @param  string|null $appendix
     * @return string
     * @causes froq\file\upload\{FileSourceException|ImageSourceException}
     */
    public final function prepareName(string $name, string $appendix = null): string
    {
        $name = trim($name);

        if ($name != '') {
            // Some security & standardization stuff.
            $name = preg_replace('~[^\w\-]~', '-', $name);
            if (strlen($name) > 255) {
                $name = substr($name, 0, 255);
            }

            // Hash name if option set.
            if ($hash = $this->options['hash']) {
                static $hashAlgos = [8 => 'fnv1a32', 16 => 'fnv1a64', 32 => 'md5', 40 => 'sha1'],
                       $hashLengthDefault = 32;

                $hashAlgo = $hashAlgos[$this->options['hashLength'] ?? $hashLengthDefault] ?? null;
                $hashAlgo || self::throw(
                    'Invalid `hashLength` option `%s` [valids: 8,16,32,40]',
                    $this->options['hashLength']
                );

                $name = match ($hash) {
                    'rand'  => hash($hashAlgo, uuid()),
                    'name'  => hash($hashAlgo, $name),
                    default => self::throw(
                        'Invalid `hash` option `%s` [valids: rand,name]', $hash
                    ),
                };

                $name || self::throw('Cannot hash file name [error: @error]');
            }
        }

        // Appendix like 'crop' (ie: abc123-crop.jpg).
        if ($appendix != '') {
            $name .= '-' . preg_replace('~[^\w\-]~', '', $appendix);
        }

        return trim($name, '-');
    }

    /**
     * Prepare target file path with/without given name & name appendix.
     *
     * @param  string|null $path
     * @param  string|null $appendix
     * @return string
     * @causes froq\file\upload\{FileSourceException|ImageSourceException}
     */
    public final function prepareTarget(string $path = null, string $appendix = null): string
    {
        $sourceInfo = $this->getSourceInfo();
        $pathInfo   = get_path_info($path ?: $sourceInfo['name']);

        $name       = $pathInfo['filename'] ?: $sourceInfo['name'];
        $extension  = $pathInfo['extension'] ?: $sourceInfo['extension'];
        $directory  = $this->options['directory'] ?? null;

        // Separate directory & name from path if contains directory.
        if (!$directory && str_contains((string) $path, DIRECTORY_SEPARATOR)) {
            ['basename' => $name, 'dirname' => $directory] = $pathInfo;
        }

        // Check / create directory.
        if (!$directory) {
            self::throw(
                'No directory given, `directory` field or option cannot be empty',
                code: UploadError::DIRECTORY_EMPTY
            );
        } elseif (!dirmake($directory)) {
            self::throw(
                'Cannot make directory [directory: %S, error: %s]',
                [$directory, '@error'],
                code: UploadError::DIRECTORY_ERROR
            );
        }

        // Check name & extension when given with save() or move().
        if (str_contains($name, '.')) {
            $extension = $pathInfo['extension'];
            $extensionWithDot = '.' . $extension;

            // Drop extension duplication.
            if (str_ends_with($name, $extensionWithDot)) {
                $name = substr($name, 0, -strlen($extensionWithDot));
            }
        }

        $extension = $extension ?: $sourceInfo['extension'];
        if (!$this->isAllowedExtension((string) $extension)) {
            self::throw(
                'Extension `%s` not allowed by options, allowed extensions: %s',
                [$extension, $this->options['allowedExtensions']],
                code: UploadError::OPTION_NOT_ALLOWED_EXTENSION
            );
        }

        // Make full-path.
        $this->target = $directory . '/' . $this->prepareName($name, $appendix)
                      . ($extension ? '.' . $extension : '');

        return $this->target;
    }

    /**
     * Check whether a mime allowed.
     *
     * @param  string $mime
     * @return bool
     * @since  5.0
     */
    public final function isAllowedMime(string $mime): bool
    {
        $mimes = (string) $this->options['allowedMimes'];

        return (
                $mimes == '*' // All.
            || ($mimes[0] == '~' && preg_test($mimes, $mime)) // RegExp.
            || in_array($mime, preg_split('~\s*,\s*~', $mimes, flags: 1), true)
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
            || ($extensions[0] == '~' && preg_test($extensions, $extension)) // RegExp.
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
                'Cannot overwrite on existing file `%s`', $target,
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
     * Save file and return its full path.
     *
     * @param  string      $path
     * @param  string|null $appendix
     * @return string
     * @throws froq\file\upload\{FileSourceException|ImageSourceException}
     */
    abstract public function save(string $path = null, string $appendix = null): string;

    /**
     * Move file and return its full path.
     *
     * @param  string      $path
     * @param  string|null $appendix
     * @return string
     * @throws froq\file\upload\{FileSourceException|ImageSourceException}
     */
    abstract public function move(string $path = null, string $appendix = null): string;

    /**
     * Clear sources/resources.
     *
     * @param  bool $force
     * @return void
     */
    abstract public function clear(bool $force = false): void;
}
