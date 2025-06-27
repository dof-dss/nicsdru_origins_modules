<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * @todo Add class description.
 */
final class ContentIssueManager {

  private EntityStorageInterface $issueStorage;

  /**
   * Constructs a ContentIssueManager object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->issueStorage = $entityTypeManager->getStorage('content_issue');
  }

  /**
   * Create a Content Issue entity.
   */
  public function createIssue($title, $description, $content_entity_id, $content_revision_id, $severity): void {

    $node = $this->entityTypeManager->getStorage('node')->load($content_entity_id);

    $issue = $this->issueStorage->create([
      'label' => $title,
      'description' => $description,
      'content_entity_id' => $content_entity_id,
      'content_revision_id' => $content_revision_id,
      'severity' => $severity,
      'assigned_to' => $node->getOwnerId(),
    ]);

    $issue->save();
  }

  /**
   * Delete a Content Issue entity.
   */
  public function deleteIssue($issue_id): void {
    $this->issueStorage->delete([$issue_id]);
  }

  /**
   * Return a render array for the Content Issue default view.
   */
  public function render($issue_id): array|null {
    $issue = $this->issueStorage->load($issue_id);

    if (empty($issue)) {
      return null;
    }

    $viewBuilder = $this->entityTypeManager->getViewBuilder('content_issue');
    return $viewBuilder->view($issue, 'default');
  }


  /**
   * Delete a Content Issue entity.
   */
  public function getIssuesByContentID(string $node_id, string|null $revision_id = NULL) {
    $issues = $this->issueStorage->loadByProperties([
      'content_entity_id' => $node_id,
      'content_entity_revision_id' => $revision_id ?? $node_id,
    ]);

    return $issues;
  }



}
