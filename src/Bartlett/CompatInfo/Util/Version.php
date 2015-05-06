<?php
/**
 * Helper class to format version string.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version  GIT: $Id$
 * @link     http://php5.laurent-laville.org/compatinfo/
 */

namespace Bartlett\CompatInfo\Util;

/**
 * Helper class to format version string.
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version  Release: @package_version@
 * @link     http://php5.laurent-laville.org/compatinfo/
 * @since    Class available since Release 4.0.0-alpha3+1
 */
class Version
{
    public static function ext($versions)
    {
        return empty($versions['ext.max'])
            ? $versions['ext.min']
            : $versions['ext.min'] . ' => ' . $versions['ext.max'];
    }

    public static function php($versions)
    {
        return empty($versions['php.max'])
            ? $versions['php.min']
            : $versions['php.min'] . ' => ' . $versions['php.max'];
    }

    public static function all($versions)
    {
        if (!empty($versions['php.all'])) {
            if (version_compare($versions['php.all'], $versions['php.min'], '>')) {
                return $versions['php.all'];
            }
        }
        return '';
    }

    public static function lib($name, $key = 'version_number')
    {
        if ('curl' == $name
            && function_exists('curl_version')
        ) {
            $meta = curl_version();
            $meta['version_text'] = $meta['version'];

        } elseif ('libxml' == $name) {
            $meta = array(
                'version_number' => defined('LIBXML_DOTTED_VERSION')
                    ? self::toNumber(LIBXML_DOTTED_VERSION) : false,
                'version_text'   => defined('LIBXML_DOTTED_VERSION')
                    ? LIBXML_DOTTED_VERSION : false,
            );

        } elseif ('intl' == $name) {
            $meta = array(
                'version_number' => defined('INTL_ICU_VERSION')
                    ? self::toNumber(INTL_ICU_VERSION) : false,
                'version_text'   => defined('INTL_ICU_VERSION')
                    ? INTL_ICU_VERSION : false,
            );

        } elseif ('openssl' == $name) {
            $meta = array(
                'version_number' => defined('OPENSSL_VERSION_NUMBER')
                    ? OPENSSL_VERSION_NUMBER : false,
                'version_text'   => defined('OPENSSL_VERSION_TEXT')
                    ? self::toText(OPENSSL_VERSION_NUMBER) : false,
            );
        }
        if (isset($meta)) {
            if (isset($key) && array_key_exists($key, $meta)) {
                return $meta[$key];
            }
            return $meta;
        }
        return false;
    }

    protected static function toText($number)
    {
        $hex = dechex(($number & ~ 15) / 16);

        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }

        $arr = str_split($hex, 2);

        return implode('.', array_map('hexdec', $arr));
    }

    protected static function toNumber($text)
    {
        $arr = explode('.', $text);
        $arr = array_map('dechex', $arr);
        $hex = '';

        foreach ($arr as $digit) {
            if (strlen($digit) % 2 !== 0) {
                $hex .= '0';
            }
            $hex .= $digit;
        }
        $hex .= 'F';

        return hexdec($hex);
    }
}
