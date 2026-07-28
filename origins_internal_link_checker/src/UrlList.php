<?php

namespace Drupal\origins_internal_link_checker;

/**
 * Normalises and validates configured URL lists.
 */
class UrlList {

  /**
   * Returns trimmed, non-empty URL lines.
   */
  public static function lines(string $value): array {
    $lines = preg_split('/\r\n|\r|\n/', $value);
    return array_values(array_filter(array_map('trim', $lines), 'strlen'));
  }

  /**
   * Returns a consistently formatted URL list for configuration storage.
   */
  public static function normalise(string $value): string {
    return implode(PHP_EOL, self::lines($value));
  }

  /**
   * Returns invalid HTTP URLs from a configured list.
   *
   * Domain aliases may contain a port and one trailing slash, but no path,
   * query string, or fragment. Excluded URLs may contain all URL components.
   */
  public static function invalidUrls(string $value, bool $allow_path): array {
    return array_values(array_filter(
      self::lines($value),
      static function (string $url) use ($allow_path): bool {
        if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
          return TRUE;
        }

        $parts = parse_url($url);
        if (!is_array($parts) ||
          !isset($parts['scheme'], $parts['host']) ||
          !in_array(strtolower($parts['scheme']), ['http', 'https'], TRUE) ||
          isset($parts['user']) || isset($parts['pass'])) {
          return TRUE;
        }

        $path = $parts['path'] ?? '';
        if (!$allow_path &&
          (!in_array($path, ['', '/'], TRUE) ||
          isset($parts['query']) || isset($parts['fragment']))) {
          return TRUE;
        }

        return FALSE;
      },
    ));
  }

}
