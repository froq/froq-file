<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\upload;

use froq\file\{File, FileException};
use froq\common\interface\Stringable;
use froq\util\Util;

/**
 * Base class for working with uploaded files / images.
 *
 * @package froq\file\upload
 * @class   froq\file\upload\Source
 * @author  Kerem Güneş
 * @since   4.0, 5.0, 7.0
 */
abstract class Source implements Stringable
{
    /** Source file. */
    private File $file;

    /** Source file info. */
    private array $fileInfo;

    /** Prepared target. */
    private string $target;

    /** Options with defaults. */
    protected array $options = [
        'allowedMimes'      => '*',   // All '*' allowed or 'image/jpeg,image/png' etc.
        'allowedExtensions' => '*',   // All '*' allowed or 'jpg,jpeg' etc.
        'maxFileSize'       => null,  // In binary mode: for 2 megabytes 2048, 2048k or 2m.
        'clear'             => true,  // To free resources after saving/moving file etc.
        'clearSource'       => false, // To delete sources after saving/moving files etc.
        'slug'              => true,  // To slugify file name & appendix.
        'overwrite'         => false, // To prevent existing file overwrite.
    ];

    /**
     * Constructor.
     *
     * @param  array|string $file
     * @param  array|null   $options
     * @throws froq\file\upload\{FileSourceException|ImageSourceException}
     */
    public function __construct(array|string $file, array $options = null)
    {
        // When only file given.
        is_string($file) && $file = ['file' => $file];

        [$error, $sfile] = $this->extractFileData($file, true);

        // Extract if these stuff given (see below).
        [$name, $size, $mime, $extension] = $this->extractFileData($file, false);

        // Check $_FILES error & validate source file.
        $error && throw SourceException::forError((int) $error);
        $sfile || throw SourceException::forPath();

        try {
            $this->file = new File($sfile);
            $this->file->open();
        } catch (FileException $e) {
            throw SourceException::exception($e, cause: $e);
        }

        $this->options = array_options($options, $this->options);

        // Separate name & extension of given (path/file/base) name.
        if ($name && preg_match('~[/\\\]?(.+)\.(\w+)$~', $name, $match)) {
            [$name, $suffix] = array_slice($match, 1);
            $extension ??= $suffix;
            unset($suffix);
        }

        // If none given, set target name to UUID as default.
        $name = $name ? $this->prepareName($name) : uuid(true);

        $size      ??= $this->file->size();
        $mime      ??= $this->file->getMime();
        $extension ??= $this->file->getExtension();

        $this->securityCheck((int) $size, (string) $mime, (string) $extension);

        $this->fileInfo = compact('name', 'size', 'mime', 'extension');
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->clear();
    }

    /**
     * Get source file.
     *
     * @return string
     */
    public function getSourceFile(): string
    {
        return $this->file->getPathName();
    }

    /**
     * Get target file.
     *
     * @return string|null
     */
    public function getTargetFile(): string|null
    {
        return $this->target ?? null;
    }

    /**
     * Get source file info.
     *
     * @return array
     */
    public function getInfo(): array
    {
        return $this->fileInfo;
    }

    /**
     * Get given or generated name.
     *
     * @return string
     */
    public function getName(): string
    {
        return (string) $this->fileInfo['name'];
    }

    /**
     * Get source file size.
     *
     * @return int
     */
    public function getSize(): int
    {
        return (int) $this->fileInfo['size'];
    }

    /**
     * Get source file mime.
     *
     * @return string
     */
    public function getMime(): string
    {
        return (string) $this->fileInfo['mime'];
    }

    /**
     * Get given or detected extension.
     *
     * @return string
     */
    public function getExtension(): string
    {
        return (string) $this->fileInfo['extension'];
    }

