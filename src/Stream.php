<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

/**
 * Stream class for directory / file streams.
 *
 * @package froq\file
 * @class   froq\file\Stream
 * @author  Kerem Güneş
 * @since   7.0
 * @internal
 */
class Stream
{
    /** Resource. */
    private $resource;

    /** Temp file to remove. */
    private string|null $tempfile;

    /**
     * Constructor.
     *
     * @param  resource<stream> $resource
     * @param  string|null      $tempfile
     * @throws ArgumentError
     */
    public function __construct($resource, string $tempfile = null)
    {
        if (!is_stream($resource)) {
            throw new \ArgumentError(
                'Argument $resource must be a stream, %t given',
                $resource
            );
        }

        $this->resource = $resource;
        $this->tempfile = $tempfile;
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Get resource.
     *
     * @return resource<stream>|null
     */
    public function resource()
    {
        return $this->resource;
    }

    /**
     * Get tempfile.
     *
     * @return string|null
     */
    public function tempfile(): string|null
    {
        return $this->tempfile;
    }

    /**
     * Get id.
     *
     * @return int|null
     */
    public function id(): int|null
    {
        return $this->valid() ? (int) $this->resource : null;
    }

    /**
     * Get meta.
     *
     * @return array|null
     */
    public function meta(): array|null
    {
        return $this->valid() ? $this->metadata() : null;
    }

    /**
     * Get type.
     *
     * @return string|null
     */
    public function type(): string|null
    {
        return $this->valid() ? lower($this->metadata('stream_type')) : null;
    }

    /**
     * Close stream.
     *
     * @return bool
     */
    public function close(): bool
    {
        $ret = false;

        if (isset($this->resource)) {
            if ($this->type() === 'dir') {
                $ret = true;
                @ closedir($this->resource);
            } else {
                $ret =@ fclose($this->resource);
            }

            $this->resource = null;

            // Drop temp file as well.
            if (isset($this->tempfile)) {
                @ unlink($this->tempfile);
            }
        }

        return $ret;
    }

    /**
     * Validate stream.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->resource);
    }

    /**
     * @internal
     */
    private function metadata(string $key = null): mixed
    {
        $ret = stream_get_meta_data($this->resource);

        return $key ? $ret[$key] : $ret;
    }
}
