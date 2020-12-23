<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-encrypting
 */
declare(strict_types=1);

namespace froq\file\mime;

/**
 * Mime Types.
 *
 * @package froq\file\mime
 * @object  froq\file\mime\MimeTypes
 * @author  Kerem Güneş
 * @since   3.0, 4.0 Moved to mime directory.
 * @static
 */
final class MimeTypes
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