    /**
     * Check if given size allowed by options.
     *
     * @param  int       $size
     * @param  int|null &$max
     * @return bool
     */
    public function isAllowedSize(int $size, int &$max = null): bool
    {
        if ($this->options['maxFileSize']) {
            $max = Util::convertBytes((string) $this->options['maxFileSize']);
            if ($max > -1 && $size > $max) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if given mime allowed by options.
     *
     * @param  string $mime
     * @return bool
     */
    public function isAllowedMime(string $mime): bool
    {
        $mimes = (string) $this->options['allowedMimes'];

        return (
                $mimes === '*' // All.
            || ($mimes[0] === '~' && preg_test($mimes, $mime)) // RegExp.
            || in_array($mime, preg_split('~\s*,\s*~', $mimes, flags: 1), true)
        );
    }

    /**
     * Check if an extension allowed.
     *
     * @param  string $extension
     * @return bool
     */
    public function isAllowedExtension(string $extension): bool
    {
        $extensions = (string) $this->options['allowedExtensions'];

        return (
                $extensions === '*' // All.
            || ($extensions[0] === '~' && preg_test($extensions, $extension)) // RegExp.
            || in_array($extension, preg_split('~\s*,\s*~', $extensions, flags: 1), true)
        );
    }

    /**
     * Check if source is an uploaded file.
     *
     * @return bool
     */
    public function isUploadedFile(): bool
    {
        return is_uploaded_file($this->getSourceFile());
    }

    /**
     * Move source to given destination and return new file path or null if error.
     *
     * @param  string      $to
     * @param  string|null $appendix
     * @param  int         $mode
     * @return string|null
     * @causes froq\file\upload\{FileSourceException|ImageSourceException}
     */
    public function moveUploadedFile(string $to, string $appendix = null, int $mode = File::MODE): string|null
    {
        $source = $this->getSourceFile();

        if (is_uploaded_file($source)) {
            $this->securityCheck($this->getSize(), $this->getMime(), $this->getExtension());

            $target = $this->prepareTarget($to, $appendix);

            if (move_uploaded_file($source, $target)) {
                $this->applyMode($target, $mode);

                return $target;
            }
        }

        return null;
    }

    /**
     * Remove an uploaded file.
     *
     * @return bool|null
     */
    public function removeUploadedFile(): bool|null
    {
        $source = $this->getSourceFile();

        if (is_uploaded_file($source)) {
            return unlink($source);
        }

        return null;
    }

    /**
     * Prepare name.
     *
     * Note: If given name contains non-ascii characters, all will be replaced with ascii
     * characters when "slug" option is true (as default), also cut to 255 length.
     *
     * @param  string      $name
     * @param  string|null $appendix
     * @return string
     */
    protected function prepareName(string $name, string $appendix = null): string
    {
        [$name, $namex] = array_map('trim', [$name, (string) $appendix]);

        // Slug options with defaults.
        if ($slug = $this->options['slug']) {
            static $defArgs;
            $defArgs ??= reflect('slug')->getParameterDefaults(skip: 0);
            $slugArgs = array_select((array) $slug, $defArgs, combine: true);
        }

        if ($name !== '') {
            $slug && $name = slug($name, ...$slugArgs);
            if (strlen($name) > 255) {
                $name = strcut($name, 255);
            }
        }

        // Eg: abc-crop.jpg.
        if ($namex !== '') {
            $slug && $namex = slug($namex, ...$slugArgs);
            $name .= '-' . $namex;
        }

        return trim($name, '-');
    }

    /**
     * Prepare target.
     *
     * Note: If a directory given as target path, given or generated name (UUID)
     * will be used as target file (base) name.
     *
     * @param  string      $path
     * @param  string|null $appendix
     * @return string
     * @throws froq\file\upload\{FileSourceException|ImageSourceException}
     */
    protected function prepareTarget(string $path, string $appendix = null): string
    {
        $fileInfo   = $this->fileInfo;
        $pathInfo   = get_path_info($path);

        $directory  = $pathInfo['dirname']   ?: '';
        $name       = $pathInfo['filename']  ?: $fileInfo['name'];
        $extension  = $pathInfo['extension'] ?: $fileInfo['extension'];

        // Directory given (uses UUID as name).
        if (strsfx($path, DIRECTORY_SEPARATOR)) {
            $directory = chop($path, DIRECTORY_SEPARATOR);
        }

        // Ensure directory.
        if (!@dirmake($directory)) {
            throw SourceException::forMakeDirectoryError($directory);
        }

        if (!$this->isAllowedExtension((string) $extension)) {
            throw SourceException::forNotAllowedExtension(
                (string) $this->options['allowedExtensions'], $extension
            );
        }

        // Assign target as well, for save/move etc.
        $this->target = $directory . DIRECTORY_SEPARATOR
                      . $this->prepareName($name, $appendix)
                      . ($extension ? '.' . $extension : '');

        return $this->target;
    }

    /**
     * Check security for size, mime, extension.
     *
     * @param  int    $size
     * @param  string $mime
     * @param  string $extension
     * @return void
     * @throws froq\file\upload\{FileSourceException|ImageSourceException}
     */
    protected function securityCheck(int $size, string $mime, string $extension): void
    {
        if (!$this->isAllowedSize($size, $max)) {
            throw SourceException::forMaxFileSize(
                (string) $this->options['maxFileSize'], $max
            );
        }
        if (!$this->isAllowedMime($mime)) {
            throw SourceException::forNotAllowedMime(
                (string) $this->options['allowedMimes'], $mime
            );
        }
        if (!$this->isAllowedExtension($extension)) {
            throw SourceException::forNotAllowedExtension(
                (string) $this->options['allowedExtensions'], $extension
            );
        }
    }

    /**
     * Check target path overwrite avaibility by options.
     *
     * @param  string $target
     * @return void
     * @throws froq\file\upload\{FileSourceException|ImageSourceException}
     */
    protected function overwriteCheck(string $path): void
    {
        if (!$this->options['overwrite'] && file_exists($path)) {
            throw SourceException::forCannotOverwriteFile($path);
        }
    }

    /**
     * Remove source file if exists.
     *
     * @return void
     */
    protected function clearSource(): void
    {
        $file = $this->getSourceFile();
        if (file_exists($file)) {
            file_remove($file);
            clearstatcache(true, $file);
        }
    }

    /**
     * Apply given mode for target file.
     *
     * @param  string $file
     * @param  int    $mode
     * @return bool
     */
    protected function applyMode(string $file, int $mode = File::MODE): bool
    {
        return @chmod($file, $mode);
    }

    /**
     * @internal
     */
    private function extractFileData(array $file, bool $simple): array
    {
        if ($simple) {
            $ret = [
                // From $_FILES global.
                $file['error'] ?? null,
                // Either "file" or "tmp_name" must given.
                $file['file'] ?? $file['tmp_name'] ?? null,
            ];
        } else {
            $ret = array_select($file, ['name', 'size', 'mime', 'extension']);
        }

        return array_apply($ret, fn($v) => $v !== '' && $v !== null ? $v : null);
    }

    /**
     * Save file & return its full path.
     *
     * @param  string      $to
     * @param  string|null $appendix
     * @return string
     * @throws froq\file\upload\{FileSourceException|ImageSourceException}
     */
    abstract public function save(string $to, string $appendix = null): string;

    /**
     * Move file & return its full path.
     *
     * @param  string      $to
     * @param  string|null $appendix
     * @return string
     * @throws froq\file\upload\{FileSourceException|ImageSourceException}
     */
    abstract public function move(string $to, string $appendix = null): string;

    /**
     * Clear source / resource.
     *
     * @param  bool $force
     * @return void
     */
    abstract public function clear(bool $force = false): void;
}
