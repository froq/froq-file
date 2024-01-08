<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file;

use froq\file\mime\Mime;

/**
 * Image class for working with image files.
 *
 * @package froq\file
 * @class   froq\file\Image
 * @author  Kerem Güneş
 * @since   7.0
 */
class Image extends File
{
    /** Resolved image info. */
    protected ?array $info = null;

    /**
     * @throws froq\file\ImageException
     * @override
     */
    public function __construct(string|Path $path, array $options = null)
    {
        try {
            parent::__construct($path, $options);
        } catch (\Throwable $e) {
            throw ImageException::exception($e);
        }
    }

    /**
     * Get image info, resolve if absent.
     *
     * @param  int|string|null $key
     * @return mixed
     * @causes froq\file\ImageException
     */
    public function info(int|string $key = null): mixed
    {
        $this->info ??= $this->information();

        return @($key !== null ? $this->info[$key] : $this->info);
    }

    /**
     * Get image dimensions, resolve if absent.
     *
     * @return array
     * @causes froq\file\ImageException
     */
    public function dims(): array
    {
        $this->info ??= $this->information();

        return @[$this->info[0], $this->info[1]];
    }

    /**
     * Create an image from given string.
     *
     * @param  string     $string
     * @param  array|null $options
     * @return froq\file\Image
     * @throws froq\file\ImageException
     * @override
     */
    public static function fromString(string $string, array $options = null): Image
    {
        // Also validate if given string is valid image string.
        $info = @getimagesizefromstring($string) ?: throw ImageException::forInvalidImageData();

        // Add extra info fields as readable fields.
        [$info['type'], $info['width'], $info['height']] = array_select($info, [2, 0, 1]);

        // When no mime / extension given.
        $options['mime']      ??= $info['mime'];
        $options['extension'] ??= Mime::getExtensionByType($info['mime']);

        $temp = @file_create('froq/', temp: true)   ?? throw ImageException::error();
        @file_write($temp, $string, flags: LOCK_EX) ?? throw ImageException::error();

        $that = new Image($temp, $options);
        $that->temp = $temp; // Parent's.
        $that->info = $info;

        // For size() etc.
        $that->open('a+b');

        return $that;
    }

    /**
     * Create an image from given file by reading it.
     *
     * @param  string     $file
     * @param  array|null $options
     * @return froq\file\Image
     * @throws froq\file\ImageException
     * @override
     */
    public static function fromFileString(string $file, array $options = null): Image
    {
        return self::fromString(
            @file_read($file) ?: throw ImageException::error(),
            $options
        );
    }

    /**
     * Resolve image information.
     *
     * @throws froq\file\ImageException
     */
    private function information(): array
    {
        $ret = @getimagesize($this->path->name) ?: throw ImageException::error();

        // Add type, with and height as named.
        [$ret['type'], $ret['width'], $ret['height']] = array_select($ret, [2, 0, 1]);

        return $ret;
    }
}
