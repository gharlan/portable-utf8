<?php

/*
 * Copyright (C) 2013 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace voku\helper\shim;

/**
 * Partial mbstring implementation in PHP, iconv based, UTF-8 centric.
 *
 * Implemented:
 * - mb_convert_encoding     - Convert character encoding
 * - mb_decode_mimeheader    - Decode string in MIME header field
 * - mb_encode_mimeheader    - Encode string for MIME header XXX NATIVE IMPLEMENTATION IS REALLY BUGGED
 * - mb_convert_case         - Perform case folding on a string
 * - mb_get_info             - Get internal settings of mbstring
 * - mb_http_input           - Detect HTTP input character encoding
 * - mb_http_output          - Set/Get HTTP output character encoding
 * - mb_internal_encoding    - Set/Get internal character encoding
 * - mb_list_encodings       - Returns an array of all supported encodings
 * - mb_output_handler       - Callback function converts character encoding in output buffer
 * - mb_strlen               - Get string length
 * - mb_strpos               - Find position of first occurrence of string in a string
 * - mb_strrpos              - Find position of last occurrence of a string in a string
 * - mb_strtolower           - Make a string lowercase
 * - mb_strtoupper           - Make a string uppercase
 * - mb_substitute_character - Set/Get substitution character
 * - mb_substr               - Get part of string
 * - mb_stripos              - Finds position of first occurrence of a string within another, case insensitive
 * - mb_stristr              - Finds first occurrence of a string within another, case insensitive
 * - mb_strrchr              - Finds the last occurrence of a character in a string within another
 * - mb_strrichr             - Finds the last occurrence of a character in a string within another, case insensitive
 * - mb_strripos             - Finds position of last occurrence of a string within another, case insensitive
 * - mb_strstr               - Finds first occurrence of a string within anothers
 * - mb_strwidth             - Return width of string
 * - mb_substr_count         - Count the number of substring occurrences
 *
 * Not implemented:
 * - mb_convert_kana         - Convert "kana" one from another ("zen-kaku", "han-kaku" and more)
 * - mb_convert_variables    - Convert character code in variable(s)
 * - mb_decode_numericentity - Decode HTML numeric string reference to character
 * - mb_encode_numericentity - Encode character to HTML numeric string reference
 * - mb_ereg_*               - Regular expression with multibyte support
 * - mb_parse_str            - Parse GET/POST/COOKIE data and set global variable
 * - mb_preferred_mime_name  - Get MIME charset string
 * - mb_regex_encoding       - Returns current encoding for multibyte regex as string
 * - mb_regex_set_options    - Set/Get the default options for mbregex functions
 * - mb_send_mail            - Send encoded mail
 * - mb_split                - Split multibyte string using regular expression
 * - mb_strcut               - Get part of string
 * - mb_strimwidth           - Get truncated string with specified width
 *
 * @package voku\helper\shim
 */
class Mbstring
{
  const MB_CASE_FOLD = PHP_INT_MAX;

  protected static

      $encoding_list = array(
      'ASCII',
      'UTF-8'
  ),
      $language = 'neutral',
      $internal_encoding = 'UTF-8',
      $caseFold = array(
      array(
          'µ',
          'ſ',
          "\xCD\x85",
          'ς',
          "\xCF\x90",
          "\xCF\x91",
          "\xCF\x95",
          "\xCF\x96",
          "\xCF\xB0",
          "\xCF\xB1",
          "\xCF\xB5",
          "\xE1\xBA\x9B",
          "\xE1\xBE\xBE"
      ),
      array(
          'μ',
          's',
          'ι',
          'σ',
          'β',
          'θ',
          'φ',
          'π',
          'κ',
          'ρ',
          'ε',
          "\xE1\xB9\xA1",
          'ι'
      )
  );

