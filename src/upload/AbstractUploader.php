<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file\upload;

use froq\file\upload\{UploadError, UploadException, Util as FileUtil};
use froq\file\mime\{Mime, MimeException};

/**
 * Abstract Uploader.
 *
 * Represents an abstract updloader entity which aims to upload file/image files in OOP style.
 *
 * @package froq\file\upload
 * @object  froq\file\upload\AbstractUploader
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0, 5.0 Moved to upload directory.
 */
abstract class AbstractUploader
{
    /** @var string */
    protected string $source;

    /** @var array */
    protected array $sourceInfo;

    /** @var array */
    protected array $options = [
        'hash'                 => null, // Available commands: 'random', 'file' or 'fileName' (default=none).
        'hashLength'           => null, // 8, 16, 32 or 40 (default=32).
        'maxFileSize'          => null, // In binary mode: for 2 megabytes 2048, 2048k or 2m.
        'allowedTypes'         => null, // * means all allowed or 'image/jpeg,image/png' etc.
        'allowedExtensions'    => null, // * means all allowed or 'jpg,jpeg' etc.
        'allowEmptyExtensions' => null,
        'clear'                => true, // Useful to use resource files after upload etc.
        'clearSource'          => true, // Useful to display crop files after crop etc.
        'jpegQuality'          => -1,   // Use default quality.
        'webpQuality'          => -1,   // Use default quality.
        'directory'            => null, // Will be set in constructor via $file or $options argument.
    ];

    /**
     * Constructor.
     *
     * @param  array      $file
     * @param  array|null $options
     * @throws froq\file\upload\UploadException
     */
    public final function __construct(array $file, array $options = null)
    {
        @ ['source' => $source, 'name' => $name, 'size' => $size, 'error' => $error] = $file;

        $error && throw new UploadException(
            UploadError::MESSAGES[$error] ?? 'Unknown upload error',
            null, UploadError::INTERNAL
        );

        // Those "source", "file", "tmp_name" may be given (generally "tmp_name" comes from $_FILES global).
        $source ??= trim($file['file'] ?? $file['tmp_name'] ?? '');

        $source || throw new UploadException(
            "No valid source given, 'source', 'file' or 'tmp_name' can not be empty",
            null, UploadError::NO_VALID_FILE
        );

        is_file($source) || throw new UploadException(
            "No valid source file '%s' given by 'source', 'file' or 'tmp_name'",
            [$source], UploadError::NO_VALID_SOURCE
        );

        $name ??= self::uniqid();
        $size ??= filesize($source);

        $options = array_merge($this->options, $options ?? []);
        extract($options, EXTR_PREFIX_ALL, 'option');

        if ($option_maxFileSize != null) {
            $maxFileSize = FileUtil::convertBytes((string) $option_maxFileSize);
            if ($maxFileSize && $size > $maxFileSize) {
                throw new UploadException(
                    "File size exceeded, 'maxFileSize' option is '%s' (%s bytes)",
                    [$option_maxFileSize, $maxFileSize], UploadError::OPTION_SIZE_EXCEEDED
                );
            }
        }

        // Type & extension security.
        if ($option_allowedTypes == null || $option_allowedExtensions == null) {
            throw new UploadException(
                "Option 'allowedTypes' and 'allowedExtensions' must not be empty for ".
                "security reasons, please provide both types and extensions you allow (ie: for ".
                "types 'image/jpeg,image/png' and for extensions 'jpg,jpeg', or '*' to allow all)",
                null, UploadError::OPTION_EMPTY
            );
        }

        try { // @override.
            $type = Mime::getType($source);
        } catch (MimeException $e) {
            throw new UploadException($e);
        }

        $extension = Mime::getExtensionByType($type);
        if ($extension == null && $option_allowEmptyExtensions === false) {
            throw new UploadException(
                'Empty extensions not allowed via options',
                null, UploadError::OPTION_EMPTY_EXTENSION
            );
        }

        if ($option_allowedTypes !== '*'
            && !in_array($type, explode(',', $option_allowedTypes))) {
            throw new UploadException(
                "Type '%s' not allowed via options, allowed types: '%s'".
                [$type, $option_allowedTypes], UploadError::OPTION_NOT_ALLOWED_TYPE
            );
        }

        if ($extension && $option_allowedExtensions !== '*'
            && !in_array($extension, explode(',', $option_allowedExtensions))) {
            throw new UploadException(
                "Extension '%s' not allowed via options, allowed extensions: '%s'",
                [$extension, $option_allowedExtensions], UploadError::OPTION_NOT_ALLOWED_EXTENSION
            );
        }

        $directory = trim($file['directory'] ?? $options['directory'] ?? '');
        $directory || throw new UploadException(
            'Directory must not be empty', null, UploadError::DIRECTORY_EMPTY
        );

        if (!is_dir($directory)) {
            $ok = mkdir($directory, 0755, true);
            $ok || throw new UploadException(
                'Cannot make directory [error: %s]', '@error', UploadError::DIRECTORY_ERROR
            );
        }

        $name = $this->prepareName($name);

        $this->source = $source;
        $this->sourceInfo = ['type' => $type, 'name' => $name,
                             'size' => $size, 'extension' => $extension];

        $this->options  = ['directory' => $directory] + $options;
    }

