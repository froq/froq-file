<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

use froq\file\mime\Mime;
use froq\common\interface\Stringable;

/**
 *  A read-only remote file class.
 *
 * @package froq\file
 * @class   froq\file\RemoteFile
 * @author  Kerem Güneş
 * @since   7.9
 */
class RemoteFile implements Stringable
{
    /** Stream handle. */
    private ?Stream $stream = null;

    /** Options with defaults. */
    private array $options = [
        'timeout' => 3,
        'block'   => true,
        'gzip'    => true,  // For readAll().
        'method'  => 'GET', // Default method.
        'headers' => [      // Default headers.
            'accept'          => '*/*',
            'accept-encoding' => 'gzip',
            'user-agent'      => 'Froq Remote File (+http://github.com/froq/froq-file)',
        ],
        // Request body (if given).
        'body' => null, 'bodyEncode' => true,
    ];

    /** URL address. */
    public readonly string $url;

    /** Request/response object. */
    public readonly object $request, $response;

    /** Resolved mime. */
    public readonly string|null $mime;

    /** Resolved extension. */
    public readonly string|null $extension;

    /**
     * Constructor.
     *
     * @param  string     $url
     * @param  array|null $options
     * @throws froq\file\RemoteFileException
     */
    public function __construct(string $url, array $options = null)
    {
        if (!preg_test('~^\w+://.+~', $url)) {
            throw RemoteFileException::forInvalidUrl($url);
        }

        $this->url = $url;

        if ($options) {
            if (!empty($options['method'])) {
                $options['method'] = strtoupper((string) $options['method']);
            }

            // Normalize header names.
            if (!empty($options['headers'])) {
                $options['headers'] = array_lower_keys((array) $options['headers']);
            }

            // No GZip decoding.
            if (($options['gzip'] ?? true) !== true) {
                $options['headers']['accept-encoding'] = null;
            }

            $this->options = array_options($options, $this->options);

            // Auto-open.
            if (!empty($options['open'])) {
                $this->open();
            }
        }
    }

    /**
     * Open stream.
     *
     * @return self
     * @throws froq\file\RemoteFileException
     */
    public function open(): self
    {
        try {
            $this->request = $this->request();

            $content = null;

            if (isset($this->options['body'])) {
                $body = $this->options['body'];

                if ($this->options['bodyEncode']) {
                    if (str_test((string) ($this->request->headers['content-type'] ?? ''), '[/+]json')) {
                        $content = json_serialize($body);
                    } elseif (is_array($body) || is_object($body)) {
                        $content = http_build_query($body);
                    }
                }

                $content ??= $body;

                $this->request->headers['content-type'] ??= 'application/x-www-form-urlencoded';

                // Update (fix) request method.
                if ($this->request->method === 'GET') {
                    $this->request->method = 'POST';
                }
            }

            $context = stream_context_create([
                'http' => [
                    'method'  => $this->request->method,
                    'header'  => http_build_headers($this->request->headers),
                    'content' => $content
                ]
            ]);

            $resource = @fopen($this->url, 'rb', context: $context);

            if (!$resource) {
                $error = error_message($code, extract: true);
                throw new RemoteFileException($error, code: $code, request: $this->request);
            }

            stream_set_timeout($resource, (int) $this->options['timeout']);
            stream_set_blocking($resource, (bool) $this->options['block']);

            $this->stream = new Stream($resource);

            $this->response = $this->response($this->stream->meta()['wrapper_data']);

            // Throw HTTP errors.
            if ($this->response->status >= 400) {
                throw RemoteFileException::forResponseError($this->request, $this->response);
            }
        } catch (\Throwable $e) {
            $status  = $e->getCode();
            $message = trim($e->getMessage());

            if (preg_match('~HTTP/[\d\.]+ [^\r\n]+~', $message, $match)) {
                $status = http_parse_response_line($match[0])['status'] ?? $status;
            }

            $e = ($e instanceof RemoteFileException) ? $e : new RemoteFileException($e);
            $e->setCode($status)->setMessage($message);

            throw $e;
        }

        return $this;
    }

    /**
     * Close stream.
     *
     * @return bool
     */
    public function close(): bool
    {
        return (bool) $this->stream?->close();
    }

    /**
     * Validate stream.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return (bool) $this->stream?->valid();
    }

    /**
     * Read.
     *
     * @param  int $length
     * @return string|null
     * @causes froq\file\RemoteFileException
     */
    public function read(int $length): string|null
    {
        $ret = @fread($this->resource(), $length);

        // @tome: fread() returns '' if EOF.
        return ($ret !== false && $ret !== '') ? $ret : null;
    }

    /**
     * Read line.
     *
     * @return string|null
     * @causes froq\file\RemoteFileException
     */
    public function readLine(): string|null
    {
        $ret = @fgets($this->resource());

        return ($ret !== false) ? chop($ret) : null;
    }