  /**
   * @param $s
   * @param $to_encoding
   * @param $from_encoding
   *
   * @return bool|mixed|string
   */
  public static function mb_convert_encoding($s, $to_encoding, $from_encoding = INF)
  {
    INF === $from_encoding && $from_encoding = self::$internal_encoding;

    $from_encoding = strtolower($from_encoding);
    $to_encoding = strtolower($to_encoding);

    if ('base64' === $from_encoding) {
      $s = base64_decode($s);
      $from_encoding = $to_encoding;
    }

    if ('base64' === $to_encoding) {
      return base64_encode($s);
    }

    if ('html-entities' === $to_encoding) {
      'html-entities' === $from_encoding && $from_encoding = 'Windows-1252';
      'utf-8' === $from_encoding
      || 'utf8' === $from_encoding
      || $s = iconv($from_encoding, 'UTF-8//IGNORE', $s);
      return preg_replace_callback(
          '/[\x80-\xFF]+/', array(
          __CLASS__,
          'html_encoding_callback'
      ), $s
      );
    }

    if ('html-entities' === $from_encoding) {
      $s = html_entity_decode($s, ENT_COMPAT, 'UTF-8');
      $from_encoding = 'UTF-8';
    }

    return iconv($from_encoding, $to_encoding . '//IGNORE', $s);
  }

  /**
   * @param $s
   *
   * @return bool|string
   */
  public static function mb_decode_mimeheader($s)
  {
    return iconv_mime_decode($s, 2, self::$internal_encoding . '//IGNORE');
  }

  /**
   * @param $s
   * @param $charset
   * @param $transfer_encoding
   * @param $linefeed
   * @param $indent
   */
  public static function mb_encode_mimeheader($s, $charset = INF, $transfer_encoding = INF, $linefeed = INF, $indent = INF)
  {
    user_error('mb_encode_mimeheader() is bugged. Please use iconv_mime_encode() instead', E_USER_WARNING);
  }

  /**
   * @param $encoding
   *
   * @return bool|string
   */
  public static function mb_internal_encoding($encoding = INF)
  {
    if (INF === $encoding) {
      return self::$internal_encoding;
    } else {
      $encoding = strtoupper($encoding);
    }

    if ('UTF-8' === $encoding || 'UTF8' === $encoding || false !== @iconv($encoding, $encoding, ' ')) {
      self::$internal_encoding = 'UTF8' === $encoding ? 'UTF-8' : $encoding;

      return true;
    }

    return false;
  }

  /**
   * @param $lang
   *
   * @return bool|string
   */
  public static function mb_language($lang = INF)
  {
    if (INF === $lang) {
      return self::$language;
    }

    switch ($lang = strtolower($lang)) {
      case 'uni':
      case 'neutral':
        self::$language = $lang;
        return true;
    }

    return false;
  }

  /**
   * @return array
   */
  public static function mb_list_encodings()
  {
    return array('UTF-8');
  }

  /**
   * @param $encoding
   *
   * @return array|bool
   */
  public static function mb_encoding_aliases($encoding)
  {
    switch (strtolower($encoding)) {
      case 'utf8':
      case 'utf-8':
        return array('utf8');
    }

    return false;
  }

  /**
   * @param $var
   * @param $encoding
   *
   * @return bool
   */
  public static function mb_check_encoding($var = INF, $encoding = INF)
  {
    if (INF === $encoding) {
      if (INF === $var) {
        return false;
      }
      $encoding = self::$internal_encoding;
    }

    return false !== mb_detect_encoding($var, array($encoding), true);
  }

  /**
   * @param string       $str
   * @param string|array $encoding_list
   * @param bool         $strict
   *
   * @return bool
   */
  public static function mb_detect_encoding($str, $encoding_list = INF, $strict = false)
  {
    if (INF === $encoding_list) {
      $encoding_list = self::$encoding_list;
    } else {
      if (!is_array($encoding_list)) {
        $encoding_list = array_map('trim', explode(',', $encoding_list));
      }
      $encoding_list = array_map('strtoupper', $encoding_list);
    }

    foreach ($encoding_list as $enc) {
      switch ($enc) {
        case 'ASCII':
          if (!preg_match('/[\x80-\xFF]/', $str)) {
            return $enc;
          }
          break;

        case 'UTF8':
        case 'UTF-8':
          if (preg_match('//u', $str)) {
            return $enc;
          }
          break;

        default:
          return strncmp($enc, 'ISO-8859-', 9) ? false : $enc;
      }
    }

    return false;
  }

  /**
   * @param string|array $encoding_list
   *
   * @return array|bool
   */
  public static function mb_detect_order($encoding_list = INF)
  {
    if (INF === $encoding_list) {
      return self::$encoding_list;
    }

    if (!is_array($encoding_list)) {
      $encoding_list = array_map('trim', explode(',', $encoding_list));
    }
    $encoding_list = array_map('strtoupper', $encoding_list);

    foreach ($encoding_list as $enc) {
      switch ($enc) {
        default:
          if (strncmp($enc, 'ISO-8859-', 9)) {
            return false;
          }
        case 'ASCII':
        case 'UTF8':
        case 'UTF-8':
      }
    }

    self::$encoding_list = $encoding_list;

    return true;
  }

