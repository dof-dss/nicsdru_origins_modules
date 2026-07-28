<?php

namespace Drupal\origins_internal_link_checker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Converts absolute internal links to relative links before an entity is saved.
 */
class InternalLinkProcessor {

  /**
   * Constructs an InternalLinkProcessor object.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected RequestStack $requestStack,
    protected MigrationExecutionContext $migrationContext,
  ) {}

  /**
   * Processes text fields on a content entity.
   */
  public function processEntity(EntityInterface $entity): void {
    if ($this->migrationContext->isImportActive()) {
      return;
    }

    if (!$entity instanceof ContentEntityInterface) {
      return;
    }

    foreach ($entity->getFieldDefinitions() as $field_name => $field) {
      if (in_array($field->getType(), ['text', 'text_long', 'text_with_summary'], TRUE)) {
        $this->processField($entity, $field_name);
      }
    }
  }

  /**
   * Processes links in one text field.
   */
  public function processField(ContentEntityInterface $entity, string $field_name): void {
    $field_items = $entity->get($field_name);
    foreach ($field_items->getValue() as $delta => $values) {
      $text = $values['value'] ?? '';
      $converted_text = $this->convertText($text);
      if ($converted_text !== $text) {
        $field_items->get($delta)->set('value', $converted_text);
      }
    }
  }

  /**
   * Converts links to known site hosts without changing surrounding markup.
   */
  public function convertText(string $text): string {
    $excluded_urls = $this->configuredLines('site_url_list_exclude');

    return preg_replace_callback(
      '~(\bhref\s*=\s*)([\'"])(.*?)\2~is',
      function (array $matches) use ($excluded_urls): string {
        $url = $matches[3];
        if (in_array($url, $excluded_urls, TRUE)) {
          return $matches[0];
        }

        $url_parts = parse_url($url);
        if (!is_array($url_parts) ||
          !isset($url_parts['scheme'], $url_parts['host']) ||
          !in_array(strtolower($url_parts['scheme']), ['http', 'https'], TRUE) ||
          isset($url_parts['user']) || isset($url_parts['pass']) ||
          !$this->isKnownAuthority($url_parts)) {
          return $matches[0];
        }

        $relative_url = $url_parts['path'] ?? '/';
        $relative_url = $relative_url === '' ? '/' : $relative_url;
        if (isset($url_parts['query'])) {
          $relative_url .= '?' . $url_parts['query'];
        }
        if (isset($url_parts['fragment'])) {
          $relative_url .= '#' . $url_parts['fragment'];
        }

        return $matches[1] . $matches[2] . $relative_url . $matches[2];
      },
      $text,
    );
  }

  /**
   * Checks whether parsed URL parts identify the current site or an alias.
   */
  private function isKnownAuthority(array $url_parts): bool {
    $candidate = $this->normaliseAuthority(
      $url_parts['host'],
      $url_parts['port'] ?? NULL,
      $url_parts['scheme'],
    );

    $authorities = [];
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      $authorities[] = $this->normaliseAuthority(
        $request->getHost(),
        $request->getPort(),
        $request->getScheme(),
      );
    }

    foreach ($this->configuredLines('site_url_list') as $configured_url) {
      $parts = parse_url($configured_url);
      if (is_array($parts) && isset($parts['scheme'], $parts['host'])) {
        $authorities[] = $this->normaliseAuthority(
          $parts['host'],
          $parts['port'] ?? NULL,
          $parts['scheme'],
        );
      }
    }

    return in_array($candidate, $authorities, TRUE);
  }

  /**
   * Normalises a host and port for exact authority comparison.
   */
  private function normaliseAuthority(string $host, ?int $port, string $scheme): string {
    $host = preg_replace('/^www\./i', '', strtolower($host));
    $scheme = strtolower($scheme);
    if (($scheme === 'http' && $port === 80) ||
      ($scheme === 'https' && $port === 443)) {
      $port = NULL;
    }

    return $host . ($port === NULL ? '' : ':' . $port);
  }

  /**
   * Returns trimmed, non-empty lines from a configuration value.
   */
  private function configuredLines(string $key): array {
    $value = $this->configFactory
      ->get('origins_internal_link_checker.linksettings')
      ->get($key);
    if (!is_string($value) || trim($value) === '') {
      return [];
    }

    $lines = preg_split('/\r\n|\r|\n/', $value);
    return array_values(array_filter(array_map('trim', $lines), 'strlen'));
  }

}
