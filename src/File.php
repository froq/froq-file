<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

use froq\file\mime\Mime;
use froq\file\upload\{FileSource, ImageSource};
use froq\common\interface\Stringable;

/**
 * File class for working with regular files.
 *
 * @package froq\file
 * @class   froq\file\File
 * @author  Kerem Güneş
 * @since   7.0
 */
class File extends PathObject implements Stringable, \Countable, \IteratorAggregate
{
    /** Default make mode. */
    public const MODE = 0644;

    /** Stream handle. */
    private ?Stream $stream = null;

    /** Line for iterations. */
    private ?array $line = null;

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
    public function __construct(string|Path $path, array $options = null)
    {
        // Tempfile (@default=false).
        if (!empty($options['temp'])) {
            $options['open'] ??= 'a+b'; // Ready for write.
            $path = @tmpnam() ?? throw FileException::error();

            // Autodrop (@default=false).
            if (!empty($options['tempdrop'])) {
                $this->temp = $path;
            }
        }

        try {
            parent::__construct($path);
        } catch (\Throwable $e) {
            throw FileException::exception($e);
        }

        if ($this->path->isDirectory() || strsfx((string) $path, DIRECTORY_SEPARATOR)) {
            throw FileException::forCannotUseADirectory();
        }

        if ($options) {
            $this->mime      = $options['mime']      ?? null;
            $this->extension = $options['extension'] ?? null;

            // Auto-open (@default=null).
            if (!empty($options['open'])) {
                $this->open($options['open']);
            }
        }
    }

    /**
     * Set line for iteration.
     *
     * @param  int      $start
     * @param  int|null $stop
     * @return self
     */
    public function setLine(int $start, int $stop = null): self
    {
        $this->line = [$start, $stop];

        return $this;
    }

    /**
     * Get current line.
     *
     * @return array|null
     */
    public function getLine(): array|null
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
        return $this->mime ??= $this->path->getMime() ??
            Mime::getTypeByExtension((string) ($this->extension ?: $this->path->getExtension()));
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
        return $this->extension ??= $this->path->getExtension() ??
            Mime::getExtensionByType((string) ($this->mime ?: $this->path->getMime()));
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
        if ($this->path->isDirectory()) {
            throw FileException::forCannotOpenADirectory();
        }

        $resource = @fopen($this->path->name, $mode) ?: throw FileException::error();

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
    public function writeLine(string $data, string $eol = "\n"): int|null
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
     * Write CSV.
     *
     * @param  array  $data
     * @param  string $separator
     * @param  string $enclosure
     * @param  string $escape
     * @param  string $eol
     * @return int|null
     */
    public function writeCsv(array $data, string $separator = ',', string $enclosure = '"', string $escape = '\\',
        string $eol = "\n"): int|null
    {
        $res = $this->resource();
        $ret = false;

        if (!$data) {
            return 0;
        }

        // Format row array ([..] => [[..]]).
        if (!isset($data[0]) || !is_array($data[0])) {
            $data = [$data];
        }

        foreach ($data as $rows) {
            $size = @fputcsv($res, $rows, $separator, $enclosure, $escape, $eol);
            if ($size === false) {
                return null;
            }
            $ret += $size;
        }

        return ($ret !== false) ? $ret : null;
    }

