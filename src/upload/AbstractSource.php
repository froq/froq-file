<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file\upload;

use froq\file\upload\{UploadError, UploadException};
use froq\file\mime\{Mime, MimeException};
use froq\file\{File, Util as FileUtil};
use froq\common\traits\{OptionTrait, ApplyTrait};
use Throwable;

/**
 * Abstract Source.
 *
 * Represents an abstract uploaded source entity which aims to work files/images in OOP style with a few
 * safety options.
 *
 * @package froq\file\upload
 * @object  froq\file\upload\AbstractSource
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0, 5.0 Moved to upload directory, derived from AbstractUploader.
 */
abstract class AbstractSource
{
    /**
     * Option & Apply traits.
     * @see froq\common\traits\OptionTrait
     * @see froq\common\traits\ApplyTrait
     * @since 5.0
     */
    use OptionTrait, ApplyTrait;

    /** @var string */
    protected string $source;

    /** @var array */
    protected array $sourceInfo;

    /** @var array */
    protected static array $optionsDefault = [
        'hash'                 => null, // Available commands: 'rand', 'file' or 'name' (default=none).
        'hashLength'           => null, // 8, 16, 32 or 40 (default=32).
        'maxFileSize'          => null, // In binary mode: for 2 megabytes 2048, 2048k or 2m.
        'allowedTypes'         => null, // * means all allowed or 'image/jpeg,image/png' etc.
        'allowedExtensions'    => null, // * means all allowed or 'jpg,jpeg' etc.
        'allowEmptyExtensions' => null,
        'clear'                => true, // Useful to use resource files after upload etc.
        'clearSource'          => true, // Useful to display crop files after crop etc.
        'overwrite'            => false, // To prevent existing file overwrite.
        'directory'            => null, // Will be set in constructor via $file or $options argument.
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
     * Get destination file with/without given name & name appendix.
     *
     * @param  string|null $name
     * @param  string|null $appendix
     * @return string
     * @throws froq\file\upload\UploadException
     */
    public final function getDestination(string $name = null, string $appendix = null): string
    {
        $sourceInfo = $this->getSourceInfo();

        // Check name & extension when given with save() or move().
        if ($name !== null) {
            if (strsrc($name, '.')) {
                $extension = file_extension($name);
                if ($extension !== null) {
                    if ($this->options['allowedExtensions'] !== '*' &&
                        !in_array($extension, explode(',', $this->options['allowedExtensions']))) {
                        throw new UploadException(
                            'Extension `%s` not allowed via options, allowed extensions: %s',
                            [$extension, $this->options['allowedExtensions']], UploadError::OPTION_NOT_ALLOWED_EXTENSION
                        );
                    }

                    // Drop extension duplication.
                    $name = file_name($name);
                }
            }

            $name = $this->prepareName($name, $appendix);
        }

        $destination = $this->options['directory'] .'/'. ($name ?: $sourceInfo['name']);
        $extension ??= $sourceInfo['extension'];
        if ($extension !== null) {
            $destination = $destination .'.'. $extension;
        }

        return $destination;
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

            if ($this instanceof ImageSource && $this->options['useImagick']) {
                $this->useImagick = true;
            }
        }

        [$name, $size, $error] = array_select($file, ['name', 'size', 'error']);

        $error && throw new UploadException(
            UploadError::MESSAGES[$error] ?? 'Unknown error',
            null, UploadError::INTERNAL
        );

        // Check for only these keys.
        $source = trim((string) ($file['file'] ?? $file['tmp_name'] ?? ''));
        $source || throw new UploadException(
            'No source given, `file` or `tmp_name` option must not be empty',
            null, UploadError::NO_VALID_FILE
        );

        $directory = trim((string) ($file['directory'] ?? $this->options['directory'] ?? ''));
        $directory || throw new UploadException(
            'No directory given, `directory` option must not be empty',
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

        extract($this->options, EXTR_PREFIX_ALL, 'option');

        // Type & extension security.
        if (empty($option_allowedTypes) || empty($option_allowedExtensions)) {
            throw new UploadException(
                'Option `allowedTypes` and `allowedExtensions` must not be empty for '.
                'security reasons, please provide both types and extensions you allow (ie: for '.
                'types `image/jpeg,image/png` and for extensions `jpg,jpeg`, or `*` to allow all)',
                null, UploadError::OPTION_EMPTY
            );
        }

        $size ??= filesize($source);

        if ($option_maxFileSize > 0) {
            $maxFileSize = FileUtil::convertBytes((string) $option_maxFileSize);
            if ($maxFileSize && $size > $maxFileSize) {
                throw new UploadException(
                    'File size exceeded, `maxFileSize` option is %s (%s bytes)',
                    [$option_maxFileSize, $maxFileSize], UploadError::OPTION_SIZE_EXCEEDED
                );
            }
        }

        try {
            $type = Mime::getType($source);
        } catch (MimeException $e) {
            throw new UploadException($e);
        }

        $extension = file_extension($source) ?: Mime::getExtensionByType($type);
        if (!$extension && $option_allowEmptyExtensions === false) {
            throw new UploadException(
                'Empty extensions not allowed via options',
                null, UploadError::OPTION_EMPTY_EXTENSION
            );
        }

        if ($option_allowedTypes !== '*' &&
            !in_array($type, explode(',', $option_allowedTypes))) {
            throw new UploadException(
                'Type `%s` not allowed via options, allowed types: %s',
                [$type, $option_allowedTypes], UploadError::OPTION_NOT_ALLOWED_TYPE
            );
        }

        if ($extension && $option_allowedExtensions !== '*' &&
            !in_array($extension, explode(',', $option_allowedExtensions))) {
            throw new UploadException(
                'Extension `%s` not allowed via options, allowed extensions: %s',
                [$extension, $option_allowedExtensions], UploadError::OPTION_NOT_ALLOWED_EXTENSION
            );
        }

        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new UploadException(
                'Cannot make directory [error: %s]',
                '@error', UploadError::DIRECTORY_ERROR
            );
        }

        // Set destination name as random UUID default, if no name given.
        $name = $this->prepareName((string) $name) ?: uuid();

        $this->source = $source;
        $this->sourceInfo = ['type' => $type, 'size' => $size,
            'name' => $name, 'extension' => $extension];

        // Reset options.
        $this->options = ['directory' => $directory] + $this->options;

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
        if ($name === '') {
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
            $hashAlgo || throw new UploadException('Invalid `hashLength` option `%s`, valids are: 8, 16, 32, 40',
                $this->options['hashLength']);

            $name = match ($hash) {
                'rand'  => hash($hashAlgo, uuid()),
                'file'  => hash_file($hashAlgo, $this->source),
                'name'  => hash($hashAlgo, $name),
                default => throw new UploadException('Invalid `hash` option `%s`, valids are: rand, file, name',
                    $hash),
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
     * Check overwrite avaibility.
     *
     * @param  string $destination
     * @return void
     * @throws froq\file\upload\UploadException
     * @since  5.0
     */
    protected final function overwriteCheck(string $destination): void
    {
        if (!$this->options['overwrite'] && file_exists($destination)) {
            throw new UploadException('Cannot overwrite existing file `%s`', $destination,
                UploadError::OPTION_NOT_ALLOWED_OVERWRITE);
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
