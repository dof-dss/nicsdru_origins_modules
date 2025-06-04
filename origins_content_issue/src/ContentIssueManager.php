<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * @todo Add class description.
 */
final class ContentIssueManager {

  /**
   * Constructs a ContentIssueManager object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Delete a Content Issue entity.
   */
  public function deleteIssue($issue_id): void {
    $this->entityTypeManager->getStorage('content_issue')->delete([$issue_id]);
  }


  /**
   * Delete a Content Issue entity.
   */
  public function getIssuesByContentID(string $node_id, string|null $revision_id = NULL) {
    $issues = $this->entityTypeManager->getStorage('content_issue')->loadByProperties([
      'content_entity_id' => $node_id,
      'content_entity_revision_id' => $revision_id ?? $node_id,
    ]);

    return $issues;
  }

}