  /**
   * @param $s
   * @param $encoding
   *
   * @return bool|int
   */
  public static function mb_strlen($s, $encoding = INF)
  {
    INF === $encoding && $encoding = self::$internal_encoding;
    return iconv_strlen($s, $encoding . '//IGNORE');
  }

  /**
   * @param $s
   * @param $encoding
   *
   * @return bool|mixed|string
   */
  public static function mb_strtolower($s, $encoding = INF)
  {
    return self::mb_convert_case($s, MB_CASE_LOWER, $encoding);
  }

  /**
   * @param $s
   * @param $mode
   * @param $encoding
   *
   * @return bool|mixed|string
   */
  public static function mb_convert_case($s, $mode, $encoding = INF)
  {
    if ('' === $s .= '') {
      return '';
    }

    if (INF === $encoding) {
      $encoding = self::$internal_encoding;
    } else {
      $encoding = strtoupper($encoding);
    }

    if ('UTF-8' === $encoding || 'UTF8' === $encoding) {
      $encoding = INF;
    } else {
      $s = iconv($encoding, 'UTF-8//IGNORE', $s);
    }

    if (MB_CASE_TITLE == $mode) {
      $s = preg_replace_callback(
          '/\b\p{Ll}/u', array(
          __CLASS__,
          'title_case_upper'
      ), $s
      );
      $s = preg_replace_callback(
          '/\B[\p{Lu}\p{Lt}]+/u', array(
          __CLASS__,
          'title_case_lower'
      ), $s
      );
    } else {
      if (MB_CASE_UPPER == $mode) {
        static $upper;
        isset($upper) || $upper = static::getData('upperCase');
        $map = $upper;
      } else {
        if (self::MB_CASE_FOLD === $mode) {
          $s = str_replace(self::$caseFold[0], self::$caseFold[1], $s);
        }

        static $lower;
        isset($lower) || $lower = static::getData('lowerCase');
        $map = $lower;
      }

      static $ulen_mask = array(
          "\xC0" => 2,
          "\xD0" => 2,
          "\xE0" => 3,
          "\xF0" => 4
      );

      $i = 0;
      $len = strlen($s);

      while ($i < $len) {
        $ulen = $s[$i] < "\x80" ? 1 : $ulen_mask[$s[$i] & "\xF0"];
        $uchr = substr($s, $i, $ulen);
        $i += $ulen;

        if (isset($map[$uchr])) {
          $uchr = $map[$uchr];
          $nlen = strlen($uchr);

          if ($nlen == $ulen) {
            $nlen = $i;
            do {
              $s[--$nlen] = $uchr[--$ulen];
            } while ($ulen);
          } else {
            $s = substr_replace($s, $uchr, $i - $ulen, $ulen);
            $len += $nlen - $ulen;
            $i += $nlen - $ulen;
          }
        }
      }
    }

    if (INF === $encoding) {
      return $s;
    } else {
      return iconv('UTF-8', $encoding, $s);
    }
  }

  /**
   * @param $file
   *
   * @return bool|mixed
   */
  protected static function getData($file)
  {
    $file = __DIR__ . '/unidata/' . $file . '.ser';
    if (file_exists($file)) {
      return unserialize(file_get_contents($file));
    } else {
      return false;
    }
  }

  /**
   * @param $s
   * @param $encoding
   *
   * @return bool|mixed|string
   */
  public static function mb_strtoupper($s, $encoding = INF)
  {
    return self::mb_convert_case($s, MB_CASE_UPPER, $encoding);
  }

  /**
   * @param $c
   *
   * @return bool|string
   */
  public static function mb_substitute_character($c = INF)
  {
    return INF !== $c ? false : 'none';
  }

  /**
   * @param      $haystack
   * @param      $needle
   * @param bool $part
   * @param      $encoding
   *
   * @return bool|string
   */
  public static function mb_stristr($haystack, $needle, $part = false, $encoding = INF)
  {
    $pos = self::mb_stripos($haystack, $needle, 0, $encoding);
    return self::getSubpart($pos, $part, $haystack, $encoding);
  }