    /**
     * Write CSV head.
     *
     * @param  array  $columns
     * @param  string $separator
     * @param  string $enclosure
     * @param  string $escape
     * @param  string $eol
     * @param  string $bom
     * @return int|null
     */
    public function writeCsvHead(array $columns, string $separator = ',', string $enclosure = '"', string $escape = '\\',
        string $eol = "\n", string $bom = "\xEF\xBB\xBF"): int|null
    {
        $ret = $this->writeCsv([$bom], $separator, $enclosure, $escape, '');

        if ($ret !== null) {
            $ret = $this->writeCsv([$columns], $separator, $enclosure, $escape, $eol);
        }

        return $ret;
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
     * Read CSV head.
     *
     * @param  string $separator
     * @param  string $enclosure
     * @param  string $escape
     * @param  string $bom
     * @return array|null
     */
    public function readCsvHead(string $separator = ',', string $enclosure = '"', string $escape = '\\',
        string $bom = "\xEF\xBB\xBF"): array|null
    {
        $ret = $this->readCsv($separator, $enclosure, $escape);

        if (isset($ret[0])) {
            $ret[0] = ltrim($ret[0], $bom);
        }

        return $ret;
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
     * @param  bool   $include
     * @return string|null
     * @causes froq\file\FileException
     */
    public function readUntil(string $search, bool $include = false): string|null
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

        if ($include) {
            $ret .= $search;
        }

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
     * Sync.
     *
     * @return bool
     * @causes froq\file\FileException
     */
    public function sync(): bool
    {
        return @fsync($this->resource());
    }

    /**
     * Sync data.
     *
     * @return bool
     * @causes froq\file\FileException
     */
    public function syncData(): bool
    {
        return @fdatasync($this->resource());
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
     * @param  int       $operation
     * @param  int|null &$wouldBlock
     * @return bool
     * @causes froq\file\FileException
     */
    public function lock(int $operation = LOCK_EX, int &$wouldBlock = null): bool
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
     * Get EOF.
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
        $that = new File($from);
        $that->open('rb')->lock();

        $this->lock();
        $this->setContents($that->toString());
        $this->unlock();

        $that->unlock();

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
        $that = new File($to);
        if (!$force && $that->exists()) {
            throw FileException::forCannotOverwriteFile($to);
        }

        $that->open('wb')->lock();
        $that->setContents($this->toString());
        $that->unlock();

        // Apply mode.
        $that->mode($mode);

        return $that->path->name;
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
        $that = new File($to);
        if (!$force && $that->exists()) {
            throw FileException::forCannotOverwriteFile($to);
        }

        $that->open('wb')->lock();
        $that->setContents($this->toString());
        $that->unlock();

        // Apply mode.
        $that->mode($mode);

        // Drop old.
        $this->delete();

        return $that->path->name;
    }

    /**
     * Delete this file.
     *
     * @return bool
     * @causes froq\file\FileException
     */
    public function delete(): bool
    {
        return $this->exists() && $this->remove(true);
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
     * Get this file as an uploaded file (either `FileSource` or `ImageSource`) source.
     *
     * @param  array|null $options
     * @return froq\file\upload\{FileSource|ImageSource}
     * @throws froq\file\FileException
     */
    public function toSource(array $options = null): FileSource|ImageSource
    {
        $file = [
            'file' => $this->path->getName(), 'name' => $this->path->getBaseName(),
            'size' => null, 'mime' => $this->getMime(), 'extension' => $this->getExtension()
        ];

        $type = null;

        if ($options) {
            $type = array_pluck($options, '@type');

            // Internal, but better check anyway.
            if ($type && $type !== 'file' && $type !== 'image') {
                throw FileException::forInvalidTypeOption($type);
            }
        }

        return ($type === 'image' || $this instanceof Image)
            ? new ImageSource($file, $options) : new FileSource($file, $options);
    }

    /**
     * Get this file as an uploaded file (`FileSource`) source.
     *
     * @param  array|null $options
     * @return froq\file\upload\FileSource
     */
    public function toFileSource(array $options = null): FileSource
    {
        $options['@type'] = 'file';

        return $this->toSource($options);
    }

    /**
     * Get this file as an uploaded file (`ImageSource`) source.
     *
     * @param  array|null $options
     * @return froq\file\upload\ImageSource
     */
    public function toImageSource(array $options = null): ImageSource
    {
        $options['@type'] = 'image';

        return $this->toSource($options);
    }

    /**
     * @inheritDoc Countable
     */
    public function count(): int
    {
        // Rewind if opened, or open if not.
        $this->valid() ? $this->rewind() : $this->open();

        $lineCount = 0;

        while (!$this->eof()) {
            $lineCount += 1;

            $this->readLine();
        }

        $this->rewind();

        return $lineCount;
    }

    /**
     * @inheritDoc IteratorAggregate
     */
    public function getIterator(): \Generator|\Traversable
    {
        // Rewind if opened, or open if not.
        $this->valid() ? $this->rewind() : $this->open();

        $lineIndex = 0;
        $startLine = $this->line[0] ?? 0;
        $stopLine  = $this->line[1] ?? PHP_INT_MAX;

        while (!$this->eof()) {
            $lineIndex += 1;

            // Update start & stop.
            $this->line[0] = $lineIndex;
            $this->line[1] ??= null;

            if ($lineIndex < $startLine) {
                // Consume uppers.
                $this->readLine();

                continue;
            }

            if ($lineIndex >= $stopLine) {
                break;
            }

            yield $lineIndex => $this->readLine();
        }
    }

    /**
     * Create a temp file.
     *
     * @param  bool       $drop
     * @param  array|null $options
     * @return static
     */
    public static function fromTemp(bool $drop = true, array $options = null): static
    {
        // For constructor.
        $options['temp']     = true;
        $options['tempdrop'] = $drop;

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
     * Create a file from given file by reading it.
     *
     * @param  string     $file
     * @param  array|null $options
     * @return froq\file\File
     * @throws froq\file\FileException
     */
    public static function fromFileString(string $file, array $options = null): File
    {
        return self::fromString(
            @file_read($file) ?? throw FileException::error(),
            $options
        );
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
