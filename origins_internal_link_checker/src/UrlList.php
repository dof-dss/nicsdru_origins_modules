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
    $invalid_urls = [];

    foreach (self::lines($value) as $url) {
      if (self::isInvalidUrl($url, $allow_path)) {
        $invalid_urls[] = $url;
      }
    }

    return $invalid_urls;
  }

  /**
   * Determines whether one configured URL is invalid.
   */
  private static function isInvalidUrl(string $url, bool $allow_path): bool {
    if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
      return TRUE;
    }

    $parts = parse_url($url);
    if (empty($parts) || empty($parts['scheme']) || empty($parts['host'])) {
      return TRUE;
    }

    $scheme = strtolower($parts['scheme']);
    if (!in_array($scheme, ['http', 'https'], TRUE)) {
      return TRUE;
    }

    $contains_credentials = array_key_exists('user', $parts) ||
      array_key_exists('pass', $parts);
    if ($contains_credentials) {
      return TRUE;
    }

    if ($allow_path) {
      return FALSE;
    }

    $contains_path = !empty($parts['path']) && $parts['path'] !== '/';
    $contains_query = array_key_exists('query', $parts);
    $contains_fragment = array_key_exists('fragment', $parts);

    return $contains_path || $contains_query || $contains_fragment;
  }

}