  /**
   * @param     $haystack
   * @param     $needle
   * @param int $offset
   * @param     $encoding
   *
   * @return bool|int
   */
  public static function mb_stripos($haystack, $needle, $offset = 0, $encoding = INF)
  {
    INF === $encoding && $encoding = self::$internal_encoding;
    $haystack = self::mb_convert_case($haystack, self::MB_CASE_FOLD, $encoding);
    $needle = self::mb_convert_case($needle, self::MB_CASE_FOLD, $encoding);
    return self::mb_strpos($haystack, $needle, $offset, $encoding);
  }

  /**
   * @param     $haystack
   * @param     $needle
   * @param int $offset
   * @param     $encoding
   *
   * @return bool|int
   */
  public static function mb_strpos($haystack, $needle, $offset = 0, $encoding = INF)
  {
    INF === $encoding && $encoding = self::$internal_encoding;
    if ('' === $needle .= '') {
      user_error(__METHOD__ . ': Empty delimiter', E_USER_WARNING);
      return false;
    } else {
      return iconv_strpos($haystack, $needle, $offset, $encoding . '//IGNORE');
    }
  }

  /**
   * @param $pos
   * @param $part
   * @param $haystack
   * @param $encoding
   *
   * @return bool|string
   */
  protected static function getSubpart($pos, $part, $haystack, $encoding)
  {
    INF === $encoding && $encoding = self::$internal_encoding;

    if (false === $pos) {
      return false;
    }
    if ($part) {
      return self::mb_substr($haystack, 0, $pos, $encoding);
    } else {
      return self::mb_substr($haystack, $pos, null, $encoding);
    }
  }

  /**
   * @param string   $s
   * @param int      $start
   * @param null|int $length
   * @param string   $encoding
   *
   * @return string
   */
  public static function mb_substr($s, $start, $length = null, $encoding = INF)
  {
    INF === $encoding && $encoding = self::$internal_encoding;

    if ($start < 0) {
      $start = iconv_strlen($s, $encoding . '//IGNORE') + $start;
      if ($start < 0) {
        $start = 0;
      }
    }

    if (null === $length) {
      $length = 2147483647;
    } else if ($length < 0) {
      $length = iconv_strlen($s, $encoding . '//IGNORE') + $length - $start;
      if ($length < 0) {
        return '';
      }
    }

    return iconv_substr($s, $start, $length, $encoding . '//IGNORE') . '';
  }

  /**
   * @param string $type
   *
   * @return array|bool
   */
  public static function mb_get_info($type = 'all')
  {
    $info = array(
        'internal_encoding'          => self::$internal_encoding,
        'http_output'                => 'pass',
        'http_output_conv_mimetypes' => '^(text/|application/xhtml\+xml)',
        'func_overload'              => 0,
        'func_overload_list'         => 'no overload',
        'mail_charset'               => 'UTF-8',
        'mail_header_encoding'       => 'BASE64',
        'mail_body_encoding'         => 'BASE64',
        'illegal_chars'              => 0,
        'encoding_translation'       => 'Off',
        'language'                   => self::$language,
        'detect_order'               => self::$encoding_list,
        'substitute_character'       => 'none',
        'strict_detection'           => 'Off',
    );

    if ('all' === $type) {
      return $info;
    } elseif (isset($info[$type])) {
      return $info[$type];
    } else {
      return false;
    }
  }

  /**
   * @param string $type
   *
   * @return bool
   */
  public static function mb_http_input($type = '')
  {
    return false;
  }

  /**
   * @param $encoding
   *
   * @return bool|string
   */
  public static function mb_http_output($encoding = INF)
  {
    return INF !== $encoding ? 'pass' === $encoding : 'pass';
  }

  /**
   * @param $s
   * @param $encoding
   *
   * @return int
   */
  public static function mb_strwidth($s, $encoding = INF)
  {
    $encoding = INF === $encoding ? self::$internal_encoding : strtoupper($encoding);

    if ('UTF-8' !== $encoding && 'UTF8' !== $encoding) {
      $s = iconv($encoding, 'UTF-8//IGNORE', $s);
    }

    $s = preg_replace('/[\x00-\x19]/', '', $s);

    preg_replace('/[\x{0020}-\x{1FFF}\x{FF61}-\x{FF9F}]/u', '', $s, -1, $narrow);

    return (iconv_strlen($s, 'UTF-8') << 1) - $narrow;
  }

  /**
   * @param $haystack
   * @param $needle
   * @param $encoding
   *
   * @return int
   */
  public static function mb_substr_count($haystack, $needle, $encoding = INF)
  {
    return substr_count($haystack, $needle);
  }

