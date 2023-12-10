<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * @package froq\file
 * @class   froq\file\PathObjectException
 * @author  Kerem Güneş
 * @since   7.0, 7.1
 */
class PathObjectException extends FileSystemException
{
    public static function forCannotUseAFile(): static
    {
        return new static('Cannot use a file as a directory', cause: new error\NotADirectoryError(reduce: true));
    }

    public static function forCannotOpenAFile(): static
    {
        return new static('Cannot open a file', cause: new error\NotADirectoryError(reduce: true));
    }

    public static function forCannotUseADirectory(): static
    {
        $exception = self::getExceptionClass();

        return new $exception('Cannot use a directory as a file', cause: new error\NotAFileError(reduce: true));
    }

    public static function forCannotOpenADirectory(): static
    {
        $exception = self::getExceptionClass();

        return new $exception('Cannot open a directory', cause: new error\NotAFileError(reduce: true));
    }

    public static function forCannotUnlink(string $path): static
    {
        if ($type = @filetype($path)) {
            $message = 'Cannot unlink a ' . ($type === 'dir' ? 'directory' : $type);
        } else {
            $message = 'Cannot unlink a non-existing path';
        }

        $exception = self::getExceptionClass();

        return new $exception($message, cause: new error\NotALinkError(reduce: true));
    }

    public static function forCannotRename(string $path): static
    {
        $exception = self::getExceptionClass();

        return new $exception('Cannot rename path %s, use $force argument as true', $path);
    }

    public static function forCannotRemove(string $path): static
    {
        $exception = self::getExceptionClass();

        return new $exception('Cannot remove path %s, use $force argument as true', $path);
    }

    public static function forCannotClear(string $path): static
    {
        $exception = self::getExceptionClass();

        return new $exception('Cannot clear path %s, use $force argument as true', $path);
    }

    public static function forCannotDrop(string $path): static
    {
        $exception = self::getExceptionClass();

        return new $exception('Cannot drop path %s, use $force argument as true', $path);
    }

    public static function forCannotOverwriteFile(string $file): static
    {
        $exception = self::getExceptionClass();

        return new $exception('Cannot overwrite existing file %s, use $force argument', $file);
    }

    // public static function forInvalidWriteMode(): static
    // {
    //     $exception = self::getExceptionClass();

    //     return new $exception('Failed to write stream: Not opened with write mode');
    // }

    public static function forInvalidStream(): static
    {
        $exception = self::getExceptionClass();

        return new $exception('Stream is closed or not opened yet, call open()');
    }
}
