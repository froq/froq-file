<?php
/**
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Copyright (c) 2015 Kerem Güneş
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
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
        'text/x-php'                         => ['php'],
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
     * @return ?string
     */
    public static function getType(string $file): ?string
    {
        $return = null;
        if (extension_loaded('fileinfo')) {
            $info = finfo_open(FILEINFO_MIME_TYPE);
            $return =@ finfo_file($info, $file);
            finfo_close($info);
        }

        // check error
        if ($return === false) {
            throw new FileException(error_get_last()['message'] ?? 'Unknown error!');
        }

        return $return;
    }

    /**
     * Get type by extension.
     * @param  string $file
     * @return ?string
     */
    public static function getTypeByExtension(string $file): ?string
    {
        $return = null;
        if (!strpos($file, '.')) {
            return $return;
        }

        $extension =@ end(explode('.', $file));
        foreach (self::$types as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                $return = $type;
                break;
            }
        }

        return $return;
    }

    /**
     * Get extension.
     * @param  string $type
     * @param  int    $i
     * @return ?string
     */
    public static function getExtensionByType(string $type, int $i = 0): ?string
    {
        $type = strtolower($type);
        if (isset(self::$types[$type])) {
            return self::$types[$type][$i] ?? self::$types[$type][0];
        }

        return null;
    }
}
