<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
namespace froq\file\mime;

/**
 * An MIME type registry class with `all()` method.
 *
 * @package froq\file\mime
 * @class   froq\file\mime\Mimes
 * @author  Kerem Güneş
 * @since   3.0, 4.0
 * @static
 */
class Mimes extends \StaticClass
{
    /** All MIMEs. */
    private static array $all;

    /**
     * Get all MIMEs.
     *
     * @return array
     */
    public static function all(): array
    {
        return self::$all ??= require __DIR__ . '/all.php';
    }
}