    /**
     * Read all.
     *
     * @return string|null
     * @causes froq\file\RemoteFileException
     */
    public function readAll(): string|null
    {
        [$size, $read] = [$this->size(), $this->meta()['unread_bytes']];

        // 'Cos not seeakable.
        if (($size - $read) > 0) {
            $that = new self($this->url, $this->options);
            $that->open();
        } else {
            $that = $this;
        }

        $ret = @stream_get_contents($that->resource());
        $ret = $this->decode($ret);

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Read CSV.
     *
     * @param  string $separator
     * @param  string $enclosure
     * @param  string $escape
     * @return array|null
     */
    public function readCsv(string $separator = ',', string $enclosure = '"', string $escape = '\\'): array|null
    {
        $ret = @fgetcsv($this->resource(), null, $separator, $enclosure, $escape);

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Read until.
     *
     * @param  string $search
     * @param  bool   $include
     * @return string|null
     * @causes froq\file\RemoteFileException
     */
    public function readUntil(string $search, bool $include = false): string|null
    {
        $ret = null;
        $pos = null;

        do {
            $read = $this->read(1024);
            if ($read !== null && ($pos = strpos($read, $search)) !== false) {
                $read = substr($read, 0, $pos);
            }
            if ($read === null || $read === '') {
                break;
            }
            $ret .= $read;
        } while (!$pos && !$this->feof());

        if ($include) {
            $ret .= $search;
        }

        return $ret;
    }

    /**
     * Tell.
     *
     * @return int|null
     * @causes froq\file\RemoteFileException
     */
    public function tell(): int|null
    {
        $ret = @ftell($this->resource());

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Get meta.
     *
     * @return array|null
     * @causes froq\file\RemoteFileException
     */
    public function meta(): array|null
    {
        $this->resource();

        return $this->stream->meta();
    }

    /**
     * Get size.
     *
     * @return int|null
     * @causes froq\file\RemoteFileException
     */
    public function size(): int|null
    {
        $this->resource();

        return $this->response->contentLength;
    }

    /**
     * Get type.
     *
     * @return string|null
     * @causes froq\file\RemoteFileException
     */
    public function type(): string|null
    {
        $this->resource();

        return $this->response->contentType;
    }

    /**
     * Get EOF.
     *
     * @return bool
     * @causes froq\file\RemoteFileException
     */
    public function eof(): bool
    {
        return feof($this->resource());
    }

    /**
     * Save all read data from remote & write to given file.
     *
     * @param  string $to
     * @param  bool   $force
     * @param  int    $mode
     * @return string
     * @throws froq\file\RemoteFileException
     */
    public function save(string $to, bool $force = false, int $mode = File::MODE): string
    {
        try {
            $file = new File($to);
            if (!$force && $file->exists()) {
                throw RemoteFileException::forCannotOverwriteFile($to);
            }

            $file->open('wb')->lock();
            $file->setContents($this->toString());
            $file->unlock();

            // Apply mode.
            $file->mode($mode);
        } catch (FileException $e) {
            throw RemoteFileException::exception($e);
        }

        // Return normalized path.
        return $file->getPathName();
    }

    /**
     * @inheritDoc froq\common\interface\Stringable
     */
    public function toString(): string
    {
        return (string) $this->readAll();
    }

    /**
     * Get contents as Base-64 encoded.
     *
     * @return string
     */
    public function toBase64(): string
    {
        return base64_encode($this->toString());
    }

    /**
     * Get contents as Data URL.
     *
     * @return string
     */
    public function toDataUrl(): string
    {
        return 'data:' . $this->mime . ';base64,' . $this->toBase64();
    }

    /**
     * Get contents as hashed.
     *
     * @param  string $algo
     * @return string
     */
    public function toHash(string $algo = 'md5'): string
    {
        return hash($algo, $this->toString());
    }

    /**
     * Save all read data from remote & write to temp file, return a File instance.
     *
     * Note: This method will overwrite to given path if exists.
     *
     * @param  string|null $path
     * @param  array|null  $options
     * @return froq\file\File
     * @throws froq\file\RemoteFileException
     */
    public function toFile(string $path = null, array $options = null): File
    {
        $path ??= @file_create('froq/', temp: true)           ?? throw RemoteFileException::error();
        @file_write($path, $this->toString(), flags: LOCK_EX) ?? throw RemoteFileException::error();

        // When no mime / extension given.
        $options['mime']      ??= $this->mime;
        $options['extension'] ??= $this->extension;

        return new File($path, $options);
    }

    /**
     * Make a request object preparing headers.
     */
    private function request(): object
    {
        return object(
            url: $this->url,
            method: $this->options['method'],
            headers: $this->options['headers']
        );
    }

    /**
     * Make a response object parsing given headers.
     */
    private function response(array $headers): object
    {
        $headers = join("\r\n", $headers);

        // Last slice of multi headers (eg: redirections).
        if ($headers && str_contains($headers, "\r\n\r\n")) {
            $headers = last(explode("\r\n\r\n", $headers));
        }

        if ($headers = http_parse_headers($headers, CASE_LOWER)) {
            if ($responseLine = http_parse_response_line($headers[0])) {
                $status   = $responseLine['status'];
                $protocol = $responseLine['protocol'];
            }
        } else {
            $status = $protocol = $headers = null;
        }

        $contentType   = $headers['content-type']   ?? null;
        $contentLength = $headers['content-length'] ?? null;

        if ($contentType && ($type = grep('~([^/]+/[^;]+)~', $contentType))) {
            $contentType = $type;

            // Set self attributes.
            [$this->mime, $this->extension] = [$type, Mime::getExtensionByType($type)];

            // Set missing header.
            if ($contentLength === null) {
                $contentLength = $headers['content-length'] = '0';
            }
        }
        if ($contentLength !== null) {
            $contentLength = (int) $contentLength;
        }

        $headers && ksort($headers);

        return object(
            status: $status, protocol: $protocol, headers: $headers,
            contentType: $contentType, contentLength: $contentLength,
        );
    }

    /**
     * Decode GZip'ed data if options.gzip is true.
     */
    private function decode(string|false $data): string|false
    {
        if (!$this->options['gzip']) {
            return $data;
        }
        if (!str_contains($this->response->headers['content-encoding'] ?? '', 'gzip')) {
            return $data;
        }

        return ($data !== false) ? @gzdecode($data) : false;
    }

    /**
     * Get stream resource or throw a `RemoteFileException` if not valid or opened yet.
     *
     * @throws froq\file\RemoteFileException
     */
    private function resource()
    {
        return $this->stream?->resource()
            ?? throw RemoteFileException::forInvalidStream();
    }
}
