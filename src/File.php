<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

use froq\file\mime\Mime;
use froq\common\interface\Stringable;

/**
 * File class for working with regular files.
 *
 * @package froq\file
 * @class   froq\file\File
 * @author  Kerem Güneş
 * @since   7.0
 */
class File extends Path implements Stringable, \IteratorAggregate
{
    /** Default mode. */
    public const MODE = 0644;

    /** Stream handle. */
    private ?Stream $stream = null;

    /** Line for iterations. */
    private ?int $line = null;

    /** Temp file to remove. */
    protected ?string $temp = null;

    /** Given or resolved mime. */
    protected ?string $mime = null;

    /** Given or resolved extension. */
    protected ?string $extension = null;

    /**
     * @throws froq\file\FileException
     * @override
     */
    public function __construct(string $path, array $options = null)
    {
        // Temporary files.
        if (!empty($options['temp'])) {
            $options['open'] ??= 'a+b'; // Ready for write.
            $path = @tmpnam() ?? throw FileException::error();

            // For autodrop (@default=true).
            if (!empty($options['tempdrop'])) {
                $this->temp = $path;
            }
        }

        try {
            parent::__construct($path);
        } catch (\Throwable $e) {
            throw FileException::exception($e);
        }

        if ($options) {
            $this->mime      = $options['mime']      ?? null;
            $this->extension = $options['extension'] ?? null;

            // Auto-open.
            if (!empty($options['open'])) {
                $this->open($options['open']);
            }
        }
    }

    /**
     * Set line for iteration.
     *
     * @param  int $line
     * @return self
     */
    public function setLine(int $line): self
    {
        $this->line = $line;

        return $this;
    }

    /**
     * Get current line.
     *
     * @return int|null
     */
    public function getLine(): int|null
    {
        return $this->line;
    }

    /**
     * Set mime.
     *
     * @param  string $mime
     * @return self
     */
    public function setMime(string $mime): self
    {
        $this->mime = strtolower($mime);

        return $this;
    }

    /**
     * Get mime.
     *
     * @return string|null
     */
    public function getMime(): string|null
    {
        return $this->mime ??= @file_mime($this->getPath()) ??
            Mime::getTypeByExtension((string) ($this->extension ?: @file_extension($this->getPath())));
    }

    /**
     * Set extension.
     *
     * @param  string $extension
     * @return self
     */
    public function setExtension(string $extension): self
    {
        $this->extension = strtolower($extension);

        return $this;
    }

    /**
     * Get extension.
     *
     * @return string|null
     */
    public function getExtension(): string|null
    {
        return $this->extension ??= @file_extension($this->getPath()) ??
            Mime::getExtensionByType((string) ($this->mime ?: @file_mime($this->getPath())));
    }

