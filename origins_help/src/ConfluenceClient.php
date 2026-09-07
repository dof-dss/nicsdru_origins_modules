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
   * Each node: ['title' => string, 'url' => string, 'children' => [...]]
   *
   * When project IDs are configured, pages that have labels but none matching
   * any of the project IDs are excluded. Pages with no labels are always
   * included.
   *
   * @return array<int, array{title: string, url: string, children: array}>
   *   A tree of content collections.
   */
  public function getPageTree(): array {
    $base_url = $this->state->get('origins_help.confluence_base_url', '');
    $email = $this->state->get('origins_help.confluence_email', '');
    $token = $this->state->get('origins_help.confluence_api_token', '');
    $page_id = $this->state->get('origins_help.confluence_parent_page_id', '');
    $max_depth = (int) $this->state->get('origins_help.confluence_max_depth', 2);
    $project_ids = self::projectIds((string) $this->state->get('origins_help.confluence_project_id', ''));

    if (empty($base_url) || empty($email) || empty($token) || empty($page_id)) {
      return [];
    }

    $link_base = rtrim($base_url, '/') . '/wiki';

    return $this->fetchLevel($page_id, 0, $max_depth, $base_url, $email, $token, $link_base, $project_ids);
  }

  /**
   * Returns trimmed, lowercased, non-empty project IDs from a stored value.
   *
   * Project IDs are stored one per line, matching the settings form's
   * textarea widget.
   *
   * @return string[]
   *   A list of project IDs.
   */
  public static function projectIds(string $value): array {
    $lines = preg_split('/\r\n|\r|\n/', $value);
    return array_values(array_unique(array_filter(array_map(
      static fn (string $line): string => strtolower(trim($line)),
      $lines,
    ), 'strlen')));
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
   * Fetches all children of a page (following pagination), filters by project
   * labels, then recurses into each included child.
   *
   * @param string[] $project_ids
   *   Lowercase project IDs to match against page labels.
   */
  private function fetchLevel(string $page_id, int $depth, int $max_depth, string $base_url, string $email, string $token, string $link_base, array $project_ids): array {
    $pages = [];

    foreach ($this->fetchAllChildren($page_id, $base_url, $email, $token) as $page) {
      $child_id = (string) $page['id'];

      $labels = $this->fetchLabels($child_id, $base_url, $email, $token);
      if (!empty($labels) && (empty($project_ids) || !array_intersect($project_ids, $labels))) {
        continue;
      }

      $children = [];
      if ($depth + 1 < $max_depth) {
        $children = $this->fetchLevel(
          $child_id,
          $depth + 1,
          $max_depth,
          $base_url,
          $email,
          $token,
          $link_base,
          $project_ids,
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

  /**
   * Returns all child pages of a given page, following Confluence pagination.
   *
   * The child/page endpoint may return results in multiple pages via
   * _links.next. This method collects all pages across every batch before
   * returning, so callers always see the full child list.
   *
   * @return array<int, mixed>
   *   Raw page objects from the Confluence API.
   */
  private function fetchAllChildren(string $page_id, string $base_url, string $email, string $token): array {
    $results = [];
    $endpoint = $base_url . "/wiki/rest/api/content/{$page_id}/child/page";
    $query = ['limit' => 250];

    // Guard against runaway pagination.
    $max_iterations = 20;

    for ($i = 0; $i < $max_iterations; $i++) {
      try {
        $options = [
          'auth' => [$email, $token],
          'headers' => ['Accept' => 'application/json'],
        ];

        // Query params are embedded in the URL for every request after the
        // first; passing them again via 'query' would duplicate them.
        if (!empty($query)) {
          $options['query'] = $query;
          $query = [];
        }

        $response = $this->httpClient->request('GET', $endpoint, $options);
        $data = json_decode($response->getBody()->getContents(), TRUE);

        $results = array_merge($results, $data['results'] ?? []);

        if (empty($data['_links']['next'])) {
          break;
        }

        // _links.base is the full wiki root (e.g. https://company.atlassian.net/wiki).
        // _links.next is a path relative to that root.
        $api_base = rtrim($data['_links']['base'] ?? ($base_url . '/wiki'), '/');
        $endpoint = $api_base . $data['_links']['next'];
      }
      catch (GuzzleException $e) {
        $this->loggerFactory->get('origins_help')->error(
          'Confluence API request failed for page @id: @message',
          ['@id' => $page_id, '@message' => $e->getMessage()]
        );
        break;
      }
    }

    return $results;
  }

  /**
   * Returns the lowercase label names for a Confluence page.
   *
   * Uses the dedicated /label endpoint rather than metadata expansion, as
   * expansion is not consistently returned by the child/page endpoint.
   * Returns an empty array on failure, which causes the page to be treated
   * as unlabelled (included regardless of project filter).
   *
   * @return string[]
   *   A list of labels for the given content.
   */
  private function fetchLabels(string $page_id, string $base_url, string $email, string $token): array {
    try {
      $response = $this->httpClient->request('GET', $base_url . "/wiki/rest/api/content/{$page_id}/label", [
        'auth' => [$email, $token],
        'headers' => ['Accept' => 'application/json'],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      return array_map('strtolower', array_column($data['results'] ?? [], 'name'));
    }
    catch (GuzzleException $e) {
      $this->loggerFactory->get('origins_help')->warning(
        'Could not fetch labels for Confluence page @id: @message',
        ['@id' => $page_id, '@message' => $e->getMessage()]
      );
      return [];
    }
  }

}
