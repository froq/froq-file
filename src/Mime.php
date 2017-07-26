<?php
/**
 * Copyright (c) 2016 Kerem Güneş
 *    <k-gun@mail.com>
 *
 * GNU General Public License v3.0
 *    <http://www.gnu.org/licenses/gpl-3.0.txt>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Froq\File;

/**
 * @package    Froq
 * @subpackage Froq\File
 * @object     Froq\File\Mime
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Mime
{
    /**
     * Types.
     * @const string
     */
    const TYPE_DEFAULT   = 'application/octet-stream',
          TYPE_UNKNOWN   = 'application/unknown',
          TYPE_DIRECTORY = 'directory';

    /**
     * Extensions.
     * @const string
     */
    const EXTENSION_UNKNOWN   = 'unknown',
          EXTENSION_DEFAULT   = 'txt',
          EXTENSION_DIRECTORY = 'directory';

    /**
     * MIME types.
     * @var array
     */
    private static $types = [
        'application/octet-stream'           => ['bin'],
        'application/java-archive'           => ['jar'],
        'application/mac-binhex40'           => ['hqx'],
        'application/msaccess'               => ['mdb'],
        'application/msexcel'                => ['xls', 'xmlx', 'xlt', 'xla', 'xlm', 'xlw'],
        'application/mspowerpoint'           => ['pot', 'pps', 'ppt'],
        'application/msproject'              => ['mpp'],
        'application/msword'                 => ['doc', 'docx', 'word', 'w6w'],
        'application/mswrite'                => ['wri'],
        'application/oda'                    => ['oda'],
        'application/pdf'                    => ['pdf'],
        'application/postscript'             => ['ai', 'eps', 'ps'],
        'application/rtf'                    => ['rtf'],
        'application/set'                    => ['set'],
        'application/sla'                    => ['stl'],
        'application/solids'                 => ['sol'],
        'application/step'                   => ['st', 'step', 'stp'],
        'application/vda'                    => ['vda'],
        'application/x-bcpio'                => ['bcpio'],
        'application/x-cpio'                 => ['cpio'],
        'application/x-csh'                  => ['csh'],
        'application/x-director'             => ['dcr', 'dir', 'dxr'],
        'application/x-dvi'                  => ['dvi'],
        'application/x-dwf'                  => ['dwf'],
        'application/x-gtar'                 => ['gtar'],
        'application/x-gzip'                 => ['gz', 'gzip'],
        'application/x-hdf'                  => ['hdf'],
        'application/x-javascript'           => ['js'],
        'application/x-latex'                => ['latex'],
        'application/x-midi'                 => ['mid'],
        'application/x-mif'                  => ['mif'],
        'application/x-netcdf'               => ['cdf', 'nc'],
        'application/x-sh'                   => ['sh'],
        'application/x-shar'                 => ['shar'],
        'application/x-shockwave-flash'      => ['swf'],
        'application/x-stuffit'              => ['sit'],
        'application/x-sv4cpio'              => ['sv4cpio'],
        'application/x-sv4crc'               => ['sv4crc'],
        'application/x-tar'                  => ['tar'],
        'application/x-tcl'                  => ['tcl'],
        'application/x-tex'                  => ['tex'],
        'application/x-texinfo'              => ['texi', 'texinfo'],
        'application/x-ustar'                => ['ustar'],
        'application/x-wais-source'          => ['src'],
        'application/x-winhelp'              => ['hlp'],
        'application/x-rar-compressed'       => ['rar'],
        'application/zip'                    => ['zip'],
        'application/atom+xml'               => ['atom'],
        'application/rss+xml'                => ['rss'],
        'application/json'                   => ['json'],
        'application/jsonml+json'            => ['jsonml'],

        'audio/basic'                        => ['au', 'snd'],
        'audio/midi'                         => ['mid', 'midi'],
        'audio/x-aiff'                       => ['aif', 'aifc', 'aiff'],
        'audio/x-mpeg'                       => ['mp3'],
        'audio/x-pn-realaudio'               => ['ra', 'ram'],
        'audio/x-pn-realaudio-plugin'        => ['rpm'],
        'audio/x-voice'                      => ['voc'],
        'audio/x-wav'                        => ['wav'],

        'image/bmp'                          => ['bmp'],
        'image/gif'                          => ['gif'],
        'image/ief'                          => ['ief'],
        'image/jpeg'                         => ['jpg', 'jpeg', 'jpe'],
        'image/pjpeg'                        => ['jpg', 'jpeg', 'jpe'],
        'image/pict'                         => ['pict'],
        'image/png'                          => ['png'],
        'image/tiff'                         => ['tif', 'tiff'],
        'image/vnd.microsoft.icon'           => ['ico'],
        'image/x-cmu-raster'                 => ['ras'],
        'image/x-portable-anymap'            => ['pnm'],
        'image/x-portable-bitmap'            => ['pbm'],
        'image/x-portable-graymap'           => ['pgm'],
        'image/x-portable-pixmap'            => ['ppm'],
        'image/x-rgb'                        => ['rgb'],
        'image/svg+xml'                      => ['svg', 'svgz'],
        'image/x-xbitmap'                    => ['xbm'],
        'image/x-xpixmap'                    => ['xpm'],
        'image/x-xwindowdump'                => ['xwd'],

        'multipart/x-gzip'                   => ['gzip'],
        'multipart/x-zip'                    => ['zip'],

        'text/css'                           => ['css'],
        'text/xml'                           => ['xml'],
        'text/html'                          => ['htm', 'html'],
        'text/plain'                         => ['txt', 'c', 'cc', 'h'],
        'text/richtext'                      => ['rtx'],
        'text/javascript'                    => ['js'],
        'text/tab-separated-values'          => ['tsv'],
        // 'text/x-php'                         => ['php'],
        'text/x-setext'                      => ['etx'],
        'text/x-sgml'                        => ['sgm', 'sgml'],

        'video/flv'                          => ['flv'],
        'video/mpeg'                         => ['mpe', 'mpeg', 'mpg'],
        'video/msvideo'                      => ['avi'],
        'video/quicktime'                    => ['mov', 'qt'],
        'video/vdo'                          => ['vdo'],
        'video/vivo'                         => ['viv', 'vivo'],
        'video/x-sgi-movie'                  => ['movie'],

        'x-conference/x-cooltalk'            => ['ice'],
        'x-world/x-svr'                      => ['svr'],
        'x-world/x-vrml'                     => ['wrl'],
        'x-world/x-vrt'                      => ['vrt'],

        'directory'                          => ['directory']
    ];

    /**
     * Get type.
     * @param  string $file
     * @return string
     */
    public static final function getType(string $file): string
    {
        $info = finfo_open(FILEINFO_MIME_TYPE);
        $type =@ finfo_file($info, $file);
        finfo_close($info);
        if (!$type) {
            $type = self::TYPE_UNKNOWN;
        }

        return $type;
    }

    /**
     * Get extension.
     * @param  string $type
     * @param  int    $i
     * @return string
     */
    public static final function getExtensionByType(string $type, int $i = 0): string
    {
        $type = strtolower($type);
        if (array_key_exists($type, self::$types)) {
            return self::$types[$type][$i] ?? self::$types[$type][0];
        }

        return self::EXTENSION_UNKNOWN;
    }
}
