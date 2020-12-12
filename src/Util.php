<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\file;

/**
 * Util.
 *
 * @package froq\file
 * @object  froq\file\Util
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 * @static
 */
final class Util
{
    /**
     * Format bytes as human readable text.
     *
     * @param  int $bytes
     * @return string
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        static $base = 1024, $units = ['B', 'KB', 'MB', 'GB'];

        $i = 0;
        while ($bytes > $base) {
            $i++; $bytes /= $base;
        }

        return round($bytes, $precision) . $units[$i];
    }


    /**
     * Convert human readable text to integer.
     *
     * @param  string $bytes
     * @return int
     */
    public static function convertBytes(string $bytes): int
    {
        static $base = 1024, $units = ['', 'K', 'M', 'G'];

        // Eg: 6.4M or 6.4MB => 6.4MB, 64M or 64MB => 64MB.
        if (sscanf($bytes, '%f%c', $byte, $unit) == 2) {
            $exp = array_search(strtoupper($unit), $units);

            return (int) ($byte * pow($base, $exp));
        }

        return (int) $bytes;
    }
}
