<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

use froq\common\interface\Thrownable;
use Throwable, TraceStack;

/**
 * @package froq\file
 * @class   froq\file\FileSystemException
 * @author  Kerem Güneş
 * @since   7.0
 */
class FileSystemException extends \froq\common\Exception
{
    /**
     * Extract internal error and create an exception.
     */
    public static function error(Throwable $e = null, Throwable $cause = null): static
    {
        $message = $e?->getMessage();

        if ($message ??= error_message(extract: true)) {
            switch (true) {
                case str_has($message, 'No such file', true):
                    $cause = new error\NoFileError(cause: $cause, reduce: true);
                    break;
                case str_has($message, 'Permission', true):
                    $cause = new error\NoPermissionError(cause: $cause, reduce: true);
                    break;
                case str_has($message, 'not a valid mode', true):
                    // Prettify mode error message.
                    $message = format(
                        'Failed to open stream: %q is not a valid mode',
                        grep('~`([^\']*)~', $message)
                    );
                    break;
            };
        }

        $exception = self::getExceptionClass();

        // Modify message for directories that reports exists file errors.
        if (is_class_of($exception, DirectoryException::class) && ($message === 'File exists')) {
            $message = 'Directory exists';
        }

        // Modify silent errors that show up because of invalid open modes.
        if (is_class_of($exception, DirectoryException::class, FileException::class) && ($message === null)) {
            $message = 'Unknown error, probably invalid open mode';
        }

        return new $exception($message ?? 'Unknown error', cause: $cause, reduce: true);
    }

    /**
     * Use message & cause of given Throwable and create an exception.
     */
    public static function exception(Throwable $e, Throwable $cause = null): static
    {
        $exception = self::getExceptionClass();

        if (!$cause && $e instanceof Thrownable) {
            $cause = $e->getCause();
        }

        return new $exception($e->getMessage(), cause: $cause, reduce: true);
    }

    /**
     * Get real exception class (mostly for subclasses).
     */
    protected static function getExceptionClass(): string
    {
        $traces = new TraceStack(options: 1);

        // Search related exception.
        foreach ($traces as $trace) {
            if ($trace->object !== null) {
                $class = $trace->object::class . 'Exception';
                if (str_starts_with($class, __NAMESPACE__) && class_exists($class)) {
                    return $class;
                }
            }
            if ($trace->class !== null) {
                $class = $trace->class . 'Exception';
                if (str_starts_with($class, __NAMESPACE__) && class_exists($class)) {
                    return $class;
                }
            }
        }

        // Sub-exception.
        return static::class;
    }
}
