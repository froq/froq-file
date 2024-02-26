<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\upload;

/**
 * @package froq\file\upload
 * @class   froq\file\upload\SourceException
 * @author  Kerem Güneş
 * @since   4.0, 5.0, 7.0
 */
class SourceException extends \froq\file\FileSystemException
{
    public static function forError(int $error): static
    {
        $exception = self::getExceptionClass();

        return new $exception(
            $message = SourceError::codeToMessage($error),
            cause: new SourceError($message, code: $error, reduce: true),
            code: SourceError::INTERNAL
        );
    }

    public static function forPath(): static
    {
        $exception = self::getExceptionClass();

        return new $exception(
            'No source file given, "file" or "tmp_name" field cannot be empty',
            code: SourceError::INVALID_PATH
        );
    }

    public static function forMaxFileSize(string $maxFileSizeOption, int $maxFileSize): static
    {
        $exception = self::getExceptionClass();

        return new $exception(
            'File size exceeded, "maxFileSize" option: %s (%s bytes)',
            [$maxFileSizeOption, $maxFileSize],
            code: SourceError::OPTION_SIZE_EXCEEDED
        );
    }

    public static function forNotAllowedMime(string $allowedMimesOption, string $mime): static
    {
        $exception = self::getExceptionClass();

        return new $exception(
            'Mime %s not allowed by "allowedMimes" option, allowed mimes: %s',
            [$mime, $allowedMimesOption],
            code: SourceError::OPTION_NOT_ALLOWED_MIME
        );
    }

    public static function forNotAllowedExtension(string $allowedExtensionsOption, string $extension): static
    {
        $exception = self::getExceptionClass();

        return new $exception(
            'Extension %s not allowed by "allowedExtensions" option, allowed extensions: %s',
            [$extension, $allowedExtensionsOption],
            code: SourceError::OPTION_NOT_ALLOWED_EXTENSION
        );
    }

    public static function forCannotOverwriteFile(string $file): static
    {
        $exception = self::getExceptionClass();

        return new $exception(
            'Cannot overwrite on existing file %s, use "overwrite" option as true', $file,
            code: SourceError::OPTION_NOT_ALLOWED_OVERWRITE
        );
    }

    public static function forMakeDirectoryError(string $directory): static
    {
        $exception = self::getExceptionClass();

        return new $exception('Cannot create target directory %S [error: @error]', $directory, extract: true);
    }
}
