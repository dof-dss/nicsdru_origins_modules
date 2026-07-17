<?php

declare(strict_types=1);

namespace Drupal\origins_help;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Fetches child pages from a configured Confluence parent page.
 */
final class ConfluenceClient {

  public function __construct(
    private readonly StateInterface $state,
    private readonly ClientInterface $httpClient,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Returns child pages of the configured parent page.
   *
   * @return array<int, array{title: string, url: string}>
   */
  public function getChildPages(): array {
    $base_url = $this->state->get('origins_help.confluence_base_url', '');
    $email = $this->state->get('origins_help.confluence_email', '');
    $token = $this->state->get('origins_help.confluence_api_token', '');
    $page_id = $this->state->get('origins_help.confluence_parent_page_id', '');

    if (empty($base_url) || empty($email) || empty($token) || empty($page_id)) {
      return [];
    }

    $endpoint = rtrim($base_url, '/') . '/wiki/api/v2/pages';

    try {
      $response = $this->httpClient->request('GET', $endpoint, [
        'auth' => [$email, $token],
        'query' => [
          'parent-id' => $page_id,
          'limit' => 250,
          'status' => 'current',
        ],
        'headers' => ['Accept' => 'application/json'],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      // The top-level _links.base gives the full wiki root URL.
      $link_base = rtrim($data['_links']['base'] ?? rtrim($base_url, '/') . '/wiki', '/');

      $pages = [];
      foreach ($data['results'] ?? [] as $page) {
        $pages[] = [
          'title' => $page['title'],
          'url' => $link_base . ($page['_links']['webui'] ?? ''),
        ];
      }

      return $pages;
    }
    catch (GuzzleException $e) {
      $this->loggerFactory->get('origins_help')->error(
        'Confluence API request failed: @message',
        ['@message' => $e->getMessage()]
      );
      return [];
    }
  }

  /**
   * Returns TRUE if all required credentials are configured.
   */
  public function isConfigured(): bool {
    return !empty($this->state->get('origins_help.confluence_base_url'))
      && !empty($this->state->get('origins_help.confluence_email'))
      && !empty($this->state->get('origins_help.confluence_api_token'))
      && !empty($this->state->get('origins_help.confluence_parent_page_id'));
  }

}
