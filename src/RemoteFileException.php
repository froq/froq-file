<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * @package froq\file
 * @class   froq\file\RemoteFileException
 * @author  Kerem Güneş
 * @since   7.9
 */
class RemoteFileException extends FileSystemException
{
    public readonly object|null $request;
    public readonly object|null $response;

    public function __construct(mixed ...$arguments)
    {
        $this->request  = array_pluck($arguments, 'request');
        $this->response = array_pluck($arguments, 'response');

        parent::__construct(...$arguments);
    }

    public static function forHttpError(object $request, object $response): static
    {
        return new static(
            'HTTP Error: %s', $response->headers[0],
            code: $response->status, request: $request, response: $response
        );
    }

    public static function forInvalidUrl(string $url): static
    {
        return new static(
            'Invalid URL: ' . $url,
            cause: new error\InvalidUrlError(reduce: true)
        );
    }

    public static function forInvalidPath(string $path): static
    {
        return new static(
            'Invalid path: ' . $path,
            cause: new error\InvalidPathError(reduce: true)
        );
    }

    public static function forInvalidStream(): static
    {
        return new static('Stream is closed or not opened yet, call open()');
    }

    public static function forCannotOverwriteFile(string $file): static
    {
        return new static('Cannot overwrite existing file %s, use $force argument', $file);
    }
}