  /**
   * @param $contents
   * @param $status
   *
   * @return mixed
   */
  public static function mb_output_handler($contents, $status)
  {
    return $contents;
  }

  /**
   * @param      $haystack
   * @param      $needle
   * @param bool $part
   * @param      $encoding
   *
   * @return bool|string
   */
  public static function mb_strrchr($haystack, $needle, $part = false, $encoding = INF)
  {
    INF === $encoding && $encoding = self::$internal_encoding;
    $needle = self::mb_substr($needle, 0, 1, $encoding);
    $pos = iconv_strrpos($haystack, $needle, $encoding);
    return self::getSubpart($pos, $part, $haystack, $encoding);
  }

  /**
   * @param      $haystack
   * @param      $needle
   * @param bool $part
   * @param      $encoding
   *
   * @return bool|string
   */
  public static function mb_strrichr($haystack, $needle, $part = false, $encoding = INF)
  {
    $needle = self::mb_substr($needle, 0, 1, $encoding);
    $pos = self::mb_strripos($haystack, $needle, $encoding);
    return self::getSubpart($pos, $part, $haystack, $encoding);
  }

  /**
   * @param     $haystack
   * @param     $needle
   * @param int $offset
   * @param     $encoding
   *
   * @return bool|int
   */
  public static function mb_strripos($haystack, $needle, $offset = 0, $encoding = INF)
  {
    INF === $encoding && $encoding = self::$internal_encoding;
    $haystack = self::mb_convert_case($haystack, self::MB_CASE_FOLD, $encoding);
    $needle = self::mb_convert_case($needle, self::MB_CASE_FOLD, $encoding);
    return self::mb_strrpos($haystack, $needle, $offset, $encoding);
  }

  /**
   * @param     $haystack
   * @param     $needle
   * @param int $offset
   * @param     $encoding
   *
   * @return bool|int
   */
  public static function mb_strrpos($haystack, $needle, $offset = 0, $encoding = INF)
  {
    INF === $encoding && $encoding = self::$internal_encoding;

    if ($offset != (int)$offset) {
      $offset = 0;
    } else if ($offset = (int)$offset) {
      $haystack = self::mb_substr($haystack, $offset, 2147483647, $encoding);
    }

    $pos = iconv_strrpos($haystack, $needle, $encoding . '//IGNORE');

    return false !== $pos ? $offset + $pos : false;
  }

  /**
   * @param      $haystack
   * @param      $needle
   * @param bool $part
   * @param      $encoding
   *
   * @return bool|string
   */
  public static function mb_strstr($haystack, $needle, $part = false, $encoding = INF)
  {
    $pos = strpos($haystack, $needle);
    if (false === $pos) {
      return false;
    }

    if ($part) {
      return substr($haystack, 0, $pos);
    } else {
      return substr($haystack, $pos);
    }
  }

  /**
   * @param $m
   *
   * @return string
   */
  protected static function html_encoding_callback($m)
  {
    $i = 1;
    $entities = '';
    $m = unpack('C*', htmlentities($m[0], ENT_COMPAT, 'UTF-8'));

    while (isset($m[$i])) {
      if (0x80 > $m[$i]) {
        $entities .= chr($m[$i++]);
        continue;
      }

      if (0xF0 <= $m[$i]) {
        $c = (($m[$i++] - 0xF0) << 18) + (($m[$i++] - 0x80) << 12) + (($m[$i++] - 0x80) << 6) + $m[$i++] - 0x80;
      } elseif (0xE0 <= $m[$i]) {
        $c = (($m[$i++] - 0xE0) << 12) + (($m[$i++] - 0x80) << 6) + $m[$i++] - 0x80;
      } else {
        $c = (($m[$i++] - 0xC0) << 6) + $m[$i++] - 0x80;
      }

      $entities .= '&#' . $c . ';';
    }

    return $entities;
  }

  /**
   * @param $s
   *
   * @return bool|mixed|string
   */
  protected static function title_case_lower($s)
  {
    return self::mb_convert_case($s[0], MB_CASE_LOWER, 'UTF-8');
  }

  /**
   * @param $s
   *
   * @return bool|mixed|string
   */
  protected static function title_case_upper($s)
  {
    return self::mb_convert_case($s[0], MB_CASE_UPPER, 'UTF-8');
  }
}