    /**
     * Open stream.
     *
     * @param  string $mode
     * @return self
     * @throws froq\file\FileException
     */
    public function open(string $mode = 'rb'): self
    {
        if (is_dir($this->getPath())) {
            throw FileException::forCannotOpenADirectory();
        }

        $resource = @fopen($this->getPath(), $mode) ?: throw FileException::error();

        $this->stream = new Stream($resource, $this->temp);

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
     * Write & synchronize.
     *
     * @param  string   $data
     * @param  int|null $length
     * @param  bool     $reset @internal
     * @return int|null
     * @throws froq\file\FileException
     */
    public function write(string $data, int $length = null, bool $reset = false): int|null
    {
        $res = $this->resource();
        $ret = $reset ? @freset($res, $data) : @fwrite($res, $data, $length);

        if ($ret === false) {
            throw FileException::error();
        }

        // Synchronize changes.
        @fsync($res);

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Write line & synchronize.
     *
     * @param  string $data
     * @param  string $eol
     * @return int|null
     * @causes froq\file\FileException
     */
    public function writeLine(string $data, string $eol = PHP_EOL): int|null
    {
        return $this->write($data . $eol);
    }

    /**
     * Write all & synchronize, truncating first.
     *
     * @param  string $data
     * @return int|null
     * @causes froq\file\FileException
     */
    public function writeAll(string $data): int|null
    {
        return $this->write($data, reset: true);
    }

    /**
     * Read.
     *
     * @param  int $length
     * @return string|null
     * @causes froq\file\FileException
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
     * @causes froq\file\FileException
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
     * @causes froq\file\FileException
     */
    public function readAll(): string|null
    {
        $ret = @freadall($this->resource());

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Read char.
     *
     * @return string|null
     * @causes froq\file\FileException
     */
    public function readChar(): string|null
    {
        $ret = @fgetc($this->resource());

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Read until.
     *
     * @param  string $search
     * @return string|null
     * @causes froq\file\FileException
     */
    public function readUntil(string $search): string|null
    {
        $res = $this->resource();
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
        } while (!$pos && !feof($res));

        // Fix pointer.
        $pos && @fseek($res, $pos);

        return $ret;
    }

    /**
     * Flush.
     *
     * @return bool
     * @causes froq\file\FileException
     */
    public function flush(): bool
    {
        return @fflush($this->resource());
    }

    /**
     * Empty (truncate).
     *
     * @return bool
     * @causes froq\file\FileException
     */
    public function empty(): bool
    {
        return @ftruncate($this->resource(), 0);
    }

    /**
     * Tell.
     *
     * @return int|null
     * @causes froq\file\FileException
     */
    public function tell(): int|null
    {
        $ret = @ftell($this->resource());

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Seek.
     *
     * @param  int $where
     * @param  int $whence
     * @return bool
     * @causes froq\file\FileException
     */
    public function seek(int $where, int $whence = SEEK_SET): bool
    {
        return @fseek($this->resource(), $where, $whence) === 0;
    }

    /**
     * Rewind.
     *
     * @return bool
     * @causes froq\file\FileException
     */
    public function rewind(): bool
    {
        return @rewind($this->resource());
    }

    /**
     * Lock.
     *
     * @param  int      $operation
     * @param  int|null &$wouldBlock
     * @return bool
     * @causes froq\file\FileException
     */
    public function lock(int $operation, int &$wouldBlock = null): bool
    {
        return @flock($this->resource(), $operation, $wouldBlock);
    }

    /**
     * Unlock.
     *
     * @return bool
     * @causes froq\file\FileException
     */
    public function unlock(): bool
    {
        return @flock($this->resource(), LOCK_UN);
    }

    /**
     * Get meta.
     *
     * @return array|null
     * @causes froq\file\FileException
     */
    public function meta(): array|null
    {
        return @fmeta($this->resource()) ?: null;
    }

    /**
     * Get stat.
     *
     * @return array|null
     * @causes froq\file\FileException
     */
    public function stat(): array|null
    {
        return @fstat($this->resource()) ?: null;
    }

    /**
     * Get size.
     *
     * @return int|null
     * @causes froq\file\FileException
     */
    public function size(): int|null
    {
        $ret = @fsize($this->resource());

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Get EOF state.
     *
     * @return bool
     * @causes froq\file\FileException
     */
    public function eof(): bool
    {
        return feof($this->resource());
    }

    /**
     * Set contents.
     *
     * @param  string $contents
     * @return int|null
     * @causes froq\file\FileException
     */
    public function setContents(string $contents): int|null
    {
        return $this->writeAll($contents);
    }

    /**
     * Get contents.
     *
     * @return string|null
     * @causes froq\file\FileException
     */
    public function getContents(): string|null
    {
        return $this->readAll();
    }

    /**
     * Copy and set contents from given file.
     *
     * @param  string $from
     * @return self
     * @causes froq\file\FileException
     */
    public function copy(string $from): self
    {
        $file = new File($from);
        $file->open('rb')->lock(LOCK_EX);

        $this->setContents($this->getPath(), $file->toString());

        $file->unlock();

        return $this;
    }

    /**
     * Save contents to given file & return saved file path.
     *
     * @param  string $to
     * @param  bool   $force
     * @param  int    $mode
     * @return string
     * @throws froq\file\FileException
     * @causes froq\file\FileException
     */
    public function save(string $to, bool $force = false, int $mode = self::MODE): string
    {
        $file = new File($to);
        if (!$force && $file->exists()) {
            throw FileException::forCannotOverwriteFile($to);
        }

        $file->open('wb')->lock(LOCK_EX);
        $file->setContents($this->toString());
        $file->unlock();

        // Apply mode.
        $file->mode($mode);

        return $file->getPath();
    }

    /**
     * Move contents to given file, delete this file & return moved file path.
     *
     * @param  string $to
     * @param  bool   $force
     * @param  int    $mode
     * @return string
     * @throws froq\file\FileException
     * @causes froq\file\FileException
     */
    public function move(string $to, bool $force = false, int $mode = self::MODE): string
    {
        $file = new File($to);
        if (!$force && $file->exists()) {
            throw FileException::forCannotOverwriteFile($to);
        }

        $file->open('wb')->lock(LOCK_EX);
        $file->setContents($this->toString());
        $file->unlock();

        // Apply mode.
        $file->mode($mode);

        // Drop old.
        $this->delete();

        return $file->getPath();
    }

    /**
     * Delete this file.
     *
     * @return bool
     * @causes froq\file\FileException
     */
    public function delete(): bool
    {
        return $this->remove(true);
    }

    /**
     * @inheritDoc froq\common\interface\Stringable
     */
    public function toString(): string
    {
        return (string) $this->getContents();
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
        return 'data:' . $this->getMime() . ';base64,' . $this->toBase64();
    }

    /**
     * @inheritDoc IteratorAggregate
     */
    public function getIterator(): \Iterator
    {
        // Open if not opened, or rewind only if opened.
        $this->valid() ? $this->rewind() : $this->open();

        $linePos = +$this->line;
        $lineInd = 0;

        while (null !== ($line = $this->readLine())) {
            $lineInd++;

            // Update current line.
            $this->line = $lineInd;

            if ($lineInd < $linePos) {
                continue;
            }

            yield $lineInd => $line;
        }
    }

    /**
     * Get directory.
     *
     * @return froq\file\Directory
     */
    public function getDirectory(): Directory
    {
        return new Directory(dirname($this->getPath()));
    }

    /**
     * Create a temp file.
     *
     * @param  bool       $autodrop
     * @param  array|null $options
     * @return static
     */
    public static function fromTemp(bool $autodrop = true, array $options = null): static
    {
        // Temporary file options.
        $options['temp']     = true;
        $options['tempdrop'] = $autodrop;

        return new static('', $options);
    }

    /**
     * Create a file from given string.
     *
     * @param  string     $string
     * @param  array|null $options
     * @return froq\file\File
     * @throws froq\file\FileException
     */
    public static function fromString(string $string, array $options = null): File
    {
        $temp = @file_create('froq/', temp: true)   ?? throw FileException::error();
        @file_write($temp, $string, flags: LOCK_EX) ?? throw FileException::error();

        $that = new File($temp, $options);
        $that->temp = $temp;

        // For size() etc.
        $that->open('a+b');

        return $that;
    }

    /**
     * Get stream resource or throw a `FileException` if not valid or opened yet.
     *
     * @throws froq\file\FileException
     */
    private function resource()
    {
        return $this->stream?->resource()
            ?? throw FileException::forInvalidStream();
    }
}
