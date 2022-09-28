<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-file
 */
declare(strict_types=1);

namespace froq\file\mime;

/**
 * An MIME type registry class with `all()` method.
 *
 * @package froq\file\mime
 * @object  froq\file\mime\Mimes
 * @author  Kerem Güneş
 * @since   3.0, 4.0
 * @static
 */
final class Mimes extends \StaticClass
{
    /** @var array */
    private static array $all;

    /**
     * Get/include all MIMEs.
     *
     * @return array
     */
    public static function all(): array
    {
        return self::$all ??= include 'all.php';
    }
}
