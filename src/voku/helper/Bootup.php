<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * Class Bootup
 *
 * @package voku\helper
 */
class Bootup
{
  /**
   * filter request inputs
   *
   * Ensures inputs are well formed UTF-8
   * When not, assumes Windows-1252 and converts to UTF-8
   * Tests only values, not keys
   *
   * @param int    $normalization_form
   * @param string $leading_combining
   */
  public static function filterRequestInputs($normalization_form = 4 /* n::NFC */, $leading_combining = '◌')
  {
    $a = [
        &$_FILES,
        &$_ENV,
        &$_GET,
        &$_POST,
        &$_COOKIE,
        &$_SERVER,
        &$_REQUEST,
    ];

    /** @noinspection ReferenceMismatchInspection */
    /** @noinspection ForeachSourceInspection */
    foreach ($a[0] as &$r) {
      $a[] = [
          &$r['name'],
          &$r['type'],
      ];
    }
    unset($r, $a[0]);

    $len = \count($a) + 1;
    for ($i = 1; $i < $len; ++$i) {
      /** @noinspection ReferenceMismatchInspection */
      /** @noinspection ForeachSourceInspection */
      foreach ($a[$i] as &$r) {
        /** @noinspection ReferenceMismatchInspection */
        $s = $r; // $r is a reference, $s a copy
        if (\is_array($s)) {
          $a[$len++] = &$r;
        } else {
          $r = self::filterString($s, $normalization_form, $leading_combining);
        }
      }
      unset($r, $a[$i]);
    }
  }

  /**
   * Filter current REQUEST_URI .
   *
   * @param string|null $uri <p>If null is set, then the server REQUEST_URI will be used.</p>
   * @param bool        $exit
   *
   * @return mixed
   */
  public static function filterRequestUri($uri = null, $exit = true)
  {
    if (null === $uri) {

      if (!isset($_SERVER['REQUEST_URI'])) {
        return false;
      }

      $uri = $_SERVER['REQUEST_URI'];
    }

    $uriOrig = $uri;

    //
    // Ensures the URL is well formed UTF-8
    //

    if (UTF8::is_utf8(\rawurldecode($uri)) === true) {
      return $uri;
    }

    //
    // When not, assumes Windows-1252 and redirects to the corresponding UTF-8 encoded URL
    //

    $uri = (string)\preg_replace_callback(
        '/[\x80-\xFF]+/',
        function ($m) {
          return \rawurlencode($m[0]);
        },
        $uri
    );

    $uri = (string)\preg_replace_callback(
        '/(?:%[89A-F][0-9A-F])+/i',
        function ($m) {
          return \rawurlencode(UTF8::rawurldecode($m[0]));
        },
        $uri
    );

    if (
        $uri !== $uriOrig
        &&
        $exit === true
        &&
        \headers_sent() === false
    ) {
      // Use ob_start() to buffer content and avoid problem of headers already sent...
      $severProtocol = ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1');
      \header($severProtocol . ' 301 Moved Permanently');
      \header('Location: ' . $uri);
      exit();
    }

    return $uri;
  }

  /**
   * Normalizes to UTF-8 NFC, converting from WINDOWS-1252 when needed.
   *
   * @param mixed  $input
   * @param int    $normalization_form
   * @param string $leading_combining
   *
   * @return mixed
   */
  public static function filterString($input, int $normalization_form = 4 /* n::NFC */, string $leading_combining = '◌')
  {
    return UTF8::filter($input, $normalization_form, $leading_combining);
  }

  /**
   * Get random bytes via "random_bytes()"
   *
   * @param  int $length <p>output length</p>
   *
   * @return  string|false <p>false on error</p>
   *
   * @throws \Exception <p>If it was not possible to gather sufficient entropy.</p>
   */
  public static function get_random_bytes($length)
  {
    if (!$length) {
      return false;
    }

    $length = (int)$length;

    if ($length <= 0) {
      return false;
    }

    return \random_bytes($length);
  }

  /**
   * bootstrap
   */
  public static function initAll()
  {
    \ini_set('default_charset', 'UTF-8');

    // everything is init via composer, so we are done here ...
  }

  /**
   * Determines if the current version of PHP is equal to or greater than the supplied value.
   *
   * @param string $version <p>e.g. "7.1"<p>
   *
   * @return bool <p>Return <strong>true</strong> if the current version is $version or higher</p>
   */
  public static function is_php($version): bool
  {
    static $_IS_PHP;

    $version = (string)$version;

    if (!isset($_IS_PHP[$version])) {
      $_IS_PHP[$version] = \version_compare(PHP_VERSION, $version, '>=');
    }

    return $_IS_PHP[$version];
  }
}
