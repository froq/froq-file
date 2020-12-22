<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file\mime;

/**
 * Mime Types.
 *
 * @package froq\file\mime
 * @object  froq\file\mime\MimeTypes
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0, 4.0 Moved to mime directory.
 * @static
 */
final class MimeTypes
{
    /** @var array */
    private static array $all;

    /**
     * All.
     * @return array
     */
    public static function all(): array
    {
        return self::$all ??= include 'all.php';
    }
}
