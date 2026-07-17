<?php

declare(strict_types=1);

namespace Drupal\origins_help;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Fetches pages from a configured Confluence parent page as a nested tree.
 */
final class ConfluenceClient {

  public function __construct(
    private readonly StateInterface $state,
    private readonly ClientInterface $httpClient,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Returns direct children (and their descendants) of the configured page.
   *
   * Makes one API call per level of the tree, up to the configured max depth.
   * Uses /child/page rather than /descendant/page to avoid having to
   * reconstruct hierarchy from ancestor chains, which behaves inconsistently
   * across spaces.
   *
   * Each node: ['title' => string, 'url' => string, 'children' => [...]]
   *
   * @return array<int, array{title: string, url: string, children: array}>
   */
  public function getPageTree(): array {
    $base_url = $this->state->get('origins_help.confluence_base_url', '');
    $email = $this->state->get('origins_help.confluence_email', '');
    $token = $this->state->get('origins_help.confluence_api_token', '');
    $page_id = $this->state->get('origins_help.confluence_parent_page_id', '');
    $max_depth = (int) $this->state->get('origins_help.confluence_max_depth', 2);

    if (empty($base_url) || empty($email) || empty($token) || empty($page_id)) {
      return [];
    }

    // Compute the link base once; Confluence Cloud is always {base_url}/wiki.
    $link_base = rtrim($base_url, '/') . '/wiki';

    return $this->fetchLevel($page_id, 0, $max_depth, $base_url, $email, $token, $link_base);
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

  /**
   * Fetches one level of children and recurses up to $max_depth.
   *
   * @param string $page_id
   *   The Confluence page ID whose children to fetch.
   * @param int $depth
   *   Current recursion depth (0 = direct children of the configured root).
   * @param int $max_depth
   *   Stop recursing when $depth reaches this value.
   * @param string $base_url
   *   Confluence base URL (no trailing slash).
   * @param string $email
   *   Atlassian account email for Basic Auth.
   * @param string $token
   *   Atlassian API token for Basic Auth.
   * @param string $link_base
   *   Prepended to each page's webui path to form an absolute URL.
   *
   * @return array<int, array{title: string, url: string, children: array}>
   */
  private function fetchLevel(string $page_id, int $depth, int $max_depth, string $base_url, string $email, string $token, string $link_base): array {
    $endpoint = $base_url . "/wiki/rest/api/content/{$page_id}/child/page";

    try {
      $response = $this->httpClient->request('GET', $endpoint, [
        'auth' => [$email, $token],
        'query' => ['limit' => 250],
        'headers' => ['Accept' => 'application/json'],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      $pages = [];

      foreach ($data['results'] ?? [] as $page) {
        $children = [];
        if ($depth + 1 < $max_depth) {
          $children = $this->fetchLevel(
            (string) $page['id'],
            $depth + 1,
            $max_depth,
            $base_url,
            $email,
            $token,
            $link_base,
          );
        }

        $pages[] = [
          'title' => $page['title'],
          'url' => $link_base . ($page['_links']['webui'] ?? ''),
          'children' => $children,
        ];
      }

      return $pages;
    }
    catch (GuzzleException $e) {
      $this->loggerFactory->get('origins_help')->error(
        'Confluence API request failed for page @id: @message',
        ['@id' => $page_id, '@message' => $e->getMessage()]
      );
      return [];
    }
  }

}
