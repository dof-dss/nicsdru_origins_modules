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
  ) {}

  /**
   * Processes text fields on a content entity.
   */
  public function processEntity(EntityInterface $entity): void {
    // If this hook has been invoked from a migration, bail out.
    $page = $this->requestStack->getCurrentRequest()?->getRequestUri() ?? '';
    if (preg_match('|^/batch|', $page) || $page === '/') {
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
    $config = $this->configFactory->get('origins_internal_link_checker.linksettings');
    $exclude_list_bare = $config->get('site_url_list_exclude');
    $exclude_url_list = !empty($exclude_list_bare)
      ? preg_split('/\r\n|\r|\n/', $exclude_list_bare)
      : [];

    $field_items = $entity->get($field_name);
    foreach ($field_items->getValue() as $delta => $values) {
      $text = $values['value'] ?? '';
      $matches = [];
      if (preg_match_all('|href\=[\'"]+([^ >"\']*)[\'"]+[^>]*>|', $text, $matches)) {
        foreach ($matches[1] as $original_link) {
          if (!in_array($original_link, $exclude_url_list, TRUE)) {
            if (preg_match('|http://(.*)|', $original_link) ||
              preg_match('|https://(.*)|', $original_link)) {
              $text = $this->convertAbsoluteLink($text, $original_link);
            }
          }
        }
        $field_items->get($delta)->set('value', $text);
      }
    }
  }

  /**
   * Converts an internal absolute link to a relative link.
   */
  public function convertAbsoluteLink(string $body_text, string $original_link): string {
    $replace_url_list = explode(PHP_EOL, $this->urlsToReplace());
    $replace_url_list = array_filter($replace_url_list);

    foreach ($replace_url_list as $replace_url) {
      $replace_url = str_replace(["\n", "\t", "\r"], '', $replace_url);
      if (!preg_match('|/$|', $replace_url)) {
        $replace_url .= '/';
      }
      if (str_contains($original_link, $replace_url)) {
        $body_text = preg_replace(
          '~href\=[\'"]+' . $replace_url . '~',
          'href="/',
          $body_text,
          1,
        );
      }
    }

    return $body_text;
  }

  /**
   * Builds all configured variants of the current site's domains.
   */
  public function urlsToReplace(): string {
    $host = $this->requestStack->getCurrentRequest()?->getHost() ?? '';
    $protocols = ['http://', 'http://www.', 'https://', 'https://www.'];
    $domains = $this->configFactory
      ->get('origins_internal_link_checker.linksettings')
      ->get('site_url_list');
    $domains = empty($domains) ? [] : explode(PHP_EOL, $domains);
    $domains[] = $host;

    foreach ($domains as $key => $domain) {
      $domain = str_replace('www.', '', $domain);
      foreach ($protocols as $protocol) {
        $domain = str_replace($protocol, '', $domain);
      }
      $domains[$key] = $domain;
    }

    $urls_to_replace = '';
    foreach ($protocols as $protocol) {
      foreach ($domains as $domain) {
        $urls_to_replace .= $protocol . $domain . "\r\n";
      }
    }

    return $urls_to_replace;
  }

}