    /**
     * Destructor.
     */
    public final function __destruct()
    {
        $this->clear();
    }

    /**
     * Get source file.
     *
     * @return string
     */
    public final function getSource(): string
    {
        return $this->source;
    }

    /**
     * Get source info.
     *
     * @return array
     */
    public final function getSourceInfo(): array
    {
        return $this->sourceInfo;
    }

    /**
     * Get options.
     *
     * @return array
     */
    public final function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get destination file with/without given name & name appendix.
     *
     * @param  string|null $name
     * @param  string|null $appendix
     * @return string
     * @throws froq\file\upload\UploadException
     */
    protected final function getDestination(string $name = null, string $appendix = null): string
    {
        $extension = $this->sourceInfo['extension'];

        // Check name & extension if given on runtime (with saveAs() or moveAs()).
        if ($name != null) {
            if (strpos($name, '.')) {
                $extension = file_extension($name);
                if ($extension && $this->options['allowedExtensions'] !== '*'
                    && !in_array($extension, explode(',', $this->options['allowedExtensions']))) {
                    throw new UploadException(
                        "Extension '%s' not allowed via options, allowed extensions: '%s'",
                        [$extension, $this->options['allowedExtensions']], UploadError::OPTION_NOT_ALLOWED_EXTENSION
                    );
                }
            }

            $name = $this->prepareName($name, $appendix);
        }

        $destination = $this->options['directory'] .'/'. ($name ?? $this->sourceInfo['name']);
        if ($extension != null) {
            $destination = $destination .'.'. $extension;
        }

        return $destination;
    }

    /**
     * Prepare file name wiht/without name appendix.
     *
     * @param  string      $name
     * @param  string|null $appendix
     * @return string
     * @throws froq\file\upload\UploadException
     */
    protected final function prepareName(string $name, string $appendix = null): string
    {
        // Some security & standard stuff.
        $name = preg_replace('~[^\w\-]~i', '-', file_name($name));
        if (strlen($name) > 255) {
            $name = substr($name, 0, 255);
        }

        $name = trim($name, '-');

        // Hash name if option set.
        $hash = $this->options['hash'];
        if ($hash != null) {
            static $hashAlgos = [8 => 'fnv1a32', 16 => 'fnv1a64', 32 => 'md5', 40 => 'sha1'],
                   $hashLengthDefault = 32;

            $hashAlgo = $hashAlgos[$this->options['hashLength'] ?? $hashLengthDefault] ?? null;
            if (!$hashAlgo) {
                throw new UploadException("Invalid 'hashLength' option '%s', valids are: 8, 16, 32, 40",
                    $this->options['hashLength']);
            }

            if ($hash == 'random') {
                $name = hash($hashAlgo, self::uniqid());
            } elseif ($hash == 'file') {
                $name = hash_file($hashAlgo, $this->source);
            } elseif ($hash == 'fileName') {
                $name = hash($hashAlgo, $name);
            } else {
                throw new UploadException("Invalid 'hash' option '%s', valids are: random, file, fileName",
                    $hash);
            }

            if (!$name) {
                throw new UploadException('Cannot hash file name [error: %s]', '@error');
            }
        }

        // Appendix like 'crop' (ie: abc123-crop.jpg).
        if ($appendix != null) {
            $name .= '-'. preg_replace('~[^\w\-]~i', '', $appendix);
        }

        return $name;
    }

    /**
     * Generate a uniq-id.
     *
     * @return string
     * @since  5.0
     */
    private static function uniqid(): string
    {
        return md5(uniqid(random_bytes(8), true));
    }

    /**
     * Save a file.
     *
     * @return string
     * @throws froq\file\upload\UploadException
     */
    abstract public function save(): string;

    /**
     * Save a file with given name.
     *
     * @param  string      $name
     * @param  string|null $appendix
     * @return string
     * @throws froq\file\upload\UploadException
     */
    abstract public function saveAs(string $name, string $appendix = null): string;

    /**
     * Move a file.
     *
     * @return string
     * @throws froq\file\upload\UploadException
     */
    abstract public function move(): string;

    /**
     * Move a file with given name.
     *
     * @param  string $name
     * @param  string|null $appendix
     * @return string
     * @throws froq\file\upload\UploadException
     */
    abstract public function moveAs(string $name, string $appendix = null): string;

    /**
     * Clear sources/resources.
     *
     * @param  bool $force
     * @return void
     */
    abstract public function clear(bool $force = false): void;
}
