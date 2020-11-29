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
 * @package froq\file\upload
 * @object  froq\file\upload\AbstractUploader
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0, 5.0 Moved to upload directory.
 */
abstract class AbstractUploader
{
    /**
     * Source.
     * @var string
     */
    protected string $source;

    /**
     * File info.
     * @var array
     */
    protected array $fileInfo;

    /**
     * Options.
     * @var array
     */
    protected array $options = [
        'hash'                 => null, // Available commands: 'rand', 'file' or 'fileName' (default=none).
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
     * @param  array      $file
     * @param  array|null $options
     * @throws froq\file\upload\UploadException
     */
    public final function __construct(array $file, array $options = null)
    {
        @ ['source' => $source, 'name' => $name, 'size' => $size, 'error' => $error] = $file;

        // Those "source", "file", "tmp_name" may be given (generally "tmp_name" comes from $_FILES global).
        $source ??= $file['file'] ?? $file['tmp_name'] ?? null;

        if (!$source) {
            throw new UploadException(
                'No valid source given, "source", "file" or "tmp_name" can not be empty',
                null, UploadError::NO_VALID_FILE
            );
        }

        if (!is_string($source) || !is_file($source)) {
            throw new UploadException(
                'No valid source file "%s" given by "source", "file" or "tmp_name"',
                [$source], UploadError::NO_VALID_SOURCE
            );
        }

        $error = $error ? (UploadError::MESSAGES[$error] ?? 'Unknown file upload error') : null;
        if ($error) {
            throw new UploadException($error, null, UploadError::INTERNAL);
        }

        $name ??= uniqid();
        $size ??= filesize($source);

        $options = array_merge($this->options, $options ?? []);
        extract($options, EXTR_PREFIX_ALL, 'options');

        $maxFileSize = FileUtil::convertBytes((string) $options_maxFileSize);
        if ($maxFileSize && $size > $maxFileSize) {
            throw new UploadException(
                'File size exceeded, "maxFileSize" option is "%s" (%s bytes)',
                [$options_maxFileSize, $maxFileSize], UploadError::OPTION_SIZE_EXCEEDED
            );
        }

        // Type & extension security.
        if (!$options_allowedTypes || !$options_allowedExtensions) {
            throw new UploadException(
                'Option "allowedTypes" and "allowedExtensions" must not be empty for '.
                'security reasons, please provide both types and extensions you allow (ie: for '.
                'types "image/jpeg,image/png" and for extensions "jpg,jpeg", or "*" to allow all)',
                null, UploadError::OPTION_EMPTY
            );
        }

        $extension = pathinfo($name, PATHINFO_EXTENSION);
        if (!$extension && $options_allowEmptyExtensions === false) {
            throw new UploadException(
                'Empty extensions not allowed via options',
                null, UploadError::OPTION_EMPTY_EXTENSION
            );
        }

        // Type @override.
        try {
            $type = Mime::getType($source);
        } catch (MimeException $e) {
            throw new UploadException($e);
        }

        if ($options_allowedTypes !== '*'
            && !in_array($type, explode(',', (string) $options_allowedTypes))) {
            throw new UploadException(
                'Type "%s" not allowed via options, allowed types: "%s"'.
                [$type, $options_allowedTypes], UploadError::OPTION_NOT_ALLOWED_TYPE
            );
        }

        $extension = $extension ?: Mime::getExtensionByType($type);
        if ($extension && $options_allowedExtensions !== '*'
            && !in_array($extension, explode(',', (string) $options_allowedExtensions))) {
            throw new UploadException(
                'Extension "%s" not allowed via options, allowed extensions: "%s"',
                [$extension, $options_allowedExtensions], UploadError::OPTION_NOT_ALLOWED_EXTENSION
            );
        }

        $directory = trim((string) ($file['directory'] ?? $options['directory'] ?? ''));
        if (!$directory) {
            throw new UploadException(
                'Directory must not be empty',
                null, UploadError::DIRECTORY_EMPTY
            );
        }

        if (!is_dir($directory)) {
            $ok = mkdir($directory, 0755, true);
            if (!$ok) {
                throw new UploadException(
                    'Cannot make directory [error: %s]',
                    '@error', UploadError::DIRECTORY_ERROR
                );
            }
        }

        $this->source   = $source;
        $this->fileInfo = ['type' => $type, 'name'      => $this->prepareName($name),
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
     * Get source.
     * @return string
     */
    public final function getSource(): string
    {
        return $this->source;
    }

    /**
     * Get file info.
     * @return array
     */
    public final function getFileInfo(): array
    {
        return $this->fileInfo;
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
     * @throws froq\file\upload\UploadException
     */
    protected final function getDestination(string $name = null, string $nameAppendix = null): string
    {
        $extension = $this->fileInfo['extension'];

        // Check name & extension if given on runtime (with saveAs() or moveAs()).
        if ($name != null) {
            if (strpos($name, '.')) {
                $extension = pathinfo($name, PATHINFO_EXTENSION);
                if ($extension && $this->options['allowedExtensions'] !== '*'
                    && !in_array($extension, explode(',', (string) $this->options['allowedExtensions']))) {
                    throw new UploadException(
                        'Extension "%s" not allowed via options, allowed extensions: "%s"',
                        [$extension, $this->options['allowedExtensions']], UploadError::OPTION_NOT_ALLOWED_EXTENSION
                    );
                }
            }

            $name = $this->prepareName($name, $nameAppendix);
        }

        $destination = $this->options['directory'] . DIRECTORY_SEPARATOR . ($name ?? $this->fileInfo['name']);
        if ($extension != null) {
            $destination = $destination .'.'. $extension;
        }

        return $destination;
    }

    /**
     * Prepare name.
     * @param  string      $name
     * @param  string|null $nameAppendix
     * @return string
     * @throws froq\file\upload\UploadException
     */
    protected final function prepareName(string $name, string $nameAppendix = null): string
    {
        // Some security & standard stuff.
        $name = preg_replace('~[^a-z0-9-]+~i', '-', pathinfo($name, PATHINFO_FILENAME));
        if (strlen($name) > 250) {
            $name = substr($name, 0, 250);
        }

        // All names lower-cased.
        $name = strtolower(trim($name, '-'));

        // Hash name if option set.
        $hash = $this->options['hash'];
        if ($hash) {
            static $hashAlgos = [8 => 'fnv1a32', 16 => 'fnv1a64', 32 => 'md5', 40 => 'sha1'],
                   $hashLengthDefault = 32;

            $hashAlgo = $hashAlgos[$this->options['hashLength'] ?? $hashLengthDefault] ?? null;
            if (!$hashAlgo) {
                throw new UploadException('Only "8,16,32,40" are accepted as "hashLength" '.
                    'option, "%s" given', [$this->options['hashLength']]);
            }

            if ($hash == 'rand') {
                $name = hash($hashAlgo, uniqid(random_bytes(8), true));
            } elseif ($hash == 'file') {
                $name = hash_file($hashAlgo, $this->source);
            } elseif ($hash == 'fileName') {
                $name = hash($hashAlgo, $name);
            } else {
                throw new UploadException('Only "rand,file,fileName" are accepted as "hash" '.
                    'option, "%s" given', [$hash]);
            }

            if (!$name) {
                throw new UploadException('Cannot hash file name [error: %s]', '@error');
            }
        }

        // Appendix like 'crop' (ie: abc123-crop.jpg).
        if ($nameAppendix) {
            $name .= '-'. strtolower(preg_replace('~[^a-z0-9-]~i', '', $nameAppendix));
        }

        return $name;
    }

    /**
     * Save.
     * @return string
     * @throws froq\file\upload\UploadException
     */
    abstract public function save(): string;

    /**
     * Save as.
     * @param  string      $name
     * @param  string|null $nameAppendix
     * @return string
     * @throws froq\file\upload\UploadException
     */
    abstract public function saveAs(string $name, string $nameAppendix = null): string;

    /**
     * Move.
     * @return string
     * @throws froq\file\upload\UploadException
     */
    abstract public function move(): string;

    /**
     * Move as.
     * @param  string $name
     * @param  string|null $nameAppendix
     * @return string
     * @throws froq\file\upload\UploadException
     */
    abstract public function moveAs(string $name, string $nameAppendix = null): string;

    /**
     * Clear.
     * @return void
     */
    abstract public function clear(bool $force = false): void;
}
